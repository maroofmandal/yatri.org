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
      <div class="field"><label>Nano Banana (image) model</label><input type="text" name="nano_banana_model" value="{{ $settings['ai']['nano_banana_model'] ?? config('gemini.nano_banana_model', 'gemini-3.1-flash-image') }}"></div>
    </div>
    <div class="row row-2">
      <div class="field">
        <label>Google Maps API key (optional) @if(!empty($settings['ai']['google_maps_api_key']))<span class="badge ok">set</span>@endif</label>
        <input type="password" name="google_maps_api_key" placeholder="{{ !empty($settings['ai']['google_maps_api_key']) ? '•••••••• stored' : 'optional — richer place data' }}">
        <div class="hint">Enable Maps JavaScript API and Places API in Google Cloud for autocomplete and business data. Weather uses Open-Meteo (free, no key needed).</div>
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

  {{-- Gemini API Keys (Round-Robin) --}}
  <div class="card mb">
    <h3>Gemini API Keys <span class="muted" style="font-size:13px;font-weight:500">— round-robin pool</span></h3>
    <div class="hint" style="margin-bottom:12px">Add multiple Gemini keys for automatic failover. If a key hits its rate limit (429), the system rotates to the next active key.</div>
    <table class="t" style="margin-bottom:12px">
      <thead><tr><th>Label</th><th>Key</th><th>Status</th><th>Last used</th><th></th></tr></thead>
      <tbody>
        @forelse($geminiKeys as $ak)
          <tr>
            <td>{{ $ak->label ?: '—' }}</td>
            <td><code style="font-size:11px">{{ substr($ak->key, 0, 8) }}••••{{ substr($ak->key, -4) }}</code></td>
            <td>@if($ak->is_active)<span class="badge ok">active</span>@else<span class="badge warn">exhausted</span>@endif</td>
            <td style="font-size:11px;color:var(--md-on-surface-variant)">{{ $ak->last_used_at ? $ak->last_used_at->diffForHumans() : 'never' }}</td>
            <td>
              <button class="btn btn-small" style="color:var(--md-error)" form="rm-gemini-{{ $ak->id }}">remove</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:var(--md-on-surface-variant);font-size:13px;padding:18px">No Gemini API keys configured. The legacy single key will be used.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="row row-3" style="align-items:end">
      <div class="field"><label>Add key</label><input type="password" name="new_gemini_key" placeholder="Paste AI Studio key"></div>
      <div class="field"><label>Label (optional)</label><input type="text" name="new_gemini_label" placeholder="e.g. Free tier #2"></div>
      <div>
        <button class="btn btn-accent" form="add-gemini-key-form">Add key</button>
        @if($geminiKeys->where('is_active',false)->count())
          <button class="btn btn-small" style="margin-left:8px" form="refresh-gemini-form" title="Reactivate exhausted keys">Reactivate all</button>
        @endif
      </div>
    </div>
  </div>

  {{-- Nano Banana API Keys (Image Generation) --}}
  <div class="card mb">
    <h3>Nano Banana <span class="muted" style="font-size:13px;font-weight:500">— AI image generation (gemini-3.1-flash-image)</span></h3>
    <div class="hint" style="margin-bottom:12px">Gemini Nano Banana generates trip hero images and Open Graph images. Multiple keys rotate on rate-limit exhaustion.</div>
    <table class="t" style="margin-bottom:12px">
      <thead><tr><th>Label</th><th>Key</th><th>Status</th><th>Last used</th><th></th></tr></thead>
      <tbody>
        @forelse($nanoBananaKeys as $ak)
          <tr>
            <td>{{ $ak->label ?: '—' }}</td>
            <td><code style="font-size:11px">{{ substr($ak->key, 0, 8) }}••••{{ substr($ak->key, -4) }}</code></td>
            <td>@if($ak->is_active)<span class="badge ok">active</span>@else<span class="badge warn">exhausted</span>@endif</td>
            <td style="font-size:11px;color:var(--md-on-surface-variant)">{{ $ak->last_used_at ? $ak->last_used_at->diffForHumans() : 'never' }}</td>
            <td>
              <button class="btn btn-small" style="color:var(--md-error)" form="rm-nb-{{ $ak->id }}">remove</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:var(--md-on-surface-variant);font-size:13px;padding:18px">No Nano Banana keys configured. Trip images will not be generated.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="row row-3" style="align-items:end">
      <div class="field"><label>Add key</label><input type="password" name="new_nano_banana_key" placeholder="Paste AI Studio key"></div>
      <div class="field"><label>Label (optional)</label><input type="text" name="new_nano_banana_label" placeholder="e.g. NB key #1"></div>
      <div>
        <button class="btn btn-accent" form="add-nb-key-form">Add key</button>
        @if($nanoBananaKeys->where('is_active',false)->count())
          <button class="btn btn-small" style="margin-left:8px" form="refresh-nb-form" title="Reactivate exhausted keys">Reactivate all</button>
        @endif
      </div>
    </div>
  </div>

  {{-- Weather Settings --}}
  <div class="card mb">
    <h3>Weather <span class="muted" style="font-size:13px;font-weight:500">— daily forecast on trip pages</span></h3>
    <label style="font-weight:500;display:block;margin-bottom:12px">
      <input type="checkbox" name="weather_enabled" value="1" style="width:auto;margin-right:6px"
        {{ ($settings['weather']['weather_enabled'] ?? true) ? 'checked' : '' }}>
      Show weather details on planned trip dates
    </label>
    <div class="row row-2">
      <div class="field">
        <label>Provider</label>
        <select name="weather_provider">
          <option value="open_meteo" {{ ($settings['weather']['weather_provider'] ?? 'open_meteo') === 'open_meteo' ? 'selected' : '' }}>Open-Meteo (free, no key)</option>
          <option value="gemini" {{ ($settings['weather']['weather_provider'] ?? '') === 'gemini' ? 'selected' : '' }}>Gemini AI (uses AI key above)</option>
        </select>
        <div class="hint">Open-Meteo returns live data from national weather services. Gemini generates descriptive weather summaries.</div>
      </div>
      @php $weatherKeySet = !empty($settings['weather']['weather_api_key']); @endphp
      <div class="field">
        <label>Weather API key @if($weatherKeySet)<span class="badge ok">set</span>@endif</label>
        <input type="password" name="weather_api_key" placeholder="{{ $weatherKeySet ? '•••••••• stored (leave blank to keep)' : 'Optional — separate weather API key' }}">
        <div class="hint">Only needed if you use a custom weather API. Open-Meteo and Gemini use existing keys.</div>
      </div>
    </div>
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
    <div class="row row-2">
      <div class="field"><label>Site name</label><input type="text" name="site_name" value="{{ $settings['brand']['site_name'] ?? 'Yatri' }}"></div>
      <div class="field"><label>Default currency</label><input type="text" name="default_currency" maxlength="3" value="{{ $settings['brand']['default_currency'] ?? 'USD' }}"></div>
    </div>

    <label>Fallback exchange rates <span class="muted" style="font-weight:500">— 1 USD = X units (used when live API is down)</span></label>
    @php
      $fxRates = $settings['brand']['fx_rates'] ?? [];
      if (!is_array($fxRates)) $fxRates = [];
      $defaults = ['inr'=>85,'eur'=>0.92,'gbp'=>0.79,'aed'=>3.67,'sgd'=>1.34,'jpy'=>157];
      $symbols = ['INR'=>'₹','EUR'=>'€','GBP'=>'£','AED'=>'AED','SGD'=>'S$','JPY'=>'¥'];
    @endphp
    <div class="row row-3">
      @foreach($symbols as $code => $sym)
        @php $lc = strtolower($code); @endphp
        <div class="field">
          <label style="font-size:12px">{{ $sym }} {{ $code }} <span class="muted" style="font-weight:400">(1 USD →)</span></label>
          <input type="number" step="0.01" min="0.01" name="fx_rates[{{ $lc }}]" value="{{ $fxRates[$lc] ?? $defaults[$lc] ?? '' }}" placeholder="{{ $defaults[$lc] ?? '' }}">
        </div>
      @endforeach
    </div>
    <div class="hint">These rates are the safety net. The planner uses live rates from a free CDN API; if that is unreachable, it falls back to these values.</div>
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

{{-- Standalone forms for API key management (must be OUTSIDE the main settings form) --}}
@foreach($geminiKeys as $ak)
  <form id="rm-gemini-{{ $ak->id }}" method="POST" action="{{ route('admin.api-keys.destroy', $ak) }}" style="display:none" onsubmit="return confirm('Remove this key?')">
    @csrf @method('DELETE')
  </form>
@endforeach
@foreach($nanoBananaKeys as $ak)
  <form id="rm-nb-{{ $ak->id }}" method="POST" action="{{ route('admin.api-keys.destroy', $ak) }}" style="display:none" onsubmit="return confirm('Remove this key?')">
    @csrf @method('DELETE')
  </form>
@endforeach
<form id="refresh-gemini-form" method="POST" action="{{ route('admin.api-keys.refresh') }}" style="display:none">
  @csrf
  <input type="hidden" name="service" value="gemini">
</form>
<form id="refresh-nb-form" method="POST" action="{{ route('admin.api-keys.refresh') }}" style="display:none">
  @csrf
  <input type="hidden" name="service" value="nano_banana">
</form>
<form id="add-gemini-key-form" method="POST" action="{{ route('admin.api-keys.store') }}" style="display:none">
  @csrf
  <input type="hidden" name="service" value="gemini">
  <input type="hidden" name="key" value="">
  <input type="hidden" name="label" value="">
</form>
<form id="add-nb-key-form" method="POST" action="{{ route('admin.api-keys.store') }}" style="display:none">
  @csrf
  <input type="hidden" name="service" value="nano_banana">
  <input type="hidden" name="key" value="">
  <input type="hidden" name="label" value="">
</form>

@push('scripts')
<script>
document.querySelector('[form="add-gemini-key-form"]')?.addEventListener('click', function(e) {
  const form = document.getElementById('add-gemini-key-form');
  const key = form.closest('.card').querySelector('[name="new_gemini_key"]');
  const label = form.closest('.card').querySelector('[name="new_gemini_label"]');
  if (!key.value) { e.preventDefault(); alert('Paste an API key first.'); return; }
  form.querySelector('[name="key"]').value = key.value;
  form.querySelector('[name="label"]').value = label.value;
});
document.querySelector('[form="add-nb-key-form"]')?.addEventListener('click', function(e) {
  const form = document.getElementById('add-nb-key-form');
  const card = form.closest('.card');
  const key = card.querySelector('[name="new_nano_banana_key"]');
  const label = card.querySelector('[name="new_nano_banana_label"]');
  if (!key.value) { e.preventDefault(); alert('Paste an API key first.'); return; }
  form.querySelector('[name="key"]').value = key.value;
  form.querySelector('[name="label"]').value = label.value;
});
</script>
@endpush

<script>
// Also handle the "Add key" buttons that use the hidden forms
document.querySelectorAll('[form="add-gemini-key-form"]').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const card = this.closest('.card');
    const key = card.querySelector('[name="new_gemini_key"]');
    const label = card.querySelector('[name="new_gemini_label"]');
    if (!key || !key.value) { e.preventDefault(); alert('Paste an API key first.'); return; }
    const form = document.getElementById('add-gemini-key-form');
    form.querySelector('[name="key"]').value = key.value;
    form.querySelector('[name="label"]').value = label.value || '';
  });
});
document.querySelectorAll('[form="add-nb-key-form"]').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const card = this.closest('.card');
    const key = card.querySelector('[name="new_nano_banana_key"]');
    const label = card.querySelector('[name="new_nano_banana_label"]');
    if (!key || !key.value) { e.preventDefault(); alert('Paste an API key first.'); return; }
    const form = document.getElementById('add-nb-key-form');
    form.querySelector('[name="key"]').value = key.value;
    form.querySelector('[name="label"]').value = label.value || '';
  });
});
</script>
@endsection
