<?php

namespace App\Services\Google;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * REST client for Google Maps Platform Weather API.
 *
 * Forecast endpoint supports up to 10 days from today. Longer-range trip dates
 * must be represented as seasonal estimates elsewhere, not live forecasts.
 */
class WeatherClient
{
    protected ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: (Setting::get('google_maps_api_key') ?: config('gemini.maps_key'));
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function dailyForecast(float $lat, float $lng, int $days = 10): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $days = max(1, min(10, $days));
        $cacheKey = 'weather:daily:'.md5(round($lat, 4).':'.round($lng, 4).':'.$days);

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($lat, $lng, $days) {
            try {
                $response = Http::timeout(15)
                    ->acceptJson()
                    ->get('https://weather.googleapis.com/v1/forecast/days:lookup', [
                        'key' => $this->apiKey,
                        'location.latitude' => $lat,
                        'location.longitude' => $lng,
                        'days' => $days,
                        'unitsSystem' => 'METRIC',
                    ]);

                if (! $response->successful()) {
                    Log::warning('Google Weather daily forecast failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'lat' => $lat,
                        'lng' => $lng,
                    ]);

                    return null;
                }

                return $this->normalizeForecastDays($response->json('forecastDays', []));
            } catch (\Throwable $e) {
                Log::warning('Google Weather daily forecast exception', [
                    'message' => $e->getMessage(),
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

                return null;
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $forecastDays
     * @return array<int, array<string, mixed>>
     */
    public function normalizeForecastDays(array $forecastDays): array
    {
        return collect($forecastDays)->map(function (array $day) {
            $displayDate = $day['displayDate'] ?? [];
            $date = null;
            if (($displayDate['year'] ?? null) && ($displayDate['month'] ?? null) && ($displayDate['day'] ?? null)) {
                $date = sprintf('%04d-%02d-%02d', $displayDate['year'], $displayDate['month'], $displayDate['day']);
            }

            $daytime = $day['daytimeForecast'] ?? [];
            $condition = $daytime['weatherCondition'] ?? [];

            return [
                'date' => $date,
                'source' => 'google_weather',
                'status' => 'live_forecast',
                'summary' => data_get($condition, 'description.text'),
                'icon' => $condition['iconBaseUri'] ?? null,
                'temperature_min_c' => $this->degrees($day['minTemperature'] ?? null),
                'temperature_max_c' => $this->degrees($day['maxTemperature'] ?? null),
                'precipitation_probability' => data_get($daytime, 'precipitation.probability.percent'),
                'humidity' => data_get($daytime, 'relativeHumidity'),
                'uv_index' => data_get($daytime, 'uvIndex'),
            ];
        })->filter(fn (array $day) => ! empty($day['date']))->values()->all();
    }

    protected function degrees(mixed $temperature): ?float
    {
        if (! is_array($temperature)) {
            return null;
        }

        $degrees = $temperature['degrees'] ?? null;

        return is_numeric($degrees) ? round((float) $degrees, 1) : null;
    }
}
