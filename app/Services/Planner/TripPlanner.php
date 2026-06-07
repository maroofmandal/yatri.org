<?php

namespace App\Services\Planner;

use App\Models\GeminiLog;
use App\Models\Trip;
use App\Services\Llm\LlmClient;
use App\Services\Google\PlacesClient;
use App\Services\Google\WeatherClient;
use Throwable;

class TripPlanner
{
    /** Budget categories that sum to the grand total. */
    public const CATEGORIES = [
        'accommodation', 'food', 'activities',
        'local_transport', 'intercity_transport', 'flights', 'misc',
    ];

    public function __construct(protected LlmClient $llm) {}

    /**
     * Generate (or regenerate) a full plan for the trip and persist it.
     */
    public function generate(Trip $trip): Trip
    {
        $trip->update(['status' => 'generating', 'error' => null]);

        if (! $this->llm->enabled()) {
            return $this->fillSample($trip);
        }

        try {
            $startedAt = microtime(true);

            // Pass 1 — grounded live research (Search + Maps).
            $research = $this->llm->generate(
                $this->researchSystem(),
                $this->researchPrompt($trip),
                ['grounding' => true, 'temperature' => 0.6]
            );
            $this->log($trip, 'research', $research, true, $startedAt);

            // Pass 2 — structure into JSON, looping until it fits the budget cap.
            [$plan, $structure] = $this->structureWithBudgetFit($trip, $research['text']);
            $this->log($trip, 'plan', $structure, false, $startedAt);

            if (empty($plan['days'])) {
                throw new \RuntimeException('Model returned an empty itinerary.');
            }

            // Post-generation: enrich with Google Places data (ratings, reviews, photos).
            $enricher = new PlacesEnricher(new PlacesClient);
            $plan = $enricher->enrich($plan);
            $plan = $this->enrichWeather($trip, $plan, new WeatherClient);
            $plan = $this->normalizePriceStatuses($plan);

            $budget = $plan['budget'] ?? [];
            $total = $this->budgetTotal($budget);

            $trip->update([
                'status' => 'ready',
                'title' => $plan['title'] ?? $trip->title,
                'plan' => $plan,
                'budget_breakdown' => $budget,
                'fit_status' => $this->fitStatus($total, (float) $trip->budget_total),
                'grounding' => $research['grounding'],
                'model_used' => $research['model'],
            ]);

            return $trip->refresh();
        } catch (Throwable $e) {
            report($e);
            // Resilience: if live generation fails (bad/denied key, quota, network),
            // fall back to a usable sample plan rather than a hard failure.
            $trip->update(['error' => 'Live AI unavailable: '.$e->getMessage()]);

            return $this->fillSample($trip);
        }
    }

    /**
     * Live, grounded Q&A about a saved trip.
     *
     * @return array{answer:string, grounding:array}
     */
    public function chat(Trip $trip, string $question): array
    {
        if (! $this->llm->enabled()) {
            return [
                'answer' => 'Live AI answers need a Gemini API key (Admin → Settings → AI). Your saved plan is shown above in the meantime.',
                'grounding' => [],
            ];
        }

        try {
            $startedAt = microtime(true);
            $res = $this->llm->generate(
                "You are Yatri's live travel assistant. Use Google Search and Google Maps grounding for current facts "
                .'(prices, opening hours, weather, transit, closures, events). Answer concretely and briefly for THIS specific trip. '
                .'Today is '.now()->toFormattedDateString().'.',
                $this->chatContext($trip)."\n\nTraveler asks: ".$question,
                ['grounding' => true, 'temperature' => 0.5]
            );
            $this->log($trip, 'chat', $res, true, $startedAt);

            return ['answer' => $res['text'], 'grounding' => $res['grounding']];
        } catch (Throwable $e) {
            report($e);

            return ['answer' => 'Sorry — the assistant hit an error: '.$e->getMessage(), 'grounding' => []];
        }
    }

    // ── Pass 2 + budget fit ────────────────────────────────────────────────

    /**
     * @return array{0: array, 1: array} [plan, lastGeminiResult]
     */
    protected function structureWithBudgetFit(Trip $trip, string $research): array
    {
        $cap = (float) $trip->budget_total;
        $feedback = '';
        $plan = [];
        $result = [];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $result = $this->llm->generate(
                $this->structureSystem($trip),
                $this->structurePrompt($trip, $research, $feedback),
                ['schema' => ItinerarySchema::get(), 'temperature' => 0.4]
            );

            $plan = json_decode($result['text'], true) ?: [];

            // Truncated JSON: model hit its output-token ceiling mid-itinerary.
            if (empty($plan['days']) && ($result['finish'] ?? null) === 'MAX_TOKENS') {
                throw new \RuntimeException(
                    'Itinerary exceeded the model output limit (too many days/stops for one pass). '
                    .'Try a shorter trip or fewer destinations.'
                );
            }

            $total = $this->budgetTotal($plan['budget'] ?? []);

            if ($cap <= 0 || $total <= $cap * 1.05) {
                break; // fits (or no cap set)
            }

            $over = round($total - $cap);
            $feedback = "Your previous plan totalled {$total} {$trip->currency}, which is {$over} OVER the hard cap of {$cap}. "
                ."Cut about {$over} {$trip->currency}: pick cheaper hotels (hostels / 3-star), use buses or trains instead of flights or express services, "
                .'and drop or swap paid activities for free ones. Keep it realistic and recompute every cost.';
        }

        return [$plan, $result];
    }

    protected function budgetTotal(array $budget): float
    {
        $sum = 0.0;
        $has = false;
        foreach (self::CATEGORIES as $cat) {
            if (isset($budget[$cat])) {
                $sum += (float) $budget[$cat];
                $has = true;
            }
        }

        return $has ? round($sum, 2) : (float) ($budget['total'] ?? 0);
    }

    protected function fitStatus(float $total, float $cap): string
    {
        if ($cap <= 0) {
            return 'fit';
        }
        if ($total > $cap * 1.02) {
            return 'over';
        }

        return $total <= $cap * 0.85 ? 'under' : 'fit';
    }

    protected function enrichWeather(Trip $trip, array $plan, WeatherClient $weather): array
    {
        $days = collect($plan['days'] ?? []);
        if ($days->isEmpty()) {
            return $plan;
        }

        $route = collect($plan['route'] ?? $trip->destinations)
            ->filter(fn ($stop) => ! empty($stop['name']))
            ->keyBy(fn ($stop) => mb_strtolower((string) $stop['name']));

        $forecastByCity = [];
        if ($trip->start_date
            && $weather->isConfigured()
            && $trip->start_date->gte(now()->startOfDay())
            && $trip->start_date->lte(now()->addDays(9)->endOfDay())) {
            $cities = $days->pluck('city')->filter()->unique();

            foreach ($cities as $city) {
                $stop = $route->get(mb_strtolower((string) $city));
                if (! isset($stop['lat'], $stop['lng'])) {
                    continue;
                }

                $forecast = $weather->dailyForecast((float) $stop['lat'], (float) $stop['lng'], 10);
                if ($forecast) {
                    $forecastByCity[$city] = collect($forecast)->keyBy('date')->all();
                }
            }
        }

        $weatherDays = [];
        foreach ($days as $index => $day) {
            $city = $day['city'] ?? null;
            $date = $trip->start_date ? $trip->start_date->copy()->addDays($index)->toDateString() : null;
            $live = $city && $date && isset($forecastByCity[$city][$date]) ? $forecastByCity[$city][$date] : null;

            $entry = $live ?: [
                'date' => $date,
                'source' => 'seasonal_estimate',
                'status' => $trip->start_date ? 'seasonal_estimate' : 'flexible_dates_estimate',
                'summary' => 'Seasonal estimate. Live Google Weather forecast is available only within 10 days of travel.',
                'icon' => null,
                'temperature_min_c' => null,
                'temperature_max_c' => null,
                'precipitation_probability' => null,
                'humidity' => null,
                'uv_index' => null,
            ];

            $entry['day'] = $day['day'] ?? ($index + 1);
            $entry['city'] = $city;
            $weatherDays[] = $entry;
            $plan['days'][$index]['weather'] = $entry;
        }

        $plan['weather'] = [
            'source' => collect($weatherDays)->contains(fn ($day) => ($day['source'] ?? null) === 'google_weather')
                ? 'google_weather'
                : 'seasonal_estimate',
            'note' => 'Google Weather gives live daily forecasts for up to 10 days. Later or flexible dates use seasonal estimates.',
            'days' => $weatherDays,
        ];

        return $plan;
    }

    protected function normalizePriceStatuses(array $plan): array
    {
        foreach ($plan['flights'] ?? [] as $i => $flight) {
            $plan['flights'][$i]['price_status'] = $flight['price_status'] ?? 'estimated';
        }

        foreach ($plan['hotels'] ?? [] as $i => $hotel) {
            $plan['hotels'][$i]['price_status'] = $hotel['price_status'] ?? 'estimated';
        }

        foreach ($plan['days'] ?? [] as $d => $day) {
            foreach ($day['items'] ?? [] as $i => $item) {
                if (isset($item['entry_fee_status'])) {
                    continue;
                }

                $cost = $item['cost'] ?? null;
                $plan['days'][$d]['items'][$i]['entry_fee_status'] = ((float) $cost) <= 0 ? 'free' : 'estimated';
            }
        }

        return $plan;
    }

    // ── Prompts ────────────────────────────────────────────────────────────

    protected function researchSystem(): string
    {
        return 'You are Yatri, an expert travel planner with live web and maps access. '
            .'Use Google Search and Google Maps grounding to gather CURRENT, REAL, specific data '
            .'(2026 prices, real names, opening hours, durations). Be factual and concise — no fluff. '
            .'Today is '.now()->toFormattedDateString().'.';
    }

    protected function researchPrompt(Trip $trip): string
    {
        $stops = collect($trip->destinations)
            ->map(fn ($d) => $d['name'].' ('.($d['nights'] ?? 2).' nights)')
            ->implode(' → ');

        $interests = $trip->interests ? implode(', ', $trip->interests) : 'general sightseeing';

        return <<<TXT
        Plan a {$trip->days}-day trip for {$trip->travelers} traveler(s).
        Origin: {$trip->origin}
        Route: {$trip->origin} → {$stops} → back to {$trip->origin}
        Dates: {$this->dateLabel($trip)}
        Total budget (HARD CAP, whole party, whole trip): {$trip->budget_total} {$trip->currency}
        Travel style: {$trip->style}
        Interests: {$interests}

        Research and report live, usable data to build a COSTED day-by-day plan that fits the budget:
        1. Best transport for every leg (origin↔stops and between stops): mode, typical 2026 price per person, duration. Compare flight vs train/bus where relevant.
        2. 2–3 well-rated hotels per stop in a sensible area, with the current or typical nightly price for the style above. Treat hotel prices as estimates unless a source clearly confirms exact date rates.
        3. Top attractions per stop with entry fees (say if free) and the exact Google Maps search query. Mark whether fees are free, estimated, or confirmed by source.
        4. Typical daily food cost per person for this style.
        5. Visa, seasonal weather, live-weather availability, or closure notes for these dates.
        Use real names and real current numbers. Keep it tight.
        TXT;
    }

    protected function structureSystem(Trip $trip): string
    {
        return "You convert travel research into a STRICT JSON itinerary.\n"
            ."HARD RULE 1 (budget): the realistic grand total must be <= {$trip->budget_total} {$trip->currency} for the WHOLE party "
            ."({$trip->travelers} traveler(s)) across {$trip->days} days. If realistic costs exceed the cap, downgrade "
            ."(cheaper hotels, buses/trains over flights for SHORT legs, fewer paid activities) until total <= cap, and explain in fit.note.\n"
            .'HARD RULE 2 (transport realism): international or intercontinental legs — anywhere separated by sea/ocean or roughly >1500 km, '
            .'e.g. Mumbai→Tokyo — MUST be FLIGHTS. Never label such a leg train, bus or ferry. Put long-haul air legs in `flights`; '
            ."only genuine ground/intercity legs go in `transport` with mode train/bus/car.\n"
            ."Every cost is the TOTAL for the whole party for the whole trip, in {$trip->currency} (NOT per person), "
            .'EXCEPT hotels.price_per_night which is per night. Use plain numbers, no currency symbols. '
            ."Fill lat/lng for every route stop. Make budget.total equal the sum of the category amounts.\n"
            .'Set flights[].price_status and hotels[].price_status to `estimated` because no paid live fare/rate API is connected. '
            ."Set days[].items[].entry_fee_status to one of `free`, `estimated`, or `confirmed_by_source`.\n"
            .'Also populate: route_options (2 distinct routing options with pros/cons), flights (each leg with airlines, type, duration, price, booking_query), '
            .'weather (seasonal estimates per city/day if available), packing (grouped lists suited to the season/dates), culture (dos/donts per country or place), and countdown (a pre-trip timeline from ~8 weeks out to 1 week before).';
    }

    protected function structurePrompt(Trip $trip, string $research, string $feedback): string
    {
        $stops = collect($trip->destinations)->pluck('name')->implode(', ');

        return "Trip: origin {$trip->origin}; stops: {$stops}; {$trip->days} days; {$trip->travelers} traveler(s); "
            ."style {$trip->style}; budget cap {$trip->budget_total} {$trip->currency}; dates {$this->dateLabel($trip)}.\n\n"
            ."Research:\n{$research}\n\n"
            .($feedback ? $feedback."\n\n" : '')
            .'Return the JSON itinerary now.';
    }

    protected function chatContext(Trip $trip): string
    {
        $plan = $trip->plan ?? [];
        $route = collect($plan['route'] ?? $trip->destinations)->pluck('name')->implode(' → ');

        return "Trip context — \"{$trip->title}\": {$trip->origin} → {$route}, {$trip->days} days, "
            ."{$trip->travelers} traveler(s), budget {$trip->budget_total} {$trip->currency}, style {$trip->style}, "
            ."dates {$this->dateLabel($trip)}.";
    }

    protected function dateLabel(Trip $trip): string
    {
        if ($trip->start_date) {
            return $trip->start_date->format('d M Y')
                .($trip->end_date ? ' – '.$trip->end_date->format('d M Y') : '');
        }

        return 'flexible';
    }

    // ── Sample plan (no API key) ───────────────────────────────────────────

    /**
     * Deterministic, budget-aware sample so the whole flow is usable without a key.
     */
    protected function fillSample(Trip $trip): Trip
    {
        $cap = (float) $trip->budget_total;
        $travelers = max(1, (int) $trip->travelers);
        $stops = collect($trip->destinations)->values();
        $dailyByStyle = ['budget' => 70, 'mid' => 140, 'luxury' => 320][$trip->style] ?? 140;

        // Split the cap so the demo always "fits".
        $target = $cap > 0 ? $cap : $dailyByStyle * $trip->days * $travelers;
        $budget = [
            'currency' => $trip->currency,
            'accommodation' => round($target * 0.34),
            'food' => round($target * 0.22),
            'activities' => round($target * 0.16),
            'local_transport' => round($target * 0.08),
            'intercity_transport' => round($target * 0.10),
            'flights' => round($target * 0.07),
            'misc' => round($target * 0.03),
        ];
        $budget['total'] = array_sum(array_intersect_key($budget, array_flip(self::CATEGORIES)));

        $route = $stops->map(fn ($d) => [
            'name' => $d['name'],
            'lat' => $d['lat'] ?? null,
            'lng' => $d['lng'] ?? null,
            'nights' => (int) ($d['nights'] ?? 2),
        ])->all();

        // Day-by-day across the stops.
        $days = [];
        $dayNum = 1;
        $cursor = $trip->start_date ? $trip->start_date->copy() : null;
        foreach ($stops as $stop) {
            $nights = max(1, (int) ($stop['nights'] ?? 2));
            for ($n = 0; $n < $nights; $n++) {
                $days[] = [
                    'day' => $dayNum,
                    'date' => $cursor?->format('D d M'),
                    'city' => $stop['name'],
                    'title' => $n === 0 ? 'Arrive in '.$stop['name'] : 'Explore '.$stop['name'],
                    'summary' => 'Sample day — add a Gemini API key for a live, grounded plan.',
                    'items' => [
                        ['time' => 'Morning',   'activity' => 'Top sights in '.$stop['name'], 'cost' => round($budget['activities'] / max(1, $trip->days)), 'entry_fee_status' => 'estimated', 'map_query' => 'Things to do in '.$stop['name']],
                        ['time' => 'Afternoon', 'activity' => 'Neighbourhood walk & local food', 'cost' => 0, 'entry_fee_status' => 'free', 'map_query' => 'Best food in '.$stop['name']],
                        ['time' => 'Evening',   'activity' => 'Dinner & relax', 'cost' => 0, 'entry_fee_status' => 'free', 'map_query' => 'Dinner '.$stop['name']],
                    ],
                    'tags' => [$n === 0 ? 'arrival' : 'explore'],
                ];
                $dayNum++;
                $cursor?->addDay();
            }
        }

        $hotels = $stops->map(fn ($d) => [
            'city' => $d['name'],
            'name' => 'Well-rated stay in '.$d['name'],
            'area' => 'Central',
            'rating' => 4.3,
            'price_per_night' => round(($budget['accommodation'] / max(1, $trip->days))),
            'nights' => (int) ($d['nights'] ?? 2),
            'total' => round(($budget['accommodation'] / max(1, $trip->days)) * (int) ($d['nights'] ?? 2)),
            'booking_query' => 'Hotels in '.$d['name'],
            'price_status' => 'estimated',
        ])->all();

        $names = $stops->pluck('name')->values();
        $first = $names->first();
        $last = $names->last();

        // International air legs: origin -> first stop, last stop -> origin (long-haul = flight only).
        $flights = [];
        if ($first) {
            $flights[] = ['from' => $trip->origin, 'to' => $first, 'airlines' => 'Major carriers', 'type' => 'Non-stop / 1 stop', 'duration' => '—', 'price' => round($budget['flights'] / 2), 'booking_query' => $trip->origin.' to '.$first, 'price_status' => 'estimated'];
            $flights[] = ['from' => $last, 'to' => $trip->origin, 'airlines' => 'Major carriers', 'type' => 'Non-stop / 1 stop', 'duration' => '—', 'price' => round($budget['flights'] / 2), 'booking_query' => $last.' to '.$trip->origin, 'price_status' => 'estimated'];
        }

        // Ground / intercity legs between adjacent stops.
        $transport = [];
        for ($i = 0; $i < $names->count() - 1; $i++) {
            $transport[] = [
                'from' => $names[$i],
                'to' => $names[$i + 1],
                'mode' => 'Train / bus',
                'duration' => '—',
                'cost' => round($budget['intercity_transport'] / max(1, $names->count() - 1)),
                'note' => 'Sample estimate',
                'booking_query' => $names[$i].' to '.$names[$i + 1],
            ];
        }

        $routeOptions = [[
            'label' => 'Option A — in order',
            'summary' => 'Visit stops in the order you listed.',
            'sequence' => $names->all(),
            'pros' => 'Simple, follows your plan.',
            'cons' => 'May involve some backtracking.',
        ]];
        if ($names->count() > 2) {
            $routeOptions[] = [
                'label' => 'Option B — reversed',
                'summary' => 'Reverse sweep to cut backtracking.',
                'sequence' => $names->reverse()->values()->all(),
                'pros' => 'Less doubling back.',
                'cons' => 'Different arrival city.',
            ];
        }

        $plan = [
            'title' => $trip->title,
            'summary' => 'Sample itinerary generated without AI. Add a Gemini API key in Admin → Settings → AI for a live, grounded, budget-fit plan.',
            'demo' => true,
            'route' => $route,
            'route_options' => $routeOptions,
            'flights' => $flights,
            'transport' => $transport,
            'hotels' => $hotels,
            'days' => $days,
            'budget' => $budget,
            'fit' => [
                'within_budget' => true,
                'total' => $budget['total'],
                'target' => $cap,
                'note' => 'Sample budget scaled to fit your cap.',
            ],
            'tips' => [
                'This is a sample. Connect Gemini (free tier) for real hotels, prices and live data.',
                'Book intercity transport early for the best fares.',
            ],
            'packing' => [
                ['title' => 'Essentials', 'items' => ['Passport + visas', 'Travel insurance', 'Cards + some local cash', 'Power bank & adapters', 'eSIM / SIM', 'Offline maps']],
                ['title' => 'Clothing & day kit', 'items' => ['Layers for the season', 'Very comfortable walking shoes', 'Compact umbrella / rain shell', 'One smart outfit', 'Reusable water bottle']],
            ],
            'culture' => [[
                'place' => 'General',
                'dos' => ['Learn a few local greetings', 'Carry small cash for markets', 'Respect dress codes at religious sites'],
                'donts' => ['Don’t assume card is accepted everywhere', 'Don’t photograph people without asking', 'Don’t ignore local tipping norms'],
            ]],
            'countdown' => [
                ['when' => '8 weeks before', 'tasks' => 'Book international flights; start visa applications.'],
                ['when' => '6 weeks before', 'tasks' => 'Book hotels and any sell-out attractions.'],
                ['when' => '1 month before', 'tasks' => 'Book intercity transport; buy an eSIM.'],
                ['when' => '2 weeks before', 'tasks' => 'Confirm bookings; download offline maps & translator.'],
                ['when' => '1 week before', 'tasks' => 'Web check-in; pack; screenshot confirmations.'],
            ],
        ];

        $plan = $this->enrichWeather($trip, $plan, new WeatherClient);
        $plan = $this->normalizePriceStatuses($plan);

        $trip->update([
            'status' => 'ready',
            'plan' => $plan,
            'budget_breakdown' => $budget,
            'fit_status' => 'fit',
            'grounding' => [],
            'model_used' => 'sample',
        ]);

        return $trip->refresh();
    }

    // ── Logging ────────────────────────────────────────────────────────────

    protected function log(Trip $trip, string $kind, array $result, bool $grounded, float $startedAt): void
    {
        GeminiLog::create([
            'user_id' => $trip->user_id,
            'trip_id' => $trip->id,
            'kind' => $kind,
            'model' => $result['model'] ?? $this->llm->model(),
            'prompt_tokens' => $result['usage']['prompt'] ?? 0,
            'output_tokens' => $result['usage']['output'] ?? 0,
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'grounded' => $grounded,
            'status' => 'ok',
        ]);
    }
}
