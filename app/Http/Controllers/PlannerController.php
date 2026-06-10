<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Models\Destination;
use App\Models\Setting;
use App\Models\Trip;
use App\Services\Planner\TripPlanner;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PlannerController extends Controller
{
    public function create()
    {
        $destinations = Destination::active()->orderByDesc('popularity')->limit(12)->get();
        $recent = Trip::where('is_public', true)->where('status', 'ready')->latest()->limit(6)->get();

        // Admin-set fallback FX rates (1 USD → X) for client-side conversion.
        $fxRates = Setting::get('fx_rates', []);
        if (!is_array($fxRates)) {
            $fxRates = [];
        }

        return view('planner.create', compact('destinations', 'recent', 'fxRates'));
    }

    public function store(StorePlanRequest $request)
    {
        $data = $request->validated();

        $dests = collect($data['destinations'])->map(fn ($d) => [
            'name'   => $d['name'],
            'days'   => (int) ($d['days'] ?? 3),
            'nights' => (int) ($d['nights'] ?? 2),
            'lat'    => $d['lat'] ?? null,
            'lng'    => $d['lng'] ?? null,
        ])->all();

        $trip = Trip::create([
            'user_id'      => $request->user()?->id,
            'session_id'   => $request->user() ? null : $request->session()->getId(),
            'title'        => $this->defaultTitle($data['origin'], $dests),
            'origin'       => $data['origin'],
            'destinations' => $dests,
            'start_date'   => $data['start_date'] ?? null,
            'end_date'     => $data['end_date'] ?? null,
            'days'         => $this->computeDays($data, $dests),
            'nights'       => $this->computeNights($dests),
            'travelers'    => $data['travelers'],
            'budget_total' => $data['budget_total'],
            'currency'     => strtoupper($data['currency']),
            'style'        => $data['style'],
            'interests'    => $data['interests'] ?? [],
            'status'       => 'draft',
        ]);

        return redirect()->route('trip.show', $trip);
    }

    public function show(Trip $trip)
    {
        abort_unless($trip->is_public || $this->canManage($trip), 404);

        if ($trip->isReady()) {
            $trip->increment('views');

            $posts = $trip->posts()
                ->where('is_public', true)
                ->with(['user', 'media', 'likes', 'comments'])
                ->withCount(['likes', 'comments'])
                ->latest()
                ->get();

            $reviews = $trip->reviews()
                ->with('user')
                ->withCount(['likes'])
                ->latest()
                ->get();

            $postsMediaIds = $posts->pluck('id');
            $media = \App\Models\Media::query()
                ->where(function($q) use ($trip, $postsMediaIds) {
                    $q->where(function($q2) use ($trip) {
                        $q2->where('mediable_type', \App\Models\Trip::class)
                           ->where('mediable_id', $trip->id);
                    })->orWhere(function($q2) use ($postsMediaIds) {
                        $q2->where('mediable_type', \App\Models\Post::class)
                           ->whereIn('mediable_id', $postsMediaIds);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return view('planner.show', compact('trip', 'posts', 'reviews', 'media'));
        }

        return view('planner.generating', compact('trip'));
    }

    /**
     * Loader endpoint for the generating page. Non-blocking + poll-based.
     *
     * Live generation legitimately takes ~30–120s (grounded research + structured
     * JSON + Places/weather enrichment). Doing that inside the HTTP request blows
     * past front-proxy/PHP timeouts, so the browser saw a "Network error" even
     * though the plan generated fine. Instead we kick generation off AFTER the
     * response is flushed and let the page poll this endpoint until it's ready.
     */
    public function generate(Trip $trip, TripPlanner $planner)
    {
        $force = request()->boolean('force');

        // Already done.
        if ($trip->isReady() && ! $force) {
            return $this->generateStatus($trip);
        }

        // A run is already in flight (and not stuck) — just report status; keep polling.
        $stuck = $trip->status === 'generating'
            && $trip->updated_at
            && $trip->updated_at->lt(now()->subMinutes(4));

        if ($trip->status === 'generating' && ! $stuck && ! $force) {
            return $this->generateStatus($trip);
        }

        // Kick off (or restart a stuck/forced) generation. Mark it in-flight now so
        // concurrent polls don't trigger a second run, then do the heavy work after
        // the response is sent to the browser.
        $trip->update(['status' => 'generating', 'error' => null]);

        $tripId = $trip->id;
        dispatch(function () use ($tripId) {
            @set_time_limit(0);
            $trip = Trip::find($tripId);
            if (! $trip) {
                return;
            }
            try {
                app(TripPlanner::class)->generate($trip);
            } catch (\Throwable $e) {
                report($e);
                $trip->update(['status' => 'error', 'error' => 'Generation failed: '.$e->getMessage()]);
            }
        })->afterResponse();

        return $this->generateStatus($trip);
    }

    protected function generateStatus(Trip $trip)
    {
        return response()->json([
            'status'   => $trip->status,
            'error'    => $trip->error,
            'redirect' => route('trip.show', $trip),
        ]);
    }

    public function regenerate(Trip $trip)
    {
        abort_unless($this->canManage($trip), 403);

        $trip->update([
            'status'    => 'draft',
            'plan'      => null,
            'error'     => null,
        ]);

        return redirect()->route('trip.show', $trip);
    }

    public function chat(Request $request, Trip $trip, TripPlanner $planner)
    {
        $data = $request->validate(['question' => ['required', 'string', 'max:500']]);

        return response()->json($planner->chat($trip, $data['question']));
    }

    // ── helpers ──

    protected function computeDays(array $data, array $dests): int
    {
        if (! empty($data['start_date']) && ! empty($data['end_date'])) {
            return Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        }

        $totalDays = array_sum(array_map(fn ($d) => (int) ($d['days'] ?? 3), $dests));

        return max(1, $totalDays);
    }

    protected function computeNights(array $dests): int
    {
        return max(1, array_sum(array_map(fn ($d) => (int) ($d['nights'] ?? 2), $dests)));
    }

    protected function defaultTitle(string $origin, array $dests): string
    {
        $names = collect($dests)->pluck('name');
        $head = $names->take(2)->implode(' & ');

        if ($names->count() > 2) {
            $head .= ' +' . ($names->count() - 2);
        }

        return $head ?: 'Trip from ' . $origin;
    }

    protected function canManage(Trip $trip): bool
    {
        $user = request()->user();

        // Admin can always manage
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Logged-in user owns the trip
        if ($user && $trip->user_id === $user->id) {
            return true;
        }

        // Guest trip: only the same session can manage
        if ($trip->user_id === null) {
            return $trip->session_id === request()->session()->getId();
        }

        return false;
    }
}
