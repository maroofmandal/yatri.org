<?php

namespace App\Services\Google;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * REST client for the Google Places API (New).
 *
 * Provides place search, detail lookups, and photo URL construction.
 * API key is resolved from admin DB settings with env/config fallback.
 * All responses are cached for 24 hours to reduce API usage.
 */
class PlacesClient
{
    protected ?string $apiKey;

    /**
     * Resolve the API key from Settings DB, falling back to config.
     */
    public function __construct()
    {
        $this->apiKey = Setting::get('google_places_api_key')
            ?: (Setting::get('google_maps_api_key') ?: (config('gemini.google_places_api_key') ?: config('gemini.maps_key')));
    }

    /**
     * Check whether the Google Places API key is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Search for a place by text query using the Places API (New) Text Search.
     *
     * @param  string  $query  The search query (e.g. "Eiffel Tower Paris")
     * @param  string|null  $location  Optional lat,lng string for location bias
     * @return array|null First matching place result or null on failure
     */
    public function searchPlace(string $query, ?string $location = null): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheKey = 'places:search:'.md5($query);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query, $location) {
            try {
                $body = [
                    'textQuery' => $query,
                    'maxResultCount' => 1,
                ];

                if ($location) {
                    $parts = explode(',', $location);
                    if (count($parts) === 2) {
                        $body['locationBias'] = [
                            'circle' => [
                                'center' => [
                                    'latitude' => (float) trim($parts[0]),
                                    'longitude' => (float) trim($parts[1]),
                                ],
                                'radius' => 5000.0,
                            ],
                        ];
                    }
                }

                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-Goog-Api-Key' => $this->apiKey,
                        'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.rating,places.userRatingCount,places.photos,places.reviews,places.googleMapsUri,places.businessStatus,places.regularOpeningHours,places.websiteUri,places.priceLevel,places.priceRange',
                    ])
                    ->acceptJson()
                    ->post('https://places.googleapis.com/v1/places:searchText', $body);

                if (! $response->successful()) {
                    Log::warning('Google Places searchText failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'query' => $query,
                    ]);

                    return null;
                }

                $places = $response->json('places', []);

                return $places[0] ?? null;
            } catch (\Throwable $e) {
                Log::warning('Google Places searchText exception', [
                    'message' => $e->getMessage(),
                    'query' => $query,
                ]);

                return null;
            }
        });
    }

    /**
     * Get detailed information for a specific place by its ID.
     *
     * Returns place data including up to 5 reviews (author name, rating, text,
     * relative publish time) and up to 3 photo resource names.
     *
     * @param  string  $placeId  The Google Places ID
     * @return array|null Place details array or null on failure
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheKey = 'places:details:'.$placeId;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($placeId) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-Goog-Api-Key' => $this->apiKey,
                        'X-Goog-FieldMask' => 'displayName,formattedAddress,rating,userRatingCount,reviews,photos,googleMapsUri,businessStatus,regularOpeningHours,websiteUri,priceLevel,priceRange',
                    ])
                    ->acceptJson()
                    ->get("https://places.googleapis.com/v1/places/{$placeId}");

                if (! $response->successful()) {
                    Log::warning('Google Places getPlaceDetails failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'placeId' => $placeId,
                    ]);

                    return null;
                }

                $data = $response->json();

                // Limit reviews to 5 with relevant fields
                if (isset($data['reviews']) && is_array($data['reviews'])) {
                    $data['reviews'] = collect($data['reviews'])->take(5)->map(fn ($review) => [
                        'authorName' => $review['authorAttribution']['displayName'] ?? '',
                        'rating' => $review['rating'] ?? null,
                        'text' => $review['text']['text'] ?? '',
                        'relativePublishTime' => $review['relativePublishTimeDescription'] ?? '',
                    ])->all();
                }

                // Limit photos to 3, keeping photo resource name
                if (isset($data['photos']) && is_array($data['photos'])) {
                    $data['photos'] = collect($data['photos'])->take(3)->map(fn ($photo) => [
                        'name' => $photo['name'] ?? '',
                    ])->all();
                }

                return $data;
            } catch (\Throwable $e) {
                Log::warning('Google Places getPlaceDetails exception', [
                    'message' => $e->getMessage(),
                    'placeId' => $placeId,
                ]);

                return null;
            }
        });
    }

    /**
     * Build the photo URL for a Places photo resource.
     *
     * This constructs the URL directly — the API will redirect to the actual image.
     *
     * @param  string  $photoName  The photo resource name (e.g. "places/xxx/photos/yyy")
     * @param  int  $maxWidth  Maximum width in pixels (default 400)
     * @return string The photo media URL
     */
    public function getPhotoUrl(string $photoName, int $maxWidth = 400): string
    {
        return "https://places.googleapis.com/v1/{$photoName}/media?maxWidthPx={$maxWidth}&key={$this->apiKey}";
    }
}
