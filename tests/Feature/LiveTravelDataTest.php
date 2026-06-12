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

    public function test_generated_trip_includes_open_meteo_weather(): void
    {
        Cache::flush();
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'latitude' => 28.61,
                'longitude' => 77.21,
                'utc_offset_seconds' => 19800,
                'daily' => [
                    'time' => [now()->addDay()->toDateString()],
                    'weather_code' => [2],
                    'temperature_2m_max' => [27],
                    'temperature_2m_min' => [19],
                    'precipitation_sum' => [0.0],
                    'precipitation_probability_max' => [10],
                    'wind_speed_10m_max' => [12],
                    'uv_index_max' => [6],
                ],
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

        $this->assertSame('open_meteo', $trip->plan['weather']['source']);
        $this->assertSame('live', $trip->plan['weather']['days'][0]['status']);
        $this->assertSame(2, $trip->plan['weather']['days'][0]['weather_code']);
        $this->assertSame('estimated', $trip->plan['flights'][0]['price_status']);
        $this->assertSame('estimated', $trip->plan['hotels'][0]['price_status']);
        $this->assertSame('estimated', $trip->plan['days'][0]['items'][0]['entry_fee_status']);
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
                    'source' => 'open_meteo',
                    'note' => 'Live weather from Open-Meteo.',
                    'days' => [[
                        'day' => 1,
                        'date' => now()->addMonths(2)->toDateString(),
                        'city' => 'Goa',
                        'source' => 'open_meteo',
                        'status' => 'live',
                        'weather_code' => null,
                        'icon' => null,
                        'icon_class' => null,
                        'summary' => 'Weather data unavailable for this date.',
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
            ->assertSee('Weather data unavailable')
            ->assertSee('Museum visit');
    }

    public function test_show_page_renders_tabs_and_posts_media_reviews(): void
    {
        $trip = Trip::create([
            'title' => 'Tabs testing sample',
            'origin' => 'Mumbai',
            'destinations' => [['name' => 'Delhi', 'lat' => 28.6139, 'lng' => 77.2090, 'nights' => 1]],
            'days' => 1,
            'travelers' => 2,
            'budget_total' => 1000,
            'currency' => 'USD',
            'style' => 'mid',
            'status' => 'ready',
        ]);

        $this->get(route('trip.show', $trip))
            ->assertOk()
            ->assertSee('Trips')
            ->assertSee('Posts')
            ->assertSee('Media')
            ->assertSee('Reviews')
            ->assertSee('Log in to post to this Trip')
            ->assertSee('Log in to upload media to this Trip')
            ->assertSee('Log in to leave a review');
    }

    public function test_post_page_renders_add_media_and_review_links_if_has_trip(): void
    {
        $user = \App\Models\User::factory()->create();
        $trip = Trip::create([
            'user_id' => $user->id,
            'title' => 'Delhi sample',
            'origin' => 'Mumbai',
            'destinations' => [['name' => 'Delhi', 'lat' => 28.6139, 'lng' => 77.2090, 'nights' => 1]],
            'days' => 1,
            'travelers' => 2,
            'budget_total' => 1000,
            'currency' => 'USD',
            'style' => 'mid',
            'status' => 'ready',
        ]);

        $post = \App\Models\Post::create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'title' => 'Test Post Title',
            'body' => 'Test Post Body',
            'type' => 'text',
            'is_public' => true,
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Add post media')
            ->assertSee('Write a review')
            ->assertSee('tab=media&open_form=1')
            ->assertSee('tab=reviews&open_form=1');
    }

    private function llm(): LlmClient
    {
        return new LlmClient(new GeminiClient, new SearchProvider);
    }
}
