<?php

namespace App\Services\Planner;

use App\Services\Google\PlacesClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Post-generation enrichment: looks up real Google Places data
 * (ratings, reviews, photos) for hotels and activities in the plan.
 *
 * Designed to run AFTER Gemini returns valid JSON and BEFORE saving to DB.
 * Fails gracefully — any individual lookup error is logged and skipped.
 */
class PlacesEnricher
{
    /** Maximum number of Places API lookups per enrichment run. */
    protected const MAX_LOOKUPS = 15;

    /** Activity keywords that indicate generic/non-place items to skip. */
    protected const SKIP_KEYWORDS = [
        'lunch', 'dinner', 'breakfast', 'walk', 'relax', 'rest',
        'free time', 'check-in', 'check-out', 'checkout', 'checkin',
        'departure', 'arrival', 'travel to', 'transit', 'flight',
        'pack', 'sleep', 'nap',
    ];

    public function __construct(protected PlacesClient $client) {}

    /**
     * Enrich the plan array with Google Places data (ratings, reviews, photos).
     *
     * @param  array  $plan  Decoded plan JSON array from Gemini
     * @return array The enriched plan (or unchanged if Places is not configured)
     */
    public function enrich(array $plan): array
    {
        if (! $this->client->isConfigured()) {
            return $plan;
        }

        // Collect place queries: hotels first, then day items.
        $queries = $this->collectQueries($plan);

        // Limit total lookups.
        $queries = array_slice($queries, 0, self::MAX_LOOKUPS);

        if (empty($queries)) {
            return $plan;
        }

        // Perform lookups and build the places array.
        $places = [];
        $keyMap = []; // query => normalized key

        foreach ($queries as $entry) {
            $query = $entry['query'];
            $key = $this->normalizeKey($query);

            if (isset($places[$key])) {
                $keyMap[$query] = $key;

                continue; // Already looked up
            }

            $placeData = $this->lookupPlace($query);

            if ($placeData) {
                $places[$key] = $placeData;
            }

            $keyMap[$query] = $key;
        }

        // Store enriched data in the plan.
        $plan['places'] = $places;

        // Annotate source items with place_key references.
        $plan = $this->annotateHotels($plan, $keyMap, $places);
        $plan = $this->annotateDayItems($plan, $keyMap, $places);

        return $plan;
    }

    /**
     * Collect place queries from hotels and day items.
     *
     * @return array<int, array{query: string, source: string}>
     */
    protected function collectQueries(array $plan): array
    {
        $queries = [];

        // Hotels (prioritized first).
        foreach ($plan['hotels'] ?? [] as $hotel) {
            $query = $hotel['place_query']
                ?? ($hotel['name'].' '.($hotel['city'] ?? ''));
            $query = trim($query);

            if ($query) {
                $queries[] = ['query' => $query, 'source' => 'hotel'];
            }
        }

        // Day items (only named places/attractions, not generic activities).
        foreach ($plan['days'] ?? [] as $day) {
            foreach ($day['items'] ?? [] as $item) {
                if (empty($item['place_query'])) {
                    continue;
                }

                if ($this->isGenericActivity($item['place_query'])) {
                    continue;
                }

                $queries[] = ['query' => trim($item['place_query']), 'source' => 'day_item'];
            }
        }

        return $queries;
    }

    /**
     * Check if a query looks like a generic activity rather than a named place.
     */
    protected function isGenericActivity(string $query): bool
    {
        $lower = Str::lower($query);

        foreach (self::SKIP_KEYWORDS as $keyword) {
            if (Str::contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Perform the Places API lookup for a single query.
     *
     * @return array|null Normalized place data or null on failure
     */
    protected function lookupPlace(string $query): ?array
    {
        try {
            $result = $this->client->searchPlace($query);

            if (! $result) {
                return null;
            }

            $placeId = $result['id'] ?? null;
            $rating = $result['rating'] ?? null;
            $reviewsCount = $result['userRatingCount'] ?? null;
            $reviews = $result['reviews'] ?? null;
            $photos = $result['photos'] ?? null;
            $mapsUrl = $result['googleMapsUri'] ?? null;
            $name = $result['displayName']['text'] ?? $query;
            $businessStatus = $result['businessStatus'] ?? null;
            $hours = $result['regularOpeningHours']['weekdayDescriptions'] ?? null;
            $website = $result['websiteUri'] ?? null;
            $priceLevel = $result['priceLevel'] ?? null;
            $priceRange = $result['priceRange'] ?? null;

            // Fetch details when search result lacks richer business fields.
            if ($placeId && (empty($reviews) || empty($hours) || empty($website) || empty($priceLevel))) {
                $details = $this->client->getPlaceDetails($placeId);

                if ($details) {
                    $rating = $rating ?? ($details['rating'] ?? null);
                    $reviewsCount = $reviewsCount ?? ($details['userRatingCount'] ?? null);
                    $reviews = $details['reviews'] ?? null;
                    $photos = $photos ?? ($details['photos'] ?? null);
                    $mapsUrl = $mapsUrl ?? ($details['googleMapsUri'] ?? null);
                    $businessStatus = $businessStatus ?? ($details['businessStatus'] ?? null);
                    $hours = $hours ?? ($details['regularOpeningHours']['weekdayDescriptions'] ?? null);
                    $website = $website ?? ($details['websiteUri'] ?? null);
                    $priceLevel = $priceLevel ?? ($details['priceLevel'] ?? null);
                    $priceRange = $priceRange ?? ($details['priceRange'] ?? null);
                }
            }

            // Normalize reviews to a consistent format.
            $normalizedReviews = [];
            if (is_array($reviews)) {
                foreach (array_slice($reviews, 0, 5) as $review) {
                    $normalizedReviews[] = [
                        'author' => $review['authorName']
                            ?? $review['authorAttribution']['displayName']
                            ?? '',
                        'rating' => $review['rating'] ?? null,
                        'text' => $review['text']['text'] ?? $review['text'] ?? '',
                        'time' => $review['relativePublishTime']
                            ?? $review['relativePublishTimeDescription']
                            ?? '',
                    ];
                }
            }

            // Normalize photos to resource name strings.
            $normalizedPhotos = [];
            if (is_array($photos)) {
                foreach (array_slice($photos, 0, 5) as $photo) {
                    $photoName = $photo['name'] ?? null;
                    if ($photoName) {
                        $normalizedPhotos[] = $photoName;
                    }
                }
            }

            return [
                'name' => $name,
                'rating' => $rating,
                'reviews_count' => $reviewsCount,
                'reviews' => $normalizedReviews,
                'photos' => $normalizedPhotos,
                'maps_url' => $mapsUrl,
                'business_status' => $businessStatus,
                'hours' => is_array($hours) ? array_slice($hours, 0, 7) : [],
                'website' => $website,
                'price_level' => $priceLevel,
                'price_range' => $priceRange,
            ];
        } catch (\Throwable $e) {
            Log::warning('PlacesEnricher: lookup failed', [
                'query' => $query,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Annotate hotels with place_key references.
     */
    protected function annotateHotels(array $plan, array $keyMap, array $places): array
    {
        foreach ($plan['hotels'] ?? [] as $i => $hotel) {
            $query = $hotel['place_query']
                ?? ($hotel['name'].' '.($hotel['city'] ?? ''));
            $query = trim($query);
            $key = $keyMap[$query] ?? $this->normalizeKey($query);

            if (isset($places[$key])) {
                $plan['hotels'][$i]['place_key'] = $key;
            }
        }

        return $plan;
    }

    /**
     * Annotate day items with place_key references.
     */
    protected function annotateDayItems(array $plan, array $keyMap, array $places): array
    {
        foreach ($plan['days'] ?? [] as $d => $day) {
            foreach ($day['items'] ?? [] as $i => $item) {
                if (empty($item['place_query'])) {
                    continue;
                }

                $query = trim($item['place_query']);
                $key = $keyMap[$query] ?? $this->normalizeKey($query);

                if (isset($places[$key])) {
                    $plan['days'][$d]['items'][$i]['place_key'] = $key;
                }
            }
        }

        return $plan;
    }

    /**
     * Normalize a place query into a URL-friendly key.
     *
     * Lowercases, strips diacritics, replaces spaces/special chars with hyphens.
     */
    public function normalizeKey(string $query): string
    {
        // Transliterate to ASCII (removes diacritics).
        $key = Str::ascii($query);

        // Lowercase.
        $key = Str::lower($key);

        // Replace any non-alphanumeric chars with hyphens.
        $key = preg_replace('/[^a-z0-9]+/', '-', $key);

        // Trim leading/trailing hyphens.
        return trim($key, '-');
    }
}
