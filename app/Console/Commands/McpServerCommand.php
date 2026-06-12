<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class McpServerCommand extends Command
{
    protected $signature = 'trips:mcp-server';
    protected $description = 'Start the stdio-based MCP server for user and trip context';

    public function handle(): int
    {
        // Disable output decoration, we need raw stdout for JSON-RPC
        $this->output->getFormatter()->setDecorated(false);

        Log::info("MCP Server started");

        while (true) {
            $line = fgets(STDIN);
            if ($line === false) {
                break;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            try {
                $request = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->sendError(null, -32700, 'Parse error');
                    continue;
                }

                $this->handleRequest($request);
            } catch (\Throwable $e) {
                Log::error("MCP Error: " . $e->getMessage());
                $this->sendError(null, -32603, 'Internal error: ' . $e->getMessage());
            }
        }

        return 0;
    }

    protected function handleRequest(array $req): void
    {
        $id = $req['id'] ?? null;
        $method = $req['method'] ?? '';
        $params = $req['params'] ?? [];

        Log::info("MCP Request: method={$method}, id=" . json_encode($id));

        switch ($method) {
            case 'initialize':
                $this->sendResult($id, [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => (object)[]
                    ],
                    'serverInfo' => [
                        'name' => 'yatri-mcp-server',
                        'version' => '1.0.0'
                    ]
                ]);
                break;

            case 'notifications/initialized':
                // No response needed
                break;

            case 'tools/list':
                $this->sendResult($id, [
                    'tools' => [
                        [
                            'name' => 'get_user_profile',
                            'description' => 'Retrieve non-sensitive user profile details like home base, travel style, bio, age, current city, and default currency.',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'user_id' => [
                                        'type' => 'integer',
                                        'description' => 'The ID of the user. If not provided, defaults to first user in database.'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_trip_draft',
                            'description' => 'Retrieve destination, budget, style, start/end dates, interests, and other plan details for a specific trip draft.',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'trip_id' => [
                                        'type' => 'integer',
                                        'description' => 'The ID of the trip.'
                                    ],
                                    'share_token' => [
                                        'type' => 'string',
                                        'description' => 'The share token of the trip.'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_user_trips',
                            'description' => 'Retrieve the non-sensitive list of past trips taken by a user.',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'user_id' => [
                                        'type' => 'integer',
                                        'description' => 'The ID of the user. If not provided, defaults to first user.'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_similar_trips',
                            'description' => 'Search and retrieve non-sensitive details of past trips to similar destinations to help personalize future planning.',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'destination' => [
                                        'type' => 'string',
                                        'description' => 'The destination name to search for.'
                                    ]
                                ],
                                'required' => ['destination']
                            ]
                        ]
                    ]
                ]);
                break;

            case 'tools/call':
                $name = $params['name'] ?? '';
                $arguments = $params['arguments'] ?? [];
                $this->handleToolCall($id, $name, $arguments);
                break;

            default:
                $this->sendError($id, -32601, "Method not found: {$method}");
        }
    }

    protected function handleToolCall($id, string $name, array $args): void
    {
        Log::info("MCP Tool Call: name={$name}, args=" . json_encode($args));

        switch ($name) {
            case 'get_user_profile':
                $userId = $args['user_id'] ?? null;
                $user = $userId ? User::find($userId) : User::first();
                if (!$user) {
                    $this->sendToolResult($id, "User not found.", true);
                    return;
                }
                $profile = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'bio' => $user->bio,
                    'current_city' => $user->current_city,
                    'home_base' => $user->home_base,
                    'travel_style' => $user->travel_style,
                    'default_currency' => $user->default_currency,
                    'age' => $user->age,
                    'travel_preferences' => $user->travel_preferences,
                    'total_days_traveled' => $user->total_days_traveled,
                    'total_kilometers' => $user->total_kilometers,
                ];
                $this->sendToolResult($id, json_encode($profile, JSON_PRETTY_PRINT));
                break;

            case 'get_trip_draft':
                $tripId = $args['trip_id'] ?? null;
                $shareToken = $args['share_token'] ?? null;
                if ($tripId) {
                    $trip = Trip::find($tripId);
                } elseif ($shareToken) {
                    $trip = Trip::where('share_token', $shareToken)->first();
                } else {
                    $this->sendToolResult($id, "Must provide either trip_id or share_token.", true);
                    return;
                }
                if (!$trip) {
                    $this->sendToolResult($id, "Trip draft not found.", true);
                    return;
                }
                $details = [
                    'id' => $trip->id,
                    'share_token' => $trip->share_token,
                    'title' => $trip->title,
                    'origin' => $trip->origin,
                    'destinations' => $trip->destinations,
                    'start_date' => $trip->start_date?->toDateString(),
                    'end_date' => $trip->end_date?->toDateString(),
                    'days' => $trip->days,
                    'nights' => $trip->nights,
                    'travelers' => $trip->travelers,
                    'budget_total' => $trip->budget_total,
                    'currency' => $trip->currency,
                    'style' => $trip->style,
                    'interests' => $trip->interests,
                    'status' => $trip->status,
                    'compressed_chat_context' => $trip->compressed_chat_context,
                ];
                $this->sendToolResult($id, json_encode($details, JSON_PRETTY_PRINT));
                break;

            case 'get_user_trips':
                $userId = $args['user_id'] ?? null;
                $user = $userId ? User::find($userId) : User::first();
                if (!$user) {
                    $this->sendToolResult($id, "User not found.", true);
                    return;
                }
                $trips = $user->trips()
                    ->where('status', 'ready')
                    ->get(['id', 'title', 'origin', 'destinations', 'days', 'nights', 'budget_total', 'currency', 'style'])
                    ->toArray();
                $this->sendToolResult($id, json_encode($trips, JSON_PRETTY_PRINT));
                break;

            case 'get_similar_trips':
                $destination = $args['destination'] ?? '';
                if (empty($destination)) {
                    $this->sendToolResult($id, "Destination parameter is required.", true);
                    return;
                }
                $trips = Trip::where('status', 'ready')
                    ->where('destinations', 'LIKE', '%' . $destination . '%')
                    ->limit(5)
                    ->get(['id', 'title', 'origin', 'destinations', 'days', 'nights', 'budget_total', 'currency', 'style'])
                    ->toArray();
                $this->sendToolResult($id, json_encode($trips, JSON_PRETTY_PRINT));
                break;

            default:
                $this->sendError($id, -32601, "Tool not found: {$name}");
        }
    }

    protected function sendResult($id, array $result): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'result' => $result,
            'id' => $id
        ];
        echo json_encode($response) . "\n";
    }

    protected function sendToolResult($id, string $text, bool $isError = false): void
    {
        $this->sendResult($id, [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $text
                ]
            ],
            'isError' => $isError
        ]);
    }

    protected function sendError($id, int $code, string $message): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'id' => $id
        ];
        echo json_encode($response) . "\n";
    }
}
