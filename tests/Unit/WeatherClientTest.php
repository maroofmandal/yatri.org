<?php

namespace Tests\Unit;

use App\Services\Google\WeatherClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_forecast_normalizes_google_response(): void
    {
        Cache::flush();

        Http::fake([
            'weather.googleapis.com/*' => Http::response([
                'forecastDays' => [[
                    'displayDate' => ['year' => 2026, 'month' => 6, 'day' => 4],
                    'minTemperature' => ['degrees' => 22.4],
                    'maxTemperature' => ['degrees' => 31.8],
                    'daytimeForecast' => [
                        'weatherCondition' => [
                            'description' => ['text' => 'Partly cloudy'],
                            'iconBaseUri' => 'https://maps.gstatic.com/weather/v1/partly_cloudy',
                        ],
                        'precipitation' => ['probability' => ['percent' => 40]],
                        'relativeHumidity' => 67,
                        'uvIndex' => 8,
                    ],
                ]],
            ]),
        ]);

        $forecast = (new WeatherClient('test-key'))->dailyForecast(28.6139, 77.2090, 1);

        $this->assertSame('2026-06-04', $forecast[0]['date']);
        $this->assertSame('google_weather', $forecast[0]['source']);
        $this->assertSame('live_forecast', $forecast[0]['status']);
        $this->assertSame('Partly cloudy', $forecast[0]['summary']);
        $this->assertSame(22.4, $forecast[0]['temperature_min_c']);
        $this->assertSame(31.8, $forecast[0]['temperature_max_c']);
        $this->assertSame(40, $forecast[0]['precipitation_probability']);
    }

    public function test_daily_forecast_returns_null_without_key(): void
    {
        config(['gemini.maps_key' => null]);
        Http::fake();

        $forecast = (new WeatherClient)->dailyForecast(28.6139, 77.2090, 1);

        $this->assertNull($forecast);
        Http::assertNothingSent();
    }
}
