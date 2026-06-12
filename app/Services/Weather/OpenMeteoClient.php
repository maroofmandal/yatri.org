<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMeteoClient
{
    protected const BASE = 'https://api.open-meteo.com/v1/forecast';

    public const WMO = [
        0 => ['label' => 'Clear sky', 'icon' => 'sunny', 'class' => 'clear'],
        1 => ['label' => 'Mainly clear', 'icon' => 'clear_day', 'class' => 'clear'],
        2 => ['label' => 'Partly cloudy', 'icon' => 'partly_cloudy_day', 'class' => 'cloudy'],
        3 => ['label' => 'Overcast', 'icon' => 'cloud', 'class' => 'cloudy'],
        45 => ['label' => 'Foggy', 'icon' => 'foggy', 'class' => 'fog'],
        48 => ['label' => 'Depositing rime fog', 'icon' => 'foggy', 'class' => 'fog'],
        51 => ['label' => 'Light drizzle', 'icon' => 'rainy_light', 'class' => 'drizzle'],
        53 => ['label' => 'Moderate drizzle', 'icon' => 'rainy_light', 'class' => 'drizzle'],
        55 => ['label' => 'Dense drizzle', 'icon' => 'rainy', 'class' => 'drizzle'],
        56 => ['label' => 'Light freezing drizzle', 'icon' => 'rainy_snow', 'class' => 'drizzle'],
        57 => ['label' => 'Dense freezing drizzle', 'icon' => 'rainy_snow', 'class' => 'drizzle'],
        61 => ['label' => 'Slight rain', 'icon' => 'rainy', 'class' => 'rain'],
        63 => ['label' => 'Moderate rain', 'icon' => 'rainy', 'class' => 'rain'],
        65 => ['label' => 'Heavy rain', 'icon' => 'rainy_heavy', 'class' => 'rain'],
        66 => ['label' => 'Light freezing rain', 'icon' => 'rainy_snow', 'class' => 'rain'],
        67 => ['label' => 'Heavy freezing rain', 'icon' => 'rainy_snow', 'class' => 'rain'],
        71 => ['label' => 'Slight snow', 'icon' => 'weather_snowy', 'class' => 'snow'],
        73 => ['label' => 'Moderate snow', 'icon' => 'weather_snowy', 'class' => 'snow'],
        75 => ['label' => 'Heavy snow', 'icon' => 'weather_snowy', 'class' => 'snow'],
        77 => ['label' => 'Snow grains', 'icon' => 'grain', 'class' => 'snow'],
        80 => ['label' => 'Slight rain showers', 'icon' => 'rainy', 'class' => 'rain'],
        81 => ['label' => 'Moderate rain showers', 'icon' => 'rainy', 'class' => 'rain'],
        82 => ['label' => 'Violent rain showers', 'icon' => 'rainy_heavy', 'class' => 'rain'],
        85 => ['label' => 'Slight snow showers', 'icon' => 'weather_snowy', 'class' => 'snow'],
        86 => ['label' => 'Heavy snow showers', 'icon' => 'weather_snowy', 'class' => 'snow'],
        95 => ['label' => 'Thunderstorm', 'icon' => 'thunderstorm', 'class' => 'storm'],
        96 => ['label' => 'Thunderstorm with slight hail', 'icon' => 'thunderstorm', 'class' => 'storm'],
        99 => ['label' => 'Thunderstorm with heavy hail', 'icon' => 'thunderstorm', 'class' => 'storm'],
    ];

    public function dailyForecast(float $lat, float $lng, string $startDate, string $endDate): ?array
    {
        $cacheKey = 'weather:openmeteo:'.md5(round($lat, 4).':'.round($lng, 4).':'.$startDate.':'.$endDate);

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($lat, $lng, $startDate, $endDate) {
            try {
                $response = Http::timeout(15)
                    ->acceptJson()
                    ->get(self::BASE, [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,uv_index_max',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'timezone' => 'auto',
                    ]);

                if (! $response->successful()) {
                    Log::warning('Open-Meteo daily forecast failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'lat' => $lat,
                        'lng' => $lng,
                    ]);

                    return null;
                }

                return $this->normalize($response->json(), $startDate, $endDate);
            } catch (\Throwable $e) {
                Log::warning('Open-Meteo daily forecast exception', [
                    'message' => $e->getMessage(),
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

                return null;
            }
        });
    }

    protected function normalize(array $json, string $startDate, string $endDate): array
    {
        $daily = $json['daily'] ?? [];

        $times = $daily['time'] ?? [];
        $codes = $daily['weather_code'] ?? [];
        $maxT = $daily['temperature_2m_max'] ?? [];
        $minT = $daily['temperature_2m_min'] ?? [];
        $precip = $daily['precipitation_sum'] ?? [];
        $precipProb = $daily['precipitation_probability_max'] ?? [];
        $wind = $daily['wind_speed_10m_max'] ?? [];
        $uv = $daily['uv_index_max'] ?? [];
        $utcOffset = $json['utc_offset_seconds'] ?? 0;

        $results = [];

        foreach ($times as $i => $date) {
            if ($date < $startDate || $date > $endDate) {
                continue;
            }

            $code = (int) ($codes[$i] ?? 0);
            $wmo = self::WMO[$code] ?? self::WMO[0];

            $entry = [
                'date' => $date,
                'source' => 'open_meteo',
                'status' => 'live',
                'weather_code' => $code,
                'summary' => $wmo['label'],
                'icon' => $wmo['icon'],
                'icon_class' => $wmo['class'],
                'temperature_min_c' => isset($minT[$i]) && $minT[$i] !== null ? round((float) $minT[$i], 1) : null,
                'temperature_max_c' => isset($maxT[$i]) && $maxT[$i] !== null ? round((float) $maxT[$i], 1) : null,
                'precipitation_sum' => isset($precip[$i]) && $precip[$i] !== null ? round((float) $precip[$i], 1) : null,
                'precipitation_probability' => isset($precipProb[$i]) && $precipProb[$i] !== null ? (int) round($precipProb[$i]) : null,
                'wind_speed' => isset($wind[$i]) && $wind[$i] !== null ? round((float) $wind[$i], 1) : null,
                'uv_index' => isset($uv[$i]) && $uv[$i] !== null ? round((float) $uv[$i], 1) : null,
            ];

            $results[$date] = $entry;
        }

        return $results;
    }
}
