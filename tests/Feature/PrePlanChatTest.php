<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use App\Services\Llm\LlmClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrePlanChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_pre_plan_chat_init_returns_questions(): void
    {
        $this->mock(LlmClient::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('generate')->andReturn([
                'text' => json_encode([
                    'questions' => [
                        [
                            'id' => 'pace',
                            'question' => 'What is your preferred daily pace?',
                            'options' => ['Relaxed', 'Balanced', 'Packed'],
                            'recommended' => 'Balanced'
                        ],
                        [
                            'id' => 'food',
                            'question' => 'Do you have any food preferences?',
                            'options' => ['No restrictions', 'Vegetarian'],
                            'recommended' => 'No restrictions'
                        ],
                        [
                            'id' => 'transport',
                            'question' => 'How do you prefer getting around?',
                            'options' => ['Public transport', 'Taxis'],
                            'recommended' => 'Public transport'
                        ]
                    ]
                ])
            ]);
        });

        $payload = [
            'form_values' => [
                'origin' => 'Mumbai',
                'destinations' => [['name' => 'Tokyo', 'days' => 5, 'nights' => 4]],
                'travelers' => 2,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
                'style' => 'mid',
                'budget_total' => 3000,
                'currency' => 'USD',
                'interests' => ['Culture', 'Nature']
            ]
        ];

        $response = $this->postJson(route('plan.pre-chat-init'), $payload);

        $response->assertOk()
            ->assertJsonCount(3, 'questions')
            ->assertJsonPath('questions.0.id', 'pace')
            ->assertJsonPath('questions.0.recommended', 'Balanced');
    }

    public function test_pre_plan_chat_next_returns_follow_up_or_compressed_context(): void
    {
        $this->mock(LlmClient::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('generate')->andReturn([
                'text' => json_encode([
                    'has_more' => false,
                    'compressed_context' => 'User wants balanced pace, no dietary restrictions, and public transport.'
                ])
            ]);
        });

        $payload = [
            'form_values' => [
                'origin' => 'Mumbai',
                'destinations' => [['name' => 'Tokyo', 'days' => 5, 'nights' => 4]],
                'travelers' => 2,
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-05',
                'style' => 'mid',
                'budget_total' => 3000,
                'currency' => 'USD',
                'interests' => ['Culture', 'Nature']
            ],
            'answers' => [
                ['id' => 'pace', 'question' => 'Pace?', 'answer' => 'Balanced'],
                ['id' => 'food', 'question' => 'Food?', 'answer' => 'No restrictions'],
                ['id' => 'transport', 'question' => 'Transport?', 'answer' => 'Public transport']
            ]
        ];

        $response = $this->postJson(route('plan.pre-chat-next'), $payload);

        $response->assertOk()
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('compressed_context', 'User wants balanced pace, no dietary restrictions, and public transport.');
    }

    public function test_user_profile_saves_age_and_travel_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.profile'), [
            'name' => 'John Doe',
            'email' => $user->email,
            'bio' => 'Lover of adventure',
            'current_city' => 'Mumbai',
            'home_base' => 'Mumbai',
            'travel_style' => 'mid',
            'default_currency' => 'USD',
            'age' => 30,
            'travel_preferences' => 'Prefers hiking, local food, and early starts.'
        ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertEquals(30, $user->age);
        $this->assertEquals('Prefers hiking, local food, and early starts.', $user->travel_preferences);
    }

    public function test_async_image_generation_routes(): void
    {
        $trip = Trip::create([
            'title' => 'Tokyo trip',
            'origin' => 'Mumbai',
            'destinations' => [['name' => 'Tokyo', 'days' => 5, 'nights' => 4]],
            'days' => 5,
            'nights' => 4,
            'travelers' => 2,
            'budget_total' => 3000,
            'currency' => 'USD',
            'style' => 'mid',
            'status' => 'ready',
        ]);

        $response = $this->postJson(route('trip.generate-images', $trip));
        $response->assertOk()->assertJson(['ok' => true]);

        $response = $this->getJson(route('trip.images-status', $trip));
        $response->assertOk()
            ->assertJsonPath('ready', false)
            ->assertJsonPath('image_url', null);
    }
}
