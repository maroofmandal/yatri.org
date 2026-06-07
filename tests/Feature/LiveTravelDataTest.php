<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Trip;
use App\Services\Gemini\GeminiClient;
use App\Services\Llm\LlmClient;
use App\Services\Planner\TripPlanner;
use App\Services\Search\SearchProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveTravelDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_trip_within_ten_days_includes_google_weather(): void
    {
        Cache::flush();
        Setting::put('google_maps_api_key', 'maps-key', 'ai', 'secret');
        Http::fake([
            'weather.googleapis.com/*' => Http::response([
                'forecastDays' => [[
                    'displayDate' => [
                        'year' => now()->addDay()->year,
                        'month' => now()->addDay()->month,
                        'day' => now()->addDay()->day,
                    ],
                    'minTemperature' => ['degrees' => 19],
                    'maxTemperature' => ['degrees' => 27],
                    'daytimeForecast' => [
                        'weatherCondition' => ['description' => ['text' => 'Clear']],
                        'precipitation' => ['probability' => ['percent' => 10]],
                    ],
                ]],
            ]),
        ]);

        $trip = Trip::create([
            'title' => 'Delhi sample',
            'origin' => 'Mumbai',
            'destinations' => [['name' => 'Delhi', 'lat' => 28.6139, 'lng' => 77.2090, 'nights' => 1]],
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'days' => 1,
            'travelers' => 2,
            'budget_total' => 1000,
            'currency' => 'USD',
            'style' => 'mid',
            'status' => 'draft',
        ]);

        $trip = (new TripPlanner($this->llm()))->generate($trip);

        $this->assertSame('google_weather', $trip->plan['weather']['source']);
        $this->assertSame('live_forecast', $trip->plan['weather']['days'][0]['status']);
        $this->assertSame('estimated', $trip->plan['flights'][0]['price_status']);
        $this->assertSame('estimated', $trip->plan['hotels'][0]['price_status']);
        $this->assertSame('estimated', $trip->plan['days'][0]['items'][0]['entry_fee_status']);
    }

    public function test_future_trip_uses_seasonal_weather_estimate(): void
    {
        $trip = Trip::create([
            'title' => 'Future sample',
            'origin' => 'Mumbai',
            'destinations' => [['name' => 'Tokyo', 'lat' => 35.6762, 'lng' => 139.6503, 'nights' => 1]],
            'start_date' => now()->addMonths(2)->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'days' => 1,
            'travelers' => 2,
            'budget_total' => 2000,
            'currency' => 'USD',
            'style' => 'mid',
            'status' => 'draft',
        ]);

        $trip = (new TripPlanner($this->llm()))->generate($trip);

        $this->assertSame('seasonal_estimate', $trip->plan['weather']['source']);
        $this->assertSame('seasonal_estimate', $trip->plan['weather']['days'][0]['status']);
    }

    public function test_show_page_renders_weather_estimated_prices_and_entry_fees(): void
    {
        $trip = Trip::create([
            'title' => 'Rendered sample',
            'origin' => 'Mumbai',
            'destinations' => [['name' => 'Goa', 'lat' => 15.2993, 'lng' => 74.1240, 'nights' => 1]],
            'start_date' => now()->addMonths(2)->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'days' => 1,
            'travelers' => 2,
            'budget_total' => 1200,
            'currency' => 'USD',
            'style' => 'mid',
            'status' => 'ready',
            'plan' => [
                'title' => 'Rendered sample',
                'summary' => 'Test plan',
                'route' => [['name' => 'Goa', 'lat' => 15.2993, 'lng' => 74.1240, 'nights' => 1]],
                'weather' => [
                    'note' => 'Live Google Weather appears for trips within 10 days.',
                    'days' => [[
                        'day' => 1,
                        'date' => now()->addMonths(2)->toDateString(),
                        'city' => 'Goa',
                        'source' => 'seasonal_estimate',
                        'status' => 'seasonal_estimate',
                        'summary' => 'Warm and humid seasonal estimate.',
                    ]],
                ],
                'flights' => [[
                    'from' => 'Mumbai',
                    'to' => 'Goa',
                    'airlines' => 'Major carriers',
                    'price' => 200,
                    'price_status' => 'estimated',
                ]],
                'hotels' => [[
                    'city' => 'Goa',
                    'name' => 'Well-rated stay',
                    'price_per_night' => 80,
                    'nights' => 1,
                    'price_status' => 'estimated',
                ]],
                'days' => [[
                    'day' => 1,
                    'city' => 'Goa',
                    'title' => 'Explore Goa',
                    'items' => [[
                        'activity' => 'Museum visit',
                        'cost' => 10,
                        'entry_fee_status' => 'estimated',
                        'map_query' => 'Goa museum',
                    ]],
                ]],
                'budget' => ['total' => 1200],
            ],
            'budget_breakdown' => ['total' => 1200],
            'fit_status' => 'fit',
        ]);

        $this->get(route('trip.show', $trip))
            ->assertOk()
            ->assertSee('Weather during trip')
            ->assertSee('Warm and humid seasonal estimate.')
            ->assertSee('Estimated · check live before booking')
            ->assertSee('Estimated · check live rates')
            ->assertSee('Museum visit')
            ->assertSee('Estimated');
    }

    private function llm(): LlmClient
    {
        return new LlmClient(new GeminiClient, new SearchProvider);
    }
}
