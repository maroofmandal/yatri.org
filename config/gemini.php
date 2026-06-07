<?php

return [
    // Default to the free-tier flash model that supports Google Search + Maps grounding.
    'key'   => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),

    'grounding_search' => env('GEMINI_GROUNDING_SEARCH', true),
    'grounding_maps'   => env('GEMINI_GROUNDING_MAPS', true),

    'maps_key'   => env('GOOGLE_MAPS_API_KEY'),
    'places_key' => env('GOOGLE_PLACES_API_KEY'),

    'google_places_api_key' => env('GOOGLE_PLACES_API_KEY'),

    'base' => env('GEMINI_BASE', 'https://generativelanguage.googleapis.com/v1beta'),
];
