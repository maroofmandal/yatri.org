@extends('layouts.app')
@section('title', $trip->title.' — Yatri plan')

@php
  $plan = $trip->plan ?? [];
  $cur  = $trip->currency;
  $symMap = ['USD'=>'$','INR'=>'₹','EUR'=>'€','GBP'=>'£','AED'=>'AED ','SGD'=>'S$','JPY'=>'¥'];
  $sym = $symMap[$cur] ?? ($cur.' ');
  $money = fn($n) => '<span class="money" data-amt="'.(float)$n.'">'.$sym.number_format((float)$n).'</span>';

  $budget = $trip->budget_breakdown ?: ($plan['budget'] ?? []);
  $cats = [
    'accommodation'=>'🏨 Accommodation','food'=>'🍜 Food','activities'=>'🎟️ Activities',
    'local_transport'=>'🚇 Local transport','intercity_transport'=>'🚄 Intercity','flights'=>'✈️ Flights','misc'=>'🎒 Misc',
  ];
  $total = 0; foreach(array_keys($cats) as $k){ $total += (float)($budget[$k] ?? 0); }
  if($total<=0) $total = (float)($budget['total'] ?? $trip->budget_total);
  $maxCat = 1; foreach(array_keys($cats) as $k){ $maxCat = max($maxCat,(float)($budget[$k] ?? 0)); }

  $route = collect($plan['route'] ?? [])->filter(fn($r)=>isset($r['lat'],$r['lng']))->values();
  $fit = $plan['fit'] ?? [];
  $gmaps = fn($q) => 'https://www.google.com/maps/search/?api=1&query='.urlencode($q);
  $hotelAff = \App\Models\Setting::get('affiliate_hotels');
  $hotelLink = fn($q) => $hotelAff ? rtrim($hotelAff,'?&').(str_contains($hotelAff,'?')?'&':'?').'ss='.urlencode($q)
                                   : 'https://www.booking.com/searchresults.html?ss='.urlencode($q);
  $flightAff = \App\Models\Setting::get('affiliate_flights', 'https://www.google.com/travel/flights');
  $flightLink = fn($q) => rtrim($flightAff,'?&').(str_contains($flightAff,'?')?'&':'?').'q='.urlencode($q);

  $canManage = ($trip->user_id === null) || (auth()->check() && (auth()->user()->isAdmin() || $trip->user_id === auth()->id()));

  $placesData = $plan['places'] ?? [];
  $weatherDays = collect($plan['weather']['days'] ?? []);
  $placesApiKey = \App\Models\Setting::get('google_places_api_key')
    ?: (\App\Models\Setting::get('google_maps_api_key') ?: (config('gemini.google_places_api_key') ?: config('gemini.maps_key')));
  $photoUrl = fn($photoName, $width = 400) => $placesApiKey
    ? "https://places.googleapis.com/v1/{$photoName}/media?maxWidthPx={$width}&key={$placesApiKey}"
    : '';
  $statusText = fn($status) => match($status) {
    'live_forecast' => 'Live Google forecast',
    'confirmed_by_source' => 'Confirmed fee',
    'free' => 'Free',
    default => 'Estimated',
  };
@endphp

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css"/>
@endpush

@push('nav-right')
<select id="currencySelect">
  @foreach(['USD'=>'$ USD','INR'=>'₹ INR','EUR'=>'€ EUR','GBP'=>'£ GBP','AED'=>'AED','SGD'=>'S$ SGD','JPY'=>'¥ JPY'] as $code=>$lbl)
    <option value="{{ $code }}" {{ $cur===$code?'selected':'' }}>{{ $lbl }}</option>
  @endforeach
</select>
@endpush

@section('content')
<header class="hero"><div class="wrap">
  <p class="eyebrow">{{ $trip->origin }} · {{ $trip->days }} days · {{ $trip->travelers }} traveler(s) · {!! $money($trip->budget_total) !!} budget</p>
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <h1 style="margin:0"><strong>{{ $trip->title }}</strong>
      @if(!empty($plan['summary']))<span class="sub">{{ $plan['summary'] }}</span>@endif
    </h1>
  </div>
</div></header>

<div class="wrap">

  @if(!empty($plan['demo']))
    <div class="flash flash-err" style="margin-top:20px">
      @if($trip->error)⚠️ Live AI unavailable right now — showing a budget-fit sample. (Set a working Gemini key in Admin → Settings → AI.)
      @else⚠️ Sample plan (no AI key set). Add a Gemini key in Admin → Settings → AI for a live, grounded, budget-fit itinerary.
      @endif
    </div>
  @endif

  {{-- Budget fit --}}
  <div class="block">
    <h2>Budget — fits your {!! $money($trip->budget_total) !!}</h2>
    @php $fitClass = 'fit-'.($trip->fit_status ?? 'fit'); @endphp
    <div class="fit-banner {{ $fitClass }}">
      @if($trip->fit_status==='over') ⚠️ Realistic costs run over your cap.
      @elseif($trip->fit_status==='under') ✅ Comes in under budget — room to upgrade.
      @else ✅ Planned to fit your budget.
      @endif
      <strong style="margin-left:auto">{!! $money($total) !!} / {!! $money($trip->budget_total) !!}</strong>
    </div>
    @foreach($cats as $key=>$lbl)
      @php $val=(float)($budget[$key] ?? 0); @endphp
      @if($val>0)
      <div class="bbar">
        <div class="lbl">{{ $lbl }}</div>
        <div class="track"><span class="bbar-fill" data-pct="{{ max(4, round($val/$maxCat*100)) }}"></span></div>
        <div class="amt">{!! $money($val) !!}</div>
      </div>
      @endif
    @endforeach
    @if(!empty($fit['note']))<p class="hint mt">{{ $fit['note'] }}</p>@endif
  </div>

  {{-- Route options --}}
  @if(!empty($plan['route_options']))
  <div class="block">
    <h2>Choose your route</h2>
    <p class="lead">Routing options for these stops — trade-offs compared.</p>
    <div class="grid grid-2" id="routeOptions">
      @foreach($plan['route_options'] as $ro)
        <div class="card route-card{{ $loop->first ? ' route-active' : '' }}" data-route-idx="{{ $loop->index }}">
          <div style="display:flex;align-items:center;gap:8px">
            <h3>{{ $ro['label'] ?? 'Option' }}</h3>
            <span class="route-badge">Selected</span>
          </div>
          <p class="muted" style="font-size:13.5px">{{ $ro['summary'] ?? '' }}</p>
          <p style="font-size:14px;margin:8px 0"><strong>{{ $trip->origin }}</strong> → {{ implode(' → ', $ro['sequence'] ?? []) }} → <strong>{{ $trip->origin }}</strong></p>
          @if(!empty($ro['pros']))
          <div class="pc">
            <div class="pro"><b>Pros</b>{{ $ro['pros'] }}</div>
            @if(!empty($ro['cons']))<div class="con"><b>Cons</b>{{ $ro['cons'] }}</div>@endif
          </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Map --}}
  @if($route->count())
  <div class="block">
    <h2>Route</h2>
    <p class="lead">{{ $trip->origin }} → {{ $route->pluck('name')->implode(' → ') }}</p>
    <div id="map"></div>
  </div>
  @endif

  {{-- Weather --}}
  @if($weatherDays->count())
  <div class="block">
    <h2>Weather during trip</h2>
    <p class="lead">{{ $plan['weather']['note'] ?? 'Live Google Weather appears for trips within 10 days; later dates use seasonal estimates.' }}</p>
    <div class="weather-grid">
      @foreach($weatherDays as $w)
        <div class="weather-card {{ ($w['source'] ?? '') === 'google_weather' ? 'weather-live' : 'weather-est' }}">
          <div class="weather-top">
            <div>
              <b>Day {{ $w['day'] ?? $loop->iteration }}</b>
              <span>{{ $w['city'] ?? '' }}{{ !empty($w['date']) ? ' · '.\Illuminate\Support\Carbon::parse($w['date'])->format('d M') : '' }}</span>
            </div>
            @if(!empty($w['icon']))
              <img src="{{ $w['icon'] }}.svg" alt="" loading="lazy">
            @endif
          </div>
          <p>{{ $w['summary'] ?? 'Weather estimate unavailable.' }}</p>
          <div class="weather-meta">
            @if(isset($w['temperature_min_c']) || isset($w['temperature_max_c']))
              <span>{{ isset($w['temperature_min_c']) ? round($w['temperature_min_c']) : '—' }}°C / {{ isset($w['temperature_max_c']) ? round($w['temperature_max_c']) : '—' }}°C</span>
            @endif
            @if(isset($w['precipitation_probability']))
              <span>{{ round($w['precipitation_probability']) }}% rain</span>
            @endif
          </div>
          <span class="data-badge">{{ $statusText($w['status'] ?? 'estimated') }}</span>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Flights --}}
  @if(!empty($plan['flights']))
  <div class="block">
    <h2>Best flights &amp; prices</h2>
    <p class="lead">Indicative fares — tap to check live prices.</p>
    <div class="grid grid-2">
      @foreach($plan['flights'] as $f)
        <div class="card">
          <div style="font-weight:600;font-family:Outfit">{{ $f['from'] ?? '' }} → {{ $f['to'] ?? '' }} @if(!empty($f['type']))<span class="tag">{{ $f['type'] }}</span>@endif</div>
          <div class="muted" style="font-size:13px;margin:6px 0">{{ $f['airlines'] ?? '' }}{{ !empty($f['duration']) ? ' · '.$f['duration'] : '' }}</div>
          <div style="font-size:20px;font-weight:600;color:var(--accent);font-family:Outfit">{!! $money($f['price'] ?? 0) !!}</div>
          <span class="data-badge">{{ $statusText($f['price_status'] ?? 'estimated') }} · check live before booking</span>
          <a class="mlink mt" target="_blank" rel="noopener" href="{{ $flightLink('flights '.($f['from']??'').' to '.($f['to']??'')) }}">Check fares ↗</a>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Transport --}}
  @if(!empty($plan['transport']))
  <div class="block">
    <h2>Getting around</h2>
    @foreach($plan['transport'] as $t)
      <div class="tline">
        <span class="mode">{{ $t['mode'] ?? 'Transit' }}</span>
        <span class="ar">{{ $t['from'] ?? '' }} → {{ $t['to'] ?? '' }}{{ !empty($t['duration']) ? ' · '.$t['duration'] : '' }}{{ !empty($t['note']) ? ' · '.$t['note'] : '' }}</span>
        <span class="tc">{!! isset($t['cost']) ? $money($t['cost']) : '' !!}</span>
      </div>
    @endforeach
  </div>
  @endif

  {{-- Day by day --}}
  @if(!empty($plan['days']))
  @php $seenPlaces = []; @endphp
  <div class="block">
    <h2>Day-by-day</h2>
    <p class="lead">{{ count($plan['days']) }} days, paced to your style and budget.</p>
    @foreach($plan['days'] as $d)
      <div class="day">
        <div class="side">
          <div class="dt">Day {{ $d['day'] ?? $loop->iteration }}{{ !empty($d['date']) ? ' · '.$d['date'] : '' }}</div>
          <div class="city">{{ $d['city'] ?? '' }}</div>
          @foreach(($d['tags'] ?? []) as $tag)<span class="bdg">{{ $tag }}</span>@endforeach
        </div>
        <div class="body">
          <h3>{{ $d['title'] ?? '' }}</h3>
          @foreach(($d['items'] ?? []) as $it)
            <div class="it">
              <div class="time">{{ $it['time'] ?? '' }}</div>
              <div class="what">
                {{ $it['activity'] ?? '' }}
                @if(($it['place_key'] ?? null) && !empty($placesData[$it['place_key']]['rating']))
                  <span class="place-rating">★ {{ number_format($placesData[$it['place_key']]['rating'], 1) }}{{ !empty($placesData[$it['place_key']]['reviews_count']) ? ' ('.number_format($placesData[$it['place_key']]['reviews_count']).')' : '' }}</span>
                @endif
                @if(!empty($it['note']))<div class="note">{{ $it['note'] }}</div>@endif
                @if(!empty($it['map_query']))<a class="mlink" target="_blank" rel="noopener" href="{{ $gmaps($it['map_query']) }}">📍 Map</a>@endif
                @if(isset($it['cost']) || !empty($it['entry_fee_status']))
                  <span class="fee-badge {{ ($it['entry_fee_status'] ?? '') === 'free' ? 'fee-free' : '' }}">{{ $statusText($it['entry_fee_status'] ?? (((float)($it['cost'] ?? 0)) <= 0 ? 'free' : 'estimated')) }}</span>
                @endif
                @if(($it['place_key'] ?? null) && !empty($placesData[$it['place_key']]))
                  @php $itemPlace = $placesData[$it['place_key']]; @endphp
                  @if(!empty($itemPlace['business_status']) || !empty($itemPlace['price_level']) || !empty($itemPlace['website']) || !empty($itemPlace['maps_url']))
                  <div class="place-business mini">
                    @if(!empty($itemPlace['business_status']))<span>{{ str_replace('_',' ', strtolower($itemPlace['business_status'])) }}</span>@endif
                    @if(!empty($itemPlace['price_level']))<span>{{ str_replace('_',' ', strtolower($itemPlace['price_level'])) }}</span>@endif
                    @if(!empty($itemPlace['website']))<a target="_blank" rel="noopener" href="{{ $itemPlace['website'] }}">Website ↗</a>@endif
                    @if(!empty($itemPlace['maps_url']))<a target="_blank" rel="noopener" href="{{ $itemPlace['maps_url'] }}">Google Maps ↗</a>@endif
                  </div>
                  @endif
                @endif
                @if(($it['place_key'] ?? null) && !empty($placesData[$it['place_key']]['photos']) && $placesApiKey && ($it['_place_first'] ?? !isset($seenPlaces[$it['place_key'] ?? ''])))
                  @php $seenPlaces[$it['place_key']] = true; @endphp
                  <img class="place-thumb" src="{{ $photoUrl($placesData[$it['place_key']]['photos'][0], 200) }}" alt="{{ $placesData[$it['place_key']]['name'] ?? '' }}" loading="lazy">
                @endif
              </div>
              <div class="cost">{!! !empty($it['cost']) ? $money($it['cost']) : (isset($it['cost']) ? 'Free' : '') !!}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
  @endif

  {{-- Hotels --}}
  @if(!empty($plan['hotels']))
  <div class="block">
    <h2>Where to stay</h2>
    <div class="grid grid-3">
      @foreach($plan['hotels'] as $h)
        <div class="card hcard">
          <div class="hn">{{ $h['name'] ?? '' }}</div>
          <div class="muted" style="font-size:12.5px">{{ $h['city'] ?? '' }}{{ !empty($h['area']) ? ' · '.$h['area'] : '' }}</div>
          @if(!empty($h['rating']))<div class="rt">★ {{ number_format($h['rating'],1) }}</div>@endif
          <div class="hp">{!! $money($h['price_per_night'] ?? 0) !!} <small>/ night · {{ $h['nights'] ?? 1 }} nights</small></div>
          <span class="data-badge">{{ $statusText($h['price_status'] ?? 'estimated') }} · check live rates</span>
          <a class="mlink mt" target="_blank" rel="noopener" href="{{ $hotelLink($h['booking_query'] ?? (($h['name']??'').' '.($h['city']??''))) }}">Check rates ↗</a>
          @if(($h['place_key'] ?? null) && ($placesData[$h['place_key']] ?? null))
            @php $place = $placesData[$h['place_key']]; @endphp
            <div class="hotel-places-info">
              @if(!empty($place['business_status']) || !empty($place['price_level']) || !empty($place['website']))
              <div class="place-business">
                @if(!empty($place['business_status']))<span>{{ str_replace('_',' ', strtolower($place['business_status'])) }}</span>@endif
                @if(!empty($place['price_level']))<span>{{ str_replace('_',' ', strtolower($place['price_level'])) }}</span>@endif
                @if(!empty($place['website']))<a target="_blank" rel="noopener" href="{{ $place['website'] }}">Website ↗</a>@endif
              </div>
              @endif
              {{-- Star rating --}}
              @if(!empty($place['rating']))
              <div class="place-stars">
                @for($i = 1; $i <= 5; $i++)
                  {{ $i <= round($place['rating']) ? '★' : '☆' }}
                @endfor
                <strong>{{ number_format($place['rating'], 1) }}</strong>
                @if(!empty($place['reviews_count']))<span class="muted">({{ number_format($place['reviews_count']) }} reviews)</span>@endif
              </div>
              @endif
              {{-- Photo strip --}}
              @if(!empty($place['photos']) && $placesApiKey)
              <div class="place-photos">
                @foreach(array_slice($place['photos'], 0, 3) as $photo)
                  <img src="{{ $photoUrl($photo, 400) }}" alt="{{ $place['name'] ?? '' }}" loading="lazy">
                @endforeach
              </div>
              @endif
              {{-- Top reviews --}}
              @if(!empty($place['reviews']))
                @foreach(array_slice($place['reviews'], 0, 2) as $rev)
                <div class="place-review">
                  <div class="review-meta">
                    <span class="review-author">{{ $rev['author'] ?? '' }}</span>
                    <span class="place-stars">@for($i = 1; $i <= 5; $i++){{ $i <= ($rev['rating'] ?? 0) ? '★' : '☆' }}@endfor</span>
                    @if(!empty($rev['time']))<span class="muted">{{ $rev['time'] }}</span>@endif
                  </div>
                  <div class="review-text">{{ \Illuminate\Support\Str::limit($rev['text'] ?? '', 100) }}</div>
                </div>
                @endforeach
              @endif
            </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Tips --}}
  @if(!empty($plan['tips']))
  <div class="block">
    <h2>Good to know</h2>
    <ul>@foreach($plan['tips'] as $tip)<li style="margin:6px 0">{{ $tip }}</li>@endforeach</ul>
  </div>
  @endif

  {{-- Packing --}}
  @if(!empty($plan['packing']))
  <div class="block">
    <h2>Packing</h2>
    <div class="grid grid-2">
      @foreach($plan['packing'] as $pk)
        <div class="card">
          <h3>{{ $pk['title'] ?? '' }}</h3>
          <ul>@foreach(($pk['items'] ?? []) as $it)<li style="margin:5px 0;font-size:14px">{{ $it }}</li>@endforeach</ul>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Culture --}}
  @if(!empty($plan['culture']))
  <div class="block">
    <h2>Culture — do's &amp; don'ts</h2>
    <div class="grid grid-2">
      @foreach($plan['culture'] as $c)
        <div class="card">
          <h3>{{ $c['place'] ?? '' }}</h3>
          @if(!empty($c['dos']))<div class="subh">Do</div><ul>@foreach($c['dos'] as $d)<li style="margin:4px 0;font-size:14px">✅ {{ $d }}</li>@endforeach</ul>@endif
          @if(!empty($c['donts']))<div class="subh">Don't</div><ul>@foreach($c['donts'] as $d)<li style="margin:4px 0;font-size:14px">⛔ {{ $d }}</li>@endforeach</ul>@endif
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Pre-trip countdown --}}
  @if(!empty($plan['countdown']))
  <div class="block">
    <h2>Pre-trip countdown</h2>
    <div class="timeline">
      @foreach($plan['countdown'] as $c)
        <div><b>{{ $c['when'] ?? '' }}</b><small>{{ $c['tasks'] ?? '' }}</small></div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- AI chat --}}
  <div class="block">
    <h2>Ask about this trip</h2>
    <p class="lead">Live answers grounded in Google Search &amp; Maps — “make day 3 cheaper”, “best area to stay”, “weather in October?”.</p>
    <div class="chat-box">
      <div class="chat-log" id="chatLog">
        <div class="msg bot">Hi! Ask me anything about this trip — costs, timing, swaps, weather, bookings.</div>
      </div>
      <form class="chat-input" id="chatForm">
        <input type="text" id="chatInput" placeholder="Ask a question…" maxlength="500" autocomplete="off">
        <button class="btn btn-primary" type="submit">Ask</button>
      </form>
    </div>
  </div>

  {{-- Sources --}}
  @if(!empty($trip->grounding))
  <div class="block">
    <h2>Sources</h2>
    <p class="lead">Live references Gemini used to ground this plan.</p>
    <div class="source-list">
      @foreach($trip->grounding as $g)
        @if(!empty($g['uri']))<a target="_blank" rel="noopener" href="{{ $g['uri'] }}">{{ $g['type']==='maps'?'📍':'🔗' }} {{ \Illuminate\Support\Str::limit($g['title'] ?: $g['uri'], 50) }}</a>@endif
      @endforeach
    </div>
  </div>
  @endif

  {{-- Likes & comments --}}
  <div class="block" id="comments">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
      <button class="btn {{ $trip->isLikedBy(auth()->user())?'btn-accent':'btn-ghost' }}" id="likeBtn" {{ auth()->check()?'':'disabled' }}>
        <span id="likeIcon">{{ $trip->isLikedBy(auth()->user())?'♥':'♡' }}</span> <span id="likeCount">{{ $trip->likes()->count() }}</span>
      </button>
      <span class="muted">💬 {{ $trip->comments()->count() }} comments</span>
      @guest<span class="muted" style="font-size:13px">· <a href="{{ route('login') }}">Log in</a> to like &amp; comment</span>@endguest
    </div>

    <h2 style="margin-top:20px">Comments</h2>
    @auth
      <form method="POST" action="{{ route('trip.comment',$trip) }}" style="display:flex;gap:8px;margin-bottom:16px">
        @csrf<input type="text" name="body" placeholder="Add a comment…" maxlength="1000" required><button class="btn btn-primary">Post</button>
      </form>
    @endauth
    @forelse($trip->comments()->with('user')->latest()->get() as $c)
      <div style="display:flex;gap:10px;padding:11px 0;border-top:1px solid var(--line)">
        <img src="{{ $c->user->avatar() }}" alt="" style="width:34px;height:34px;border-radius:50%">
        <div>
          <a href="{{ route('profile',$c->user) }}" style="font-weight:600;font-size:14px">{{ $c->user->name }}</a>
          <span class="muted" style="font-size:12px">· {{ $c->created_at->diffForHumans() }}</span>
          <div style="font-size:14px">{{ $c->body }}</div>
        </div>
      </div>
    @empty
      <p class="muted">No comments yet. Be the first.</p>
    @endforelse
  </div>

  {{-- Share / actions --}}
  <div class="block">
    <h2>Share &amp; save</h2>
    <div class="row row-2">
      <div class="field">
        <label>Shareable link</label>
        <div style="display:flex;gap:8px">
          <input type="text" id="shareUrl" value="{{ route('trip.show', $trip) }}" readonly>
          <button class="btn btn-ghost" onclick="navigator.clipboard.writeText(document.getElementById('shareUrl').value);this.textContent='Copied!'">Copy</button>
        </div>
      </div>
      <div class="field" style="display:flex;align-items:flex-end;gap:10px">
        @if($canManage)
        <form method="POST" action="{{ route('trip.regenerate', $trip) }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Regenerating…'">
          @csrf<button class="btn btn-ghost" type="submit">↻ Regenerate</button>
        </form>
        @endif
        <a class="btn btn-primary" href="{{ route('home') }}">+ New trip</a>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<div id="page-data"
     data-route="{!! e(json_encode($route->map(fn($r)=>['name'=>$r['name'],'lat'=>(float)$r['lat'],'lng'=>(float)$r['lng']]))) !!}"
     data-route-options="{!! e(json_encode($plan['route_options'] ?? [])) !!}"
     data-cur-trip="{!! e(json_encode($cur)) !!}"
     style="display:none"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const _pd = document.getElementById('page-data').dataset;

// ── budget bar widths ──
document.querySelectorAll('.bbar-fill').forEach(el => {
  el.style.width = el.dataset.pct + '%';
});

// ── map ──
const ROUTE = JSON.parse(_pd.route);
const ROUTE_OPTIONS = JSON.parse(_pd.routeOptions);
let routePolylines = [];
if (ROUTE.length && window.L) {
  const map = L.map('map',{scrollWheelZoom:false}).setView([ROUTE[0].lat,ROUTE[0].lng],4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,attribution:'© OpenStreetMap'}).addTo(map);
  const pts = ROUTE.map(r=>[r.lat,r.lng]);
  ROUTE.forEach((r,i)=>L.marker([r.lat,r.lng]).addTo(map).bindPopup(`<b>${i+1}. ${r.name}</b>`));

  // Build per-route-option polylines
  if (ROUTE_OPTIONS.length) {
    ROUTE_OPTIONS.forEach((opt, idx) => {
      const seq = opt.sequence || [];
      const seqPts = seq.map(city => {
        const match = ROUTE.find(r => r.name.toLowerCase() === city.toLowerCase());
        return match ? [match.lat, match.lng] : null;
      }).filter(Boolean);
      if (seqPts.length > 1) {
        const poly = L.polyline(seqPts, {
          color: idx === 0 ? '#c2412c' : '#a8a29e',
          weight: idx === 0 ? 4 : 2.5,
          opacity: idx === 0 ? 1 : 0.5
        }).addTo(map);
        routePolylines.push(poly);
      } else {
        routePolylines.push(null);
      }
    });
  } else {
    L.polyline(pts,{color:'#c2412c',weight:3.5}).addTo(map);
  }
  map.fitBounds(L.latLngBounds(pts).pad(0.25));
}

// ── route option switching ──
document.querySelectorAll('.route-card').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('.route-card').forEach(c => c.classList.remove('route-active'));
    card.classList.add('route-active');
    const idx = parseInt(card.dataset.routeIdx, 10);
    routePolylines.forEach((poly, i) => {
      if (!poly) return;
      if (i === idx) {
        poly.setStyle({color:'#c2412c', weight:4, opacity:1});
        poly.bringToFront();
      } else {
        poly.setStyle({color:'#a8a29e', weight:2.5, opacity:0.5});
      }
    });
  });
});

// ── chat ──
const log = document.getElementById('chatLog');
function add(cls, html){ const d=document.createElement('div'); d.className='msg '+cls; d.innerHTML=html; log.appendChild(d); log.scrollTop=log.scrollHeight; return d; }
document.getElementById('chatForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const inp=document.getElementById('chatInput'); const q=inp.value.trim(); if(!q) return;
  add('user', q.replace(/</g,'&lt;')); inp.value='';
  const typing = add('bot', '<span class="typing"><i></i><i></i><i></i></span>');
  try{
    const r = await fetch("{{ route('trip.chat', $trip) }}", {method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify({question:q})});
    const d = await r.json();
    let html = (d.answer||'No answer.').replace(/</g,'&lt;').replace(/\n/g,'<br>');
    if(d.grounding && d.grounding.length){
      html += '<div class="cites">'+d.grounding.filter(g=>g.uri).slice(0,5)
        .map(g=>`<a target="_blank" rel="noopener" href="${g.uri}">${(g.title||'source').slice(0,32)}</a>`).join('')+'</div>';
    }
    typing.innerHTML = html;
  }catch(err){ typing.textContent='Sorry — something went wrong.'; }
});

// ── currency converter ──
const CUR_TRIP = JSON.parse(_pd.curTrip);
const CUR_SYM_MAP = {'USD':'$','INR':'₹','EUR':'€','GBP':'£','AED':'AED ','SGD':'S$','JPY':'¥'};
const curSel = document.getElementById('currencySelect');
let curRate = 1; // USD → current display currency
let baseRate = 1; // USD → trip's original currency

async function fetchFxRate(currency) {
  if (currency === 'USD') return 1;
  try {
    const res = await fetch('/api/fx/' + currency);
    const d = await res.json();
    if (d.rate) return d.rate;
  } catch(e) {}
  return null;
}

function convertCurrency() {
  const sym = CUR_SYM_MAP[curSel.value] ?? (curSel.value + ' ');
  document.querySelectorAll('.money[data-amt]').forEach(el => {
    const origAmt = parseFloat(el.dataset.amt);
    if (isNaN(origAmt)) return;
    // origAmt is in trip currency → USD → target currency
    const usdAmt = baseRate ? origAmt / baseRate : origAmt;
    const converted = usdAmt * curRate;
    el.textContent = sym + Math.round(converted).toLocaleString();
  });
}

curSel.addEventListener('change', async () => {
  const newRate = await fetchFxRate(curSel.value);
  if (newRate !== null) {
    curRate = newRate;
    convertCurrency();
  }
});

// Init: fetch the trip's currency rate so we have the base
(async () => {
  const r = await fetchFxRate(CUR_TRIP);
  if (r !== null) baseRate = r;
  curRate = baseRate; // start displaying in trip currency
})();

// ── like ──
const likeBtn = document.getElementById('likeBtn');
if (likeBtn && !likeBtn.disabled) {
  likeBtn.addEventListener('click', async ()=>{
    try{
      const r = await fetch("{{ route('trip.like', $trip) }}", {method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});
      const d = await r.json();
      document.getElementById('likeCount').textContent = d.count;
      document.getElementById('likeIcon').textContent = d.liked ? '♥' : '♡';
      likeBtn.classList.toggle('btn-accent', d.liked);
      likeBtn.classList.toggle('btn-ghost', !d.liked);
    }catch(e){}
  });
}
</script>
@endpush
@endsection
