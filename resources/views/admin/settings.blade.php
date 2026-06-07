@extends('admin.layout')
@section('title','Settings')

@section('admin')
<div class="adm-h"><h1>Settings</h1></div>

<form method="POST" action="{{ route('admin.settings.update') }}">
  @csrf @method('PUT')

  <div class="card mb">
    <h3>AI — Gemini</h3>
    @php $keySet = $hasEnvKey || !empty($settings['ai']['gemini_api_key']); @endphp
    @if(!$keySet)<div class="flash flash-err">No key set — planner runs in <strong>sample mode</strong>.</div>@endif
    <div class="field">
      <label>Gemini API key @if($keySet)<span class="badge ok">set</span>@endif</label>
      <input type="password" name="gemini_api_key" placeholder="{{ !empty($settings['ai']['gemini_api_key']) ? '•••••••• stored (leave blank to keep)' : 'Paste your AI Studio key' }}">
      <div class="hint">Free key at <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">aistudio.google.com/apikey</a>. Stored in DB, overrides env.</div>
    </div>
    <div class="row row-2">
      <div class="field"><label>Model</label><input type="text" name="gemini_model" value="{{ $settings['ai']['gemini_model'] ?? config('gemini.model') }}"></div>
      <div class="field">
        <label>Google Maps API key (optional) @if(!empty($settings['ai']['google_maps_api_key']))<span class="badge ok">set</span>@endif</label>
        <input type="password" name="google_maps_api_key" placeholder="{{ !empty($settings['ai']['google_maps_api_key']) ? '•••••••• stored' : 'optional — weather + richer place data' }}">
        <div class="hint">Enable Maps JavaScript API, Places API, and Weather API in Google Cloud for autocomplete, business data, and 10-day forecasts.</div>
      </div>
    </div>
    <div class="field">
      <label>Google Places API Key @if(!empty($settings['ai']['google_places_api_key']))<span class="badge ok">set</span>@endif</label>
      <input type="password" name="google_places_api_key" placeholder="{{ !empty($settings['ai']['google_places_api_key']) ? '•••••••• stored (leave blank to keep)' : 'Paste your Google Places API key' }}">
      <div class="hint">Optional separate Places key. If blank, Yatri uses the Google Maps API key above for Places lookups.</div>
    </div>
    <label style="font-weight:500"><input type="checkbox" name="gemini_grounding_search" value="1" style="width:auto;margin-right:6px" {{ ($settings['ai']['gemini_grounding_search'] ?? true) ? 'checked' : '' }}>Ground with Google Search</label><br>
    <label style="font-weight:500;display:inline-block;margin-top:8px"><input type="checkbox" name="gemini_grounding_maps" value="1" style="width:auto;margin-right:6px" {{ ($settings['ai']['gemini_grounding_maps'] ?? true) ? 'checked' : '' }}>Ground with Google Maps</label>
  </div>

  <div class="card mb">
    <h3>Providers <span class="muted" style="font-size:13px;font-weight:500">— free alternatives, no billing</span></h3>
    @php $pv = $settings['providers']; @endphp

    <div class="row row-2">
      <div class="field">
        <label>AI / LLM provider</label>
        <select name="llm_provider">
          <option value="gemini" {{ ($pv['llm_provider'] ?? 'gemini')==='gemini'?'selected':'' }}>Gemini (Google — free tier, self-grounds)</option>
          <option value="groq" {{ ($pv['llm_provider'] ?? '')==='groq'?'selected':'' }}>Groq (Llama — free, no card)</option>
          <option value="openrouter" {{ ($pv['llm_provider'] ?? '')==='openrouter'?'selected':'' }}>OpenRouter (free models)</option>
        </select>
        <div class="hint">Groq/OpenRouter ground live data via the Search provider below.</div>
      </div>
      <div class="field">
        <label>Geocoding / autocomplete</label>
        <select name="geocode_provider">
          <option value="photon" {{ ($pv['geocode_provider'] ?? 'photon')==='photon'?'selected':'' }}>Photon (OSM — keyless, worldwide)</option>
          <option value="geoapify" {{ ($pv['geocode_provider'] ?? '')==='geoapify'?'selected':'' }}>Geoapify (3k/day free)</option>
          <option value="nominatim" {{ ($pv['geocode_provider'] ?? '')==='nominatim'?'selected':'' }}>Nominatim (OSM — keyless)</option>
          <option value="google" {{ ($pv['geocode_provider'] ?? '')==='google'?'selected':'' }}>Google Places (needs billing)</option>
        </select>
        <div class="hint">Map display always uses free Leaflet + OpenStreetMap.</div>
      </div>
    </div>

    <div class="row row-2">
      <div class="field"><label>Groq API key @if(!empty($pv['groq_api_key']))<span class="badge ok">set</span>@endif</label>
        <input type="password" name="groq_api_key" placeholder="{{ !empty($pv['groq_api_key']) ? '•••••••• stored' : 'console.groq.com/keys' }}"></div>
      <div class="field"><label>Groq model</label><input type="text" name="groq_model" value="{{ $pv['groq_model'] ?? config('providers.groq.model') }}"></div>
    </div>
    <div class="row row-2">
      <div class="field"><label>OpenRouter API key @if(!empty($pv['openrouter_api_key']))<span class="badge ok">set</span>@endif</label>
        <input type="password" name="openrouter_api_key" placeholder="{{ !empty($pv['openrouter_api_key']) ? '•••••••• stored' : 'openrouter.ai/keys' }}"></div>
      <div class="field"><label>OpenRouter model</label><input type="text" name="openrouter_model" value="{{ $pv['openrouter_model'] ?? config('providers.openrouter.model') }}"></div>
    </div>

    <div class="row row-3">
      <div class="field">
        <label>Search grounding (for Groq/OpenRouter)</label>
        <select name="search_provider">
          <option value="none" {{ ($pv['search_provider'] ?? 'none')==='none'?'selected':'' }}>None</option>
          <option value="tavily" {{ ($pv['search_provider'] ?? '')==='tavily'?'selected':'' }}>Tavily (1k/mo free)</option>
          <option value="brave" {{ ($pv['search_provider'] ?? '')==='brave'?'selected':'' }}>Brave Search (free)</option>
        </select>
      </div>
      <div class="field"><label>Tavily key @if(!empty($pv['tavily_api_key']))<span class="badge ok">set</span>@endif</label>
        <input type="password" name="tavily_api_key" placeholder="{{ !empty($pv['tavily_api_key']) ? '•••••••• stored' : 'tavily.com' }}"></div>
      <div class="field"><label>Brave key @if(!empty($pv['brave_api_key']))<span class="badge ok">set</span>@endif</label>
        <input type="password" name="brave_api_key" placeholder="{{ !empty($pv['brave_api_key']) ? '•••••••• stored' : 'brave.com/search/api' }}"></div>
    </div>

    <div class="field"><label>Geoapify key (if Geoapify selected) @if(!empty($pv['geoapify_api_key']))<span class="badge ok">set</span>@endif</label>
      <input type="password" name="geoapify_api_key" placeholder="{{ !empty($pv['geoapify_api_key']) ? '•••••••• stored' : 'geoapify.com — 3000/day free' }}"></div>
  </div>

  <div class="card mb">
    <h3>Brand</h3>
    <div class="row row-3">
      <div class="field"><label>Site name</label><input type="text" name="site_name" value="{{ $settings['brand']['site_name'] ?? 'Yatri' }}"></div>
      <div class="field"><label>Default currency</label><input type="text" name="default_currency" maxlength="3" value="{{ $settings['brand']['default_currency'] ?? 'USD' }}"></div>
      <div class="field"><label>FX rate (1 USD → INR)</label><input type="number" step="0.01" name="fx_rate_inr" value="{{ $settings['brand']['fx_rate_inr'] ?? 85 }}"></div>
    </div>
  </div>

  <div class="card mb">
    <h3>Booking / affiliate</h3>
    <div class="row row-2">
      <div class="field"><label>Flights affiliate base URL</label><input type="text" name="affiliate_flights" value="{{ $settings['booking']['affiliate_flights'] ?? '' }}" placeholder="https://…"></div>
      <div class="field"><label>Hotels affiliate base URL</label><input type="text" name="affiliate_hotels" value="{{ $settings['booking']['affiliate_hotels'] ?? '' }}" placeholder="https://www.booking.com/…?aid=…"></div>
    </div>
    <div class="hint">Hotel “Check rates” links append the search query to this base. Leave blank for plain Booking.com search.</div>
  </div>

  <button class="btn btn-accent">Save settings</button>
</form>
@endsection
