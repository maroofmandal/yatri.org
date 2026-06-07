<?php

namespace App\Services\Planner;

/**
 * Gemini responseSchema (OpenAPI subset, UPPERCASE types) for a costed itinerary.
 */
class ItinerarySchema
{
    public static function get(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'summary' => ['type' => 'STRING'],

                'route' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name' => ['type' => 'STRING'],
                            'lat' => ['type' => 'NUMBER'],
                            'lng' => ['type' => 'NUMBER'],
                            'nights' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['name', 'nights'],
                    ],
                ],

                'route_options' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'label' => ['type' => 'STRING'],
                            'summary' => ['type' => 'STRING'],
                            'sequence' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'pros' => ['type' => 'STRING'],
                            'cons' => ['type' => 'STRING'],
                        ],
                        'required' => ['label', 'sequence'],
                    ],
                ],

                'flights' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'from' => ['type' => 'STRING'],
                            'to' => ['type' => 'STRING'],
                            'airlines' => ['type' => 'STRING'],
                            'type' => ['type' => 'STRING'], // Non-stop / 1 stop
                            'duration' => ['type' => 'STRING'],
                            'price' => ['type' => 'NUMBER'],
                            'price_status' => ['type' => 'STRING'],
                            'booking_query' => ['type' => 'STRING'],
                        ],
                        'required' => ['from', 'to', 'price'],
                    ],
                ],

                'transport' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'from' => ['type' => 'STRING'],
                            'to' => ['type' => 'STRING'],
                            'mode' => ['type' => 'STRING'],
                            'duration' => ['type' => 'STRING'],
                            'cost' => ['type' => 'NUMBER'],
                            'note' => ['type' => 'STRING'],
                            'booking_query' => ['type' => 'STRING'],
                        ],
                        'required' => ['from', 'to', 'mode', 'cost'],
                    ],
                ],

                'hotels' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'city' => ['type' => 'STRING'],
                            'name' => ['type' => 'STRING'],
                            'area' => ['type' => 'STRING'],
                            'rating' => ['type' => 'NUMBER'],
                            'price_per_night' => ['type' => 'NUMBER'],
                            'nights' => ['type' => 'INTEGER'],
                            'total' => ['type' => 'NUMBER'],
                            'price_status' => ['type' => 'STRING'],
                            'booking_query' => ['type' => 'STRING'],
                            'place_query' => ['type' => 'STRING', 'description' => "Searchable place name with city for Google Places lookup, e.g. 'Senso-ji Temple Tokyo'"],
                        ],
                        'required' => ['city', 'name', 'price_per_night', 'nights'],
                    ],
                ],

                'days' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'day' => ['type' => 'INTEGER'],
                            'date' => ['type' => 'STRING'],
                            'city' => ['type' => 'STRING'],
                            'title' => ['type' => 'STRING'],
                            'summary' => ['type' => 'STRING'],
                            'items' => [
                                'type' => 'ARRAY',
                                'items' => [
                                    'type' => 'OBJECT',
                                    'properties' => [
                                        'time' => ['type' => 'STRING'],
                                        'activity' => ['type' => 'STRING'],
                                        'note' => ['type' => 'STRING'],
                                        'cost' => ['type' => 'NUMBER'],
                                        'entry_fee_status' => ['type' => 'STRING'],
                                        'map_query' => ['type' => 'STRING'],
                                        'place_query' => ['type' => 'STRING', 'description' => "Searchable place name with city for Google Places lookup, e.g. 'Senso-ji Temple Tokyo'"],
                                    ],
                                    'required' => ['activity'],
                                ],
                            ],
                            'tags' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'weather' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'date' => ['type' => 'STRING'],
                                    'city' => ['type' => 'STRING'],
                                    'source' => ['type' => 'STRING'],
                                    'status' => ['type' => 'STRING'],
                                    'summary' => ['type' => 'STRING'],
                                    'temperature_min_c' => ['type' => 'NUMBER'],
                                    'temperature_max_c' => ['type' => 'NUMBER'],
                                    'precipitation_probability' => ['type' => 'NUMBER'],
                                ],
                            ],
                        ],
                        'required' => ['day', 'city', 'title'],
                    ],
                ],

                'weather' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'source' => ['type' => 'STRING'],
                        'note' => ['type' => 'STRING'],
                        'days' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'day' => ['type' => 'INTEGER'],
                                    'date' => ['type' => 'STRING'],
                                    'city' => ['type' => 'STRING'],
                                    'source' => ['type' => 'STRING'],
                                    'status' => ['type' => 'STRING'],
                                    'summary' => ['type' => 'STRING'],
                                    'temperature_min_c' => ['type' => 'NUMBER'],
                                    'temperature_max_c' => ['type' => 'NUMBER'],
                                    'precipitation_probability' => ['type' => 'NUMBER'],
                                ],
                            ],
                        ],
                    ],
                ],

                'budget' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'currency' => ['type' => 'STRING'],
                        'accommodation' => ['type' => 'NUMBER'],
                        'food' => ['type' => 'NUMBER'],
                        'activities' => ['type' => 'NUMBER'],
                        'local_transport' => ['type' => 'NUMBER'],
                        'intercity_transport' => ['type' => 'NUMBER'],
                        'flights' => ['type' => 'NUMBER'],
                        'misc' => ['type' => 'NUMBER'],
                        'total' => ['type' => 'NUMBER'],
                    ],
                    'required' => ['total'],
                ],

                'fit' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'within_budget' => ['type' => 'BOOLEAN'],
                        'total' => ['type' => 'NUMBER'],
                        'target' => ['type' => 'NUMBER'],
                        'note' => ['type' => 'STRING'],
                    ],
                ],

                'tips' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],

                'packing' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'title' => ['type' => 'STRING'],
                            'items' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        ],
                        'required' => ['title', 'items'],
                    ],
                ],

                'culture' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'place' => ['type' => 'STRING'],
                            'dos' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'donts' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        ],
                        'required' => ['place'],
                    ],
                ],

                'countdown' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'when' => ['type' => 'STRING'],
                            'tasks' => ['type' => 'STRING'],
                        ],
                        'required' => ['when', 'tasks'],
                    ],
                ],
            ],
            'required' => ['title', 'route', 'days', 'budget'],
        ];
    }
}
