<?php

namespace Tests\Unit;

use App\Services\Weather\OpenMeteoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_forecast_normalizes_open_meteo_response(): void
    {
        Cache::flush();

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'latitude' => 28.61,
                'longitude' => 77.21,
                'utc_offset_seconds' => 19800,
                'daily' => [
                    'time' => ['2026-06-15', '2026-06-16'],
                    'weather_code' => [2, 61],
                    'temperature_2m_max' => [31.8, 28.5],
                    'temperature_2m_min' => [22.4, 20.1],
                    'precipitation_sum' => [0.0, 5.2],
                    'precipitation_probability_max' => [10, 80],
                    'wind_speed_10m_max' => [12.5, 18.3],
                    'uv_index_max' => [8, 3],
                ],
            ]),
        ]);

        $forecast = (new OpenMeteoClient)->dailyForecast(28.6139, 77.2090, '2026-06-15', '2026-06-16');

        $this->assertNotNull($forecast);
        $this->assertCount(2, $forecast);

        $day1 = $forecast['2026-06-15'];
        $this->assertSame('2026-06-15', $day1['date']);
        $this->assertSame('open_meteo', $day1['source']);
        $this->assertSame('live', $day1['status']);
        $this->assertSame(2, $day1['weather_code']);
        $this->assertSame('Partly cloudy', $day1['summary']);
        $this->assertSame('partly_cloudy_day', $day1['icon']);
        $this->assertSame('cloudy', $day1['icon_class']);
        $this->assertSame(22.4, $day1['temperature_min_c']);
        $this->assertSame(31.8, $day1['temperature_max_c']);
        $this->assertSame(10, $day1['precipitation_probability']);
        $this->assertSame(12.5, $day1['wind_speed']);

        $day2 = $forecast['2026-06-16'];
        $this->assertSame(61, $day2['weather_code']);
        $this->assertSame('Slight rain', $day2['summary']);
        $this->assertSame('rainy', $day2['icon']);
        $this->assertSame('rain', $day2['icon_class']);
        $this->assertSame(5.2, $day2['precipitation_sum']);
        $this->assertSame(80, $day2['precipitation_probability']);
    }

    public function test_daily_forecast_returns_null_on_failure(): void
    {
        Cache::flush();

        Http::fake([
            'api.open-meteo.com/*' => Http::response(null, 500),
        ]);

        $forecast = (new OpenMeteoClient)->dailyForecast(28.6139, 77.2090, '2026-06-15', '2026-06-16');

        $this->assertNull($forecast);
    }
}
