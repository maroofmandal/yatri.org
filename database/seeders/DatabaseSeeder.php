<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin + demo users ──────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@yatri.org'],
            [
                'name'     => 'Yatri Admin',
                'password' => Hash::make('yatri-admin-2026'),
                'role'     => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'traveler@yatri.org'],
            [
                'name'     => 'Demo Traveler',
                'password' => Hash::make('password'),
                'role'     => 'user',
            ]
        );

        // ── Default settings ────────────────────────────────────
        Setting::put('site_name', 'Yatri', 'brand', 'string');
        Setting::put('default_currency', 'USD', 'brand', 'string');
        Setting::put('fx_rate_inr', '85', 'brand', 'string');
        Setting::put('gemini_model', config('gemini.model'), 'ai', 'string');
        Setting::put('gemini_grounding_search', true, 'ai', 'bool');
        Setting::put('gemini_grounding_maps', true, 'ai', 'bool');

        // Provider selection — free defaults (Photon geocode is keyless).
        Setting::put('llm_provider', 'gemini', 'providers', 'string');
        Setting::put('search_provider', 'none', 'providers', 'string');
        Setting::put('geocode_provider', 'photon', 'providers', 'string');

        // ── Popular destinations ────────────────────────────────
        $cities = [
            ['Tokyo', 'Japan', 35.6762, 139.6503, 130, 98],
            ['Kyoto', 'Japan', 35.0116, 135.7681, 120, 80],
            ['Seoul', 'South Korea', 37.5665, 126.9780, 95, 78],
            ['Bangkok', 'Thailand', 13.7563, 100.5018, 55, 95],
            ['Bali', 'Indonesia', -8.4095, 115.1889, 60, 96],
            ['Singapore', 'Singapore', 1.3521, 103.8198, 150, 82],
            ['Dubai', 'UAE', 25.2048, 55.2708, 180, 88],
            ['Paris', 'France', 48.8566, 2.3522, 170, 99],
            ['Rome', 'Italy', 41.9028, 12.4964, 140, 90],
            ['Barcelona', 'Spain', 41.3851, 2.1734, 130, 86],
            ['New York', 'USA', 40.7128, -74.0060, 220, 97],
            ['Goa', 'India', 15.2993, 74.1240, 40, 84],
        ];

        foreach ($cities as [$name, $country, $lat, $lng, $cost, $pop]) {
            Destination::updateOrCreate(
                ['name' => $name],
                [
                    'country'        => $country,
                    'lat'            => $lat,
                    'lng'            => $lng,
                    'avg_daily_cost' => $cost,
                    'popularity'     => $pop,
                    'is_active'      => true,
                    'summary'        => "$name, $country — a top traveler pick.",
                ]
            );
        }
    }
}
