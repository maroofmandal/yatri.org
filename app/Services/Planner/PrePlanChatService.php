<?php

namespace App\Services\Planner;

use App\Models\User;
use App\Services\Llm\LlmClient;
use Illuminate\Support\Facades\Log;

class PrePlanChatService
{
    public function __construct(protected LlmClient $llm) {}

    /**
     * Start the pre-plan chat by generating 3 initial dynamic clarification questions.
     */
    public function initChat(array $formValues, ?User $user): array
    {
        if (!$this->llm->enabled()) {
            return $this->fallbackQuestions();
        }

        try {
            $destList = collect($formValues['destinations'] ?? [])
                ->map(fn($d) => ($d['name'] ?? 'Destination') . ' (' . ($d['days'] ?? 3) . ' days)')
                ->implode(' -> ');

            $userProfileText = $user ? "Name: {$user->name}, Bio: {$user->bio}, Home: {$user->current_city}, Age: {$user->age}, Travel preferences: {$user->travel_preferences}, Travel style: {$user->travel_style}" : "Guest / Unknown";

            $interests = isset($formValues['interests']) ? implode(', ', (array) $formValues['interests']) : 'General';
            $dates = ($formValues['start_date'] ?? 'flexible') . ' to ' . ($formValues['end_date'] ?? 'flexible');

            $systemPrompt = "You are Yatri's travel advisor. Based on the selected destinations, travel style, budget, travelers, interests, dates, and the user's profile details (like age, home city, preferences), generate exactly 3 personalized, relevant clarification questions. Each question must collect missing details (e.g. food requirements, daily start times, activity priorities, pace) that would make the trip plan better. Do not ask generic questions. For each question, provide 3-4 options, and select one recommended option based on their travel style, budget, and destinations.";

            $userPrompt = "Selected values:\n"
                . "- Destinations/Route: {$formValues['origin']} → {$destList}\n"
                . "- Travel Style: {$formValues['style']}\n"
                . "- Budget: {$formValues['budget_total']} {$formValues['currency']}\n"
                . "- Travelers: {$formValues['travelers']}\n"
                . "- Interests: {$interests}\n"
                . "- Dates: {$dates}\n\n"
                . "User Profile Context:\n{$userProfileText}\n\n"
                . "Generate the 3 questions now matching the required schema.";

            $schema = [
                'type' => 'OBJECT',
                'properties' => [
                    'questions' => [
                        'type' => 'ARRAY',
                        'items' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'id' => ['type' => 'STRING'],
                                'question' => ['type' => 'STRING'],
                                'options' => [
                                    'type' => 'ARRAY',
                                    'items' => ['type' => 'STRING']
                                ],
                                'recommended' => ['type' => 'STRING']
                            ],
                            'required' => ['id', 'question', 'options', 'recommended']
                        ]
                    ]
                ],
                'required' => ['questions']
            ];

            $res = $this->llm->generate($systemPrompt, $userPrompt, [
                'schema' => $schema,
                'temperature' => 0.4
            ]);

            $parsed = json_decode($res['text'], true);
            if (!empty($parsed['questions'])) {
                return $parsed['questions'];
            }

            throw new \RuntimeException("Empty or invalid questions structure returned.");
        } catch (\Throwable $e) {
            Log::warning("Failed to generate dynamic pre-plan questions: " . $e->getMessage());
            return $this->fallbackQuestions();
        }
    }

    /**
     * Get the next question or compressed summary, based on the answers collected so far.
     */
    public function nextQuestion(array $formValues, array $answers, ?User $user): array
    {
        if (!$this->llm->enabled()) {
            return [
                'has_more' => false,
                'compressed_context' => "User prefers balanced pace, local food, and standard comfort."
            ];
        }

        try {
            $destList = collect($formValues['destinations'] ?? [])
                ->map(fn($d) => ($d['name'] ?? 'Destination') . ' (' . ($d['days'] ?? 3) . ' days)')
                ->implode(' -> ');

            $userProfileText = $user ? "Name: {$user->name}, Bio: {$user->bio}, Home: {$user->current_city}, Age: {$user->age}, Travel preferences: {$user->travel_preferences}, Travel style: {$user->travel_style}" : "Guest / Unknown";

            $interests = isset($formValues['interests']) ? implode(', ', (array) $formValues['interests']) : 'General';
            $dates = ($formValues['start_date'] ?? 'flexible') . ' to ' . ($formValues['end_date'] ?? 'flexible');

            $answersText = collect($answers)->map(fn($a) => "Q: {$a['question']}\nA: {$a['answer']}")->implode("\n\n");
            $answersCount = count($answers);

            $systemPrompt = "You are Yatri's travel advisor. You are in a conversational pre-plan flow to collect preferences for a trip. We have asked some questions and got answers.\n"
                . "If the answers so far are sufficient to build a perfect trip, or if we have reached 5 questions total (current question count: {$answersCount}), set has_more to false and provide a structured compressed_context summary.\n"
                . "Otherwise, set has_more to true and ask the next adaptive question based on the previous answers. Keep it highly relevant (e.g. if budget and they like fine dining, ask about food budget balance; if family with kids, ask about pace/stroller friendliness). Provide 3-4 options and one recommended option.";

            $userPrompt = "Selected values:\n"
                . "- Destinations/Route: {$formValues['origin']} → {$destList}\n"
                . "- Travel Style: {$formValues['style']}\n"
                . "- Budget: {$formValues['budget_total']} {$formValues['currency']}\n"
                . "- Travelers: {$formValues['travelers']}\n"
                . "- Interests: {$interests}\n"
                . "- Dates: {$dates}\n\n"
                . "User Profile Context:\n{$userProfileText}\n\n"
                . "Conversation so far:\n{$answersText}\n\n"
                . "Respond according to the schema now.";

            $schema = [
                'type' => 'OBJECT',
                'properties' => [
                    'has_more' => ['type' => 'BOOLEAN'],
                    'question' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id' => ['type' => 'STRING'],
                            'question' => ['type' => 'STRING'],
                            'options' => [
                                'type' => 'ARRAY',
                                'items' => ['type' => 'STRING']
                            ],
                            'recommended' => ['type' => 'STRING']
                        ],
                        'required' => ['id', 'question', 'options', 'recommended']
                    ],
                    'compressed_context' => [
                        'type' => 'STRING',
                        'description' => 'A structured summary of all preferences, dietary restrictions, accommodation comfort level, and transport priorities, plus travel constraints.'
                    ]
                ],
                'required' => ['has_more']
            ];

            $res = $this->llm->generate($systemPrompt, $userPrompt, [
                'schema' => $schema,
                'temperature' => 0.4
            ]);

            $parsed = json_decode($res['text'], true) ?: [];

            if (isset($parsed['has_more'])) {
                return $parsed;
            }

            throw new \RuntimeException("Invalid adaptive response structure returned.");
        } catch (\Throwable $e) {
            Log::warning("Failed to generate adaptive pre-plan question: " . $e->getMessage());
            return [
                'has_more' => false,
                'compressed_context' => "User preferred selections: " . collect($answers)->map(fn($a) => $a['answer'])->implode(', ')
            ];
        }
    }

    protected function fallbackQuestions(): array
    {
        return [
            [
                'id' => 'pace',
                'question' => 'What is your preferred daily pace?',
                'options' => [
                    'Relaxed (1-2 main sights, plenty of downtime)',
                    'Balanced (3-4 sights, moderate walking)',
                    'Packed (high energy, packed days, early starts)'
                ],
                'recommended' => 'Balanced (3-4 sights, moderate walking)'
            ],
            [
                'id' => 'food',
                'question' => 'Do you have any food preferences or restrictions?',
                'options' => [
                    'No restrictions, want local street food & cafes',
                    'Vegetarian or vegan',
                    'Halal',
                    'Fine dining focus',
                    'Budget self-catering & cheap eats'
                ],
                'recommended' => 'No restrictions, want local street food & cafes'
            ],
            [
                'id' => 'transport',
                'question' => 'How do you prefer getting around cities?',
                'options' => [
                    'Public transport (trains, buses) — budget friendly',
                    'Taxis & rideshares (Uber/Grab) — convenient & comfortable',
                    'Walking mostly',
                    'Car rental / private driver'
                ],
                'recommended' => 'Public transport (trains, buses) — budget friendly'
            ]
        ];
    }
}
