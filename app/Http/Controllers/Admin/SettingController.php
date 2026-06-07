<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = [
            'ai'        => Setting::group('ai'),
            'brand'     => Setting::group('brand'),
            'booking'   => Setting::group('booking'),
            'providers' => Setting::group('providers'),
        ];

        $settings['providers'] += [
            'llm_provider'     => $settings['providers']['llm_provider'] ?? config('providers.llm'),
            'groq_model'       => $settings['providers']['groq_model'] ?? config('providers.groq.model'),
            'openrouter_model' => $settings['providers']['openrouter_model'] ?? config('providers.openrouter.model'),
            'search_provider'  => $settings['providers']['search_provider'] ?? config('providers.search'),
            'geocode_provider' => $settings['providers']['geocode_provider'] ?? config('providers.geocode'),
        ];

        // Fallbacks from config/env so the form is never blank on first load.
        $settings['ai'] += [
            'gemini_model'            => $settings['ai']['gemini_model'] ?? config('gemini.model'),
            'gemini_grounding_search' => $settings['ai']['gemini_grounding_search'] ?? config('gemini.grounding_search'),
            'gemini_grounding_maps'   => $settings['ai']['gemini_grounding_maps'] ?? config('gemini.grounding_maps'),
        ];

        $hasEnvKey = ! empty(config('gemini.key'));

        return view('admin.settings', compact('settings', 'hasEnvKey'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'gemini_api_key'          => ['nullable', 'string', 'max:200'],
            'gemini_model'            => ['required', 'string', 'max:80'],
            'gemini_grounding_search' => ['nullable', 'boolean'],
            'gemini_grounding_maps'   => ['nullable', 'boolean'],
            'google_maps_api_key'     => ['nullable', 'string', 'max:200'],
            'google_places_api_key'   => ['nullable', 'string', 'max:200'],

            'site_name'               => ['required', 'string', 'max:80'],
            'default_currency'        => ['required', 'string', 'size:3'],
            'fx_rate_inr'             => ['required', 'numeric', 'min:1'],

            'affiliate_flights'       => ['nullable', 'string', 'max:200'],
            'affiliate_hotels'        => ['nullable', 'string', 'max:200'],

            // Free-provider selection
            'llm_provider'            => ['required', 'in:gemini,groq,openrouter'],
            'groq_api_key'            => ['nullable', 'string', 'max:200'],
            'groq_model'              => ['nullable', 'string', 'max:120'],
            'openrouter_api_key'      => ['nullable', 'string', 'max:200'],
            'openrouter_model'        => ['nullable', 'string', 'max:120'],
            'search_provider'         => ['required', 'in:none,tavily,brave'],
            'tavily_api_key'          => ['nullable', 'string', 'max:200'],
            'brave_api_key'           => ['nullable', 'string', 'max:200'],
            'geocode_provider'        => ['required', 'in:photon,geoapify,nominatim,google'],
            'geoapify_api_key'        => ['nullable', 'string', 'max:200'],
        ]);

        // AI — only overwrite the key when a new value is typed (keeps the stored secret).
        if (! empty($data['gemini_api_key'])) {
            Setting::put('gemini_api_key', $data['gemini_api_key'], 'ai', 'secret');
        }
        if (! empty($data['google_maps_api_key'])) {
            Setting::put('google_maps_api_key', $data['google_maps_api_key'], 'ai', 'secret');
        }
        if (! empty($data['google_places_api_key'])) {
            Setting::put('google_places_api_key', $data['google_places_api_key'], 'ai', 'secret');
        }
        Setting::put('gemini_model', $data['gemini_model'], 'ai', 'string');
        Setting::put('gemini_grounding_search', $request->boolean('gemini_grounding_search'), 'ai', 'bool');
        Setting::put('gemini_grounding_maps', $request->boolean('gemini_grounding_maps'), 'ai', 'bool');

        // Brand
        Setting::put('site_name', $data['site_name'], 'brand', 'string');
        Setting::put('default_currency', strtoupper($data['default_currency']), 'brand', 'string');
        Setting::put('fx_rate_inr', $data['fx_rate_inr'], 'brand', 'string');

        // Booking / affiliate
        Setting::put('affiliate_flights', $data['affiliate_flights'] ?? '', 'booking', 'string');
        Setting::put('affiliate_hotels', $data['affiliate_hotels'] ?? '', 'booking', 'string');

        // Provider selection
        Setting::put('llm_provider', $data['llm_provider'], 'providers', 'string');
        Setting::put('search_provider', $data['search_provider'], 'providers', 'string');
        Setting::put('geocode_provider', $data['geocode_provider'], 'providers', 'string');
        if (! empty($data['groq_model'])) {
            Setting::put('groq_model', $data['groq_model'], 'providers', 'string');
        }
        if (! empty($data['openrouter_model'])) {
            Setting::put('openrouter_model', $data['openrouter_model'], 'providers', 'string');
        }
        foreach (['groq_api_key', 'openrouter_api_key', 'tavily_api_key', 'brave_api_key', 'geoapify_api_key'] as $secret) {
            if (! empty($data[$secret])) {
                Setting::put($secret, $data[$secret], 'providers', 'secret');
            }
        }

        return back()->with('ok', 'Settings saved.');
    }
}
