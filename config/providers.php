<?php

return [
    // Which LLM powers planning/chat: gemini | groq | openrouter
    'llm' => env('LLM_PROVIDER', 'gemini'),

    'groq' => [
        'key'   => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'base'  => 'https://api.groq.com/openai/v1',
    ],
    'openrouter' => [
        'key'   => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
        'base'  => 'https://openrouter.ai/api/v1',
    ],

    // Live-data grounding for non-Gemini LLMs: none | tavily | brave
    'search' => env('SEARCH_PROVIDER', 'none'),
    'tavily' => ['key' => env('TAVILY_API_KEY')],
    'brave'  => ['key' => env('BRAVE_API_KEY')],

    // Geocoding / autocomplete: photon (keyless) | geoapify | nominatim | google
    'geocode'  => env('GEOCODE_PROVIDER', 'photon'),
    'geoapify' => ['key' => env('GEOAPIFY_API_KEY')],
];
