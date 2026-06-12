@extends('layouts.app')
@section('title', $trip->title.' — Yatri plan')

@php
  $tripImage = $trip->image ? \Illuminate\Support\Facades\Storage::url($trip->image) : null;
@endphp
@if($tripImage)
  @section('og_image', $tripImage)
  @section('og_image_width', '1200')
  @section('og_image_height', '630')
  @section('twitter_card', 'summary_large_image')
@endif

@php
  $plan = $trip->plan ?? [];
  $cur  = $trip->currency;
  $symMap = ['USD'=>'$','INR'=>'₹','EUR'=>'€','GBP'=>'£','AED'=>'AED ','SGD'=>'S$','JPY'=>'¥'];
  $sym = $symMap[$cur] ?? ($cur.' ');
  $money = fn($n) => '<span class="money" data-amt="'.(float)$n.'" data-cur="'.$cur.'">'.$sym.number_format((float)$n).'</span>';
  $fmt = fn($t) => nl2br(preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($t)));

  $budget = $trip->budget_breakdown ?: ($plan['budget'] ?? []);
  $catIcons = [
    'accommodation'=>'hotel',
    'food'=>'restaurant',
    'activities'=>'confirmation_number',
    'local_transport'=>'subway',
    'intercity_transport'=>'train',
    'flights'=>'flight',
    'misc'=>'sell',
  ];
  $cats = [
    'accommodation'=>'Accommodation',
    'food'=>'Food',
    'activities'=>'Activities',
    'local_transport'=>'Local transport',
    'intercity_transport'=>'Intercity',
    'flights'=>'Flights',
    'misc'=>'Misc',
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

  $canManage = auth()->check()
      ? (auth()->user()->isAdmin() || $trip->user_id === auth()->id())
      : (($trip->user_id === null) && $trip->session_id === session()->getId());

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

  /* Group hotels by city */
  $hotelsByCity = [];
  foreach(($plan['hotels'] ?? []) as $h){
    $city = $h['city'] ?? 'Other';
    $hotelsByCity[$city][] = $h;
  }

  /* Group days by city for destination cards */
  $daysByCity = [];
  foreach(($plan['days'] ?? []) as $d){
    $city = $d['city'] ?? 'Other';
    $daysByCity[$city][] = $d;
  }

  /* Collect unique spots per city from days */
  $spotsByCity = [];
  $seenSpotKeys = [];
  foreach(($plan['days'] ?? []) as $d){
    $city = $d['city'] ?? 'Other';
    foreach(($d['items'] ?? []) as $it){
      $pk = $it['place_key'] ?? null;
      if($pk && !isset($seenSpotKeys[$pk]) && !empty($placesData[$pk])){
        $seenSpotKeys[$pk] = true;
        $spotsByCity[$city][] = ['item' => $it, 'place' => $placesData[$pk], 'place_key' => $pk];
      }
    }
  }

  /* City gradient colors */
  $cityColors = ['#33384a,#7a1f37','#1f3a5f,#2b6cb0','#3a2150,#7b2ff7','#1f4d3a,#2f8f6b','#5a3a12,#c98a2b','#2a2140,#6b21a8','#7c2d12,#dc2626'];

  /* Per-city cost estimates from budget (approximate) */
  $cityCount = max(1, count($plan['route'] ?? []));
  $avgHotelNight = $cityCount > 0 ? round(($budget['accommodation'] ?? 0) / max(1, $trip->days)) : 80;
  $avgFoodDay = $cityCount > 0 ? round(($budget['food'] ?? 0) / max(1, $trip->days)) : 45;
  $avgActivities = $cityCount > 0 ? round(($budget['activities'] ?? 0) / max(1, $cityCount)) : 50;
  $avgTransport = $cityCount > 0 ? round((($budget['local_transport'] ?? 0) + ($budget['intercity_transport'] ?? 0)) / max(1, $cityCount)) : 30;
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
<header class="trip-hero" @if($tripImage) style="background-image:url('{{ $tripImage }}')" @else style="background:{{ $trip->fallbackGradient() }}" @endif>
  <div class="trip-hero-glass">
    <div class="wrap">
      <p class="eyebrow">{{ $trip->origin }} · {{ $trip->days }} days · {{ $trip->nights }} nights · {{ $trip->travelers }} traveler(s) · {!! $money($trip->budget_total) !!} budget · <x-icon name="visibility" :size="14" /> {{ $trip->views }} · <x-icon name="share" :size="14" /> {{ $trip->shares ?? 0 }}</p>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <h1 style="margin:0;color:#fff"><strong>{{ $trip->title }}</strong>
          @if(!empty($plan['summary']))<span class="sub" style="color:rgba(255,255,255,.8)">{!! $fmt($plan['summary']) !!}</span>@endif
        </h1>
      </div>
    </div>
  </div>
</header>

<div class="wrap">

  {{-- Profile-style tabs --}}
  <div class="profile-tabs" style="margin-bottom: 24px;">
    <button class="profile-tab active" onclick="showTab('trips', this)">
      <x-icon name="map" /> Trips <span class="tab-count">1</span>
    </button>
    <button class="profile-tab" onclick="showTab('posts', this)">
      <x-icon name="article" /> Posts <span class="tab-count">{{ $posts->count() }}</span>
    </button>
    <button class="profile-tab" onclick="showTab('media', this)">
      <x-icon name="photo_library" /> Media <span id="media-count-badge" class="tab-count">{{ $media->count() }}</span>
    </button>
    <button class="profile-tab" onclick="showTab('reviews', this)">
      <x-icon name="star" /> Reviews <span class="tab-count">{{ $reviews->count() }}</span>
    </button>
  </div>

  <div class="tab-content" id="tab-trips">

  @if(!empty($plan['demo']))
    <div class="flash flash-err" style="margin-top:20px">
      @if($trip->error)<x-icon name="warning" :size="18" /> Live AI unavailable — showing a budget-fit sample. Set a Gemini key in Admin → Settings → AI.
      @else<x-icon name="info" :size="18" /> Sample plan (no AI key). Add a Gemini key in Admin → Settings → AI for a live itinerary.
      @endif
    </div>
  @endif

  {{-- Interactive controls --}}
  <div class="controls-panel reveal d1">
    <div class="ctrl-card">
      <label>Budget target</label>
      <div class="ctrl-value" id="budgetDisplay">{!! $money($trip->budget_total) !!}</div>
      <input type="range" id="budgetSlider" min="50" max="50000" step="50" value="{{ round($trip->budget_total) }}" style="margin-top:8px">
    </div>
    <div class="ctrl-card">
      <label>Days</label>
      <div style="display:flex;align-items:center;gap:14px">
        <div class="day-stepper">
          <button type="button" id="dayMinus">−</button>
          <span class="step-val" id="dayValue">{{ $trip->days }}</span>
          <button type="button" id="dayPlus">+</button>
        </div>
      </div>
    </div>
    <div class="ctrl-card">
      <label>Nights</label>
      <div style="display:flex;align-items:center;gap:14px">
        <div class="day-stepper">
          <button type="button" id="nightMinus">−</button>
          <span class="step-val" id="nightValue">{{ $trip->nights }}</span>
          <button type="button" id="nightPlus">+</button>
        </div>
      </div>
    </div>
    <div class="ctrl-card">
      <label>Travellers</label>
      <div style="display:flex;align-items:center;gap:14px">
        <div class="day-stepper">
          <button type="button" id="travMinus">−</button>
          <span class="step-val" id="travValue">{{ $trip->travelers }}</span>
          <button type="button" id="travPlus">+</button>
        </div>
      </div>
    </div>
    @if(!empty($plan['route_options']) && count($plan['route_options']) > 1)
    <div class="ctrl-card">
      <label>Route</label>
      <div class="route-tab-btn" id="routeTabs">
        @foreach($plan['route_options'] as $idx => $ro)
          <button type="button" class="{{ $idx === 0 ? 'on' : '' }}" data-route-idx="{{ $idx }}">{{ $ro['label'] ?? 'Option '.($idx+1) }}</button>
        @endforeach
      </div>
    </div>
    @endif
  </div>

  {{-- Budget fit --}}
  <div class="block reveal d2">
    <h2>Budget — fits your {!! $money($trip->budget_total) !!}</h2>
    @php $fitClass = 'fit-'.($trip->fit_status ?? 'fit'); @endphp
    <div class="fit-banner {{ $fitClass }}" id="fitBanner">
      @if($trip->fit_status==='over') <x-icon name="warning" :size="20" /> Realistic costs run over your cap.
      @elseif($trip->fit_status==='under') <x-icon name="check_circle" :size="20" style="color:var(--md-primary)" /> Comes in under budget — room to upgrade.
      @else <x-icon name="check_circle" :size="20" style="color:var(--md-primary)" /> Planned to fit your budget.
      @endif
      <strong style="margin-left:auto" id="fitAmount">{!! $money($total) !!} / {!! $money($trip->budget_total) !!}</strong>
    </div>
    @foreach($cats as $key=>$lbl)
      @php $val=(float)($budget[$key] ?? 0); @endphp
      @if($val>0)
      <div class="bbar">
        <div class="bbar-header">
          <div class="lbl">
            <x-icon name="{{ $catIcons[$key] ?? 'sell' }}" />
            {{ $lbl }}
          </div>
          <div class="amt">{!! $money($val) !!}</div>
        </div>
        <div class="track"><span class="bbar-fill" data-pct="{{ max(4, round($val/$maxCat*100)) }}"></span></div>
      </div>
      @endif
    @endforeach
    @if(!empty($fit['note']))<p class="hint mt">{{ $fit['note'] }}</p>@endif
  </div>

  {{-- Route options --}}
  @if(!empty($plan['route_options']))
  <div class="block reveal">
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
  <div class="block reveal">
    <h2>Route</h2>
    <p class="lead">{{ $trip->origin }} → {{ $route->pluck('name')->implode(' → ') }}</p>
    <div id="map"></div>
  </div>
  @endif

  {{-- Weather --}}
  @php $hasWeather = $weatherDays->isNotEmpty(); @endphp
  @if($hasWeather)
  <div class="block reveal">
    <h2>Weather during trip</h2>
    <p class="lead">Live weather from Open-Meteo for your trip dates.</p>
    <div class="weather-grid">
      @foreach($weatherDays as $w)
        @php
          $cls = $w['icon_class'] ?? 'unknown';
          $hasData = ($w['weather_code'] ?? null) !== null;
          $icon = $hasData && !empty($w['icon']) ? $w['icon'] : 'cloud_off';
        @endphp
        <div class="weather-card weather-card--{{ $cls }}">
          <div class="weather-card-bg"></div>
          <div class="weather-card-body">
            <div class="weather-card-head">
              <x-icon name="{{ $icon }}" :size="32" class="weather-icon" />
              <div class="weather-card-day">
                <span class="w-day">Day {{ $w['day'] ?? $loop->iteration }}</span>
                <span class="w-city">{{ $w['city'] ?? '' }}</span>
                @if(!empty($w['date']))<span class="w-date">{{ \Illuminate\Support\Carbon::parse($w['date'])->format('d M') }}</span>@endif
              </div>
            </div>
            <div class="weather-summary">{{ $w['summary'] ?? 'Weather data unavailable.' }}</div>
            @if(isset($w['temperature_min_c']) || isset($w['temperature_max_c']))
              <div class="weather-temps">
                <span class="w-high">{{ isset($w['temperature_max_c']) ? round($w['temperature_max_c']).'°' : '—°' }}</span>
                <span class="w-sep">/</span>
                <span class="w-low">{{ isset($w['temperature_min_c']) ? round($w['temperature_min_c']).'°' : '—°' }}</span>
              </div>
            @endif
            <div class="weather-stats">
              @if(isset($w['precipitation_probability']))
                @php $pp = round($w['precipitation_probability']); @endphp
                <span class="weather-stat" title="{{ $pp }}% rain">
                  <x-icon name="water_drop" :size="14" /> {{ $pp }}%
                </span>
              @endif
              @if(isset($w['wind_speed']))
                <span class="weather-stat">
                  <x-icon name="air" :size="14" /> {{ round($w['wind_speed']) }} km/h
                </span>
              @endif
              @if(isset($w['uv_index']))
                <span class="weather-stat">
                  <x-icon name="brightness_5" :size="14" /> UV {{ round($w['uv_index']) }}
                </span>
              @endif
            </div>
            @if(isset($w['precipitation_sum']) && $w['precipitation_sum'] > 0)
              <div class="weather-bar-wrap" title="{{ $w['precipitation_sum'] }} mm rain">
                <div class="weather-bar-fill" style="width:{{ min(100, $w['precipitation_sum'] * 10) }}%"></div>
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Flights (selectable) --}}
  @if(!empty($plan['flights']))
  <div class="block reveal" id="flightsBlock">
    <h2>Best flights &amp; prices</h2>
    <p class="lead">Select flights to include — tap to check live prices.</p>
    <div class="grid grid-2">
      @foreach($plan['flights'] as $fIdx => $f)
        <div class="card selectable{{ $fIdx < 2 ? ' selected' : '' }} flight-card" data-flight-idx="{{ $fIdx }}" data-flight-price="{{ (float)($f['price'] ?? 0) }}">
          <div style="font-weight:600;font-family:'Poppins';display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            {{ $f['from'] ?? '' }} → {{ $f['to'] ?? '' }}
            @if(!empty($f['type']))<span class="tag {{ str_contains(strtolower($f['type'] ?? ''),'non-stop') || str_contains(strtolower($f['type'] ?? ''),'direct') ? 'tag-direct' : 'tag-stop' }}">{{ $f['type'] }}</span>@endif
          </div>
          <div class="muted" style="font-size:13px;margin:6px 0">{{ $f['airlines'] ?? '' }}{{ !empty($f['duration']) ? ' · '.$f['duration'] : '' }}</div>
          <div style="font-size:20px;font-weight:600;color:var(--accent);font-family:'Poppins'">{!! $money($f['price'] ?? 0) !!}</div>
          <span class="data-badge">{{ $statusText($f['price_status'] ?? 'estimated') }}</span>
          <a class="mlink mt" target="_blank" rel="noopener" href="{{ $flightLink('flights '.($f['from']??'').' to '.($f['to']??'')) }}">Check fares ↗</a>
        </div>
      @endforeach
      {{-- Flights total --}}
      @php $flightTotal = array_sum(array_column($plan['flights'], 'price')); @endphp
      <div class="card flight-total" id="flightTotalCard">
        <div style="font-weight:600;font-family:'Poppins'">Flights total</div>
        <div class="muted" style="font-size:13px;margin:6px 0">Selected air legs combined</div>
        <div style="font-size:20px;font-weight:600;color:var(--accent);font-family:'Poppins'" id="flightTotalAmt">{!! $money($flightTotal) !!}</div>
        <span class="tag">Book early for best rates</span>
      </div>
    </div>
  </div>
  @endif

  {{-- Transport --}}
  @if(!empty($plan['transport']))
  <div class="block reveal">
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

  {{-- Day by day (enhanced with photo carousels, drag, review callouts) --}}
  @if(!empty($plan['days']))
  <div class="block reveal">
    <h2>Day-by-day</h2>
    <p class="lead">{{ count($plan['days']) }} days — drag to reorder, paced to your style.</p>
    <div id="daysContainer">
      @php $seenPlaces = []; $dayPhotos = []; @endphp
      @foreach($plan['days'] as $dIdx => $d)
        @php
          $gradClass = 'g'.($dIdx % 5);
          /* Collect photos for this day from all items */
          $dayPhotoList = [];
          foreach(($d['items'] ?? []) as $it){
            $pk = $it['place_key'] ?? null;
            if($pk && !empty($placesData[$pk]['photos']) && $placesApiKey){
              foreach(array_slice($placesData[$pk]['photos'], 0, 2) as $ph){
                $dayPhotoList[] = $photoUrl($ph, 400);
              }
            }
          }
          $dayPhotos[$dIdx] = $dayPhotoList;
          /* Get first review from day items */
          $dayReview = '';
          foreach(($d['items'] ?? []) as $it){
            $pk = $it['place_key'] ?? null;
            if($pk && !empty($placesData[$pk]['reviews'][0]['text'])){
              $dayReview = $placesData[$pk]['reviews'][0]['text'];
              break;
            }
          }
          /* Day cost total */
          $dayCost = 0;
          foreach(($d['items'] ?? []) as $it){ $dayCost += (float)($it['cost'] ?? 0); }
        @endphp
        <div class="day" draggable="true" data-day-idx="{{ $dIdx }}">
          <div class="side {{ $gradClass }}">
            <span class="drag-handle">⠿</span>
            <div class="dt">Day {{ $d['day'] ?? $loop->iteration }}{{ !empty($d['date']) ? ' · '.$d['date'] : '' }}</div>
            <div class="city">{{ $d['city'] ?? '' }}</div>
            <div class="badges" style="display:flex;flex-direction:column;gap:6px;align-items:flex-start">
              @foreach(($d['tags'] ?? []) as $tag)<span class="bdg">{{ $tag }}</span>@endforeach
            </div>
          </div>
          <div class="body">
            <h3>{{ $d['title'] ?? '' }}</h3>
            <p class="muted" style="font-size:13px;margin:0 0 8px">{!! $fmt($d['summary'] ?? '') !!}</p>
            {{-- Photo carousel --}}
            @if(count($dayPhotoList) > 0)
            <div class="photo-carousel">
              @foreach($dayPhotoList as $phIdx => $phUrl)
                <div class="c-item" onclick="openLightbox({{ $dIdx }}, {{ $phIdx }})">
                  <img src="{{ $phUrl }}" alt="Trip photo" loading="lazy" onerror="this.style.display='none'">
                </div>
              @endforeach
            </div>
            @endif
            {{-- Activity items --}}
            @foreach(($d['items'] ?? []) as $it)
              <div class="it">
                <div class="time">{{ $it['time'] ?? '' }}</div>
                <div class="what">
                  {!! $fmt($it['activity'] ?? '') !!}
                  @if(($it['place_key'] ?? null) && !empty($placesData[$it['place_key']]['rating']))
                    <span class="place-rating">★ {{ number_format($placesData[$it['place_key']]['rating'], 1) }}{{ !empty($placesData[$it['place_key']]['reviews_count']) ? ' ('.number_format($placesData[$it['place_key']]['reviews_count']).')' : '' }}</span>
                  @endif
                  @if(!empty($it['note']))<div class="note">{!! $fmt($it['note']) !!}</div>@endif
                  @if(!empty($it['map_query']))<a class="mlink" target="_blank" rel="noopener" href="{{ $gmaps($it['map_query']) }}"><x-icon name="location_on" :size="14" /> Map</a>@endif
                  @if(isset($it['cost']) || !empty($it['entry_fee_status']))
                    <span class="fee-badge {{ ($it['entry_fee_status'] ?? '') === 'free' ? 'fee-free' : '' }}">{{ $statusText($it['entry_fee_status'] ?? (((float)($it['cost'] ?? 0)) <= 0 ? 'free' : 'estimated')) }}</span>
                  @endif
                </div>
                <div class="cost">{!! !empty($it['cost']) ? $money($it['cost']) : (isset($it['cost']) ? 'Free' : '') !!}</div>
              </div>
            @endforeach
            {{-- Review callout --}}
            @if($dayReview)
              <div class="review-callout"><b>From the reviews —</b> {{ \Illuminate\Support\Str::limit($dayReview, 160) }}</div>
            @endif
            {{-- Day cost --}}
            @if($dayCost > 0)
              <div style="margin-top:8px;font-size:13px;color:var(--muted)">Day cost: <strong style="color:var(--ink)">{!! $money($dayCost) !!}</strong></div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Destinations — city cards with spots, hotels, costs --}}
  <div class="block reveal">
    <h2>Destinations — spots, hotels &amp; costs</h2>
    <p class="lead">Every city with top-rated spots and hotels. Select what to include — totals update live below.</p>
    @php $cityIdx = 0; @endphp
    @foreach($plan['route'] ?? [] as $stop)
      @php
        $cityName = $stop['name'] ?? '';
        $cityHotels = $hotelsByCity[$cityName] ?? [];
        $citySpots = $spotsByCity[$cityName] ?? [];
        $cityNights = $stop['nights'] ?? 1;
        $cityColor = $cityColors[$cityIdx % count($cityColors)];
        if(empty($cityHotels) && empty($citySpots)) { $cityIdx++; continue; }
      @endphp
      <div class="city-card" id="city{{ $cityIdx }}">
        <div class="city-head" style="background:linear-gradient(135deg,{{ $cityColor }},#191a23)">
          <div>
            <h3>{{ $cityName }}</h3>
            <div class="nights">{{ $cityNights }} night{{ $cityNights != 1 ? 's' : '' }}</div>
          </div>
          <button class="city-map-btn" onclick="flyCity({{ $cityIdx }})"><x-icon name="location_on" :size="16" /> Show on map</button>
        </div>
        <div class="city-body">
          {{-- Cost chips --}}
          <div class="cost-chips">
            <div class="cost-chip">🏨 Hotel/night<b>{!! $money($avgHotelNight) !!}</b></div>
            <div class="cost-chip">🍜 Food/day<b>{!! $money($avgFoodDay) !!}</b></div>
            <div class="cost-chip">🎟️ Activities<b>{!! $money($avgActivities) !!}</b></div>
            <div class="cost-chip">🚆 Transport<b>{!! $money($avgTransport) !!}</b></div>
          </div>
          {{-- Hotels --}}
          @if(!empty($cityHotels))
          <div class="subh">🏨 Best-rated hotels near the spots</div>
          <div class="grid grid-3">
            @foreach($cityHotels as $hIdx => $h)
              <div class="card hcard selectable hotel-selectable{{ $hIdx === 0 ? ' selected' : '' }}" data-hotel-city="{{ $cityName }}" data-hotel-idx="{{ $hIdx }}" data-hotel-price="{{ (float)($h['price_per_night'] ?? 0) }}" data-hotel-nights="{{ (int)($h['nights'] ?? $cityNights) }}">
                <div class="hn">{{ $h['name'] ?? '' }}</div>
                <div class="muted" style="font-size:12.5px">{{ $cityName }}{{ !empty($h['area']) ? ' · '.$h['area'] : '' }}</div>
                @if(!empty($h['rating']))<div class="rt">★ {{ number_format($h['rating'],1) }}</div>@endif
                <div class="hp">{!! $money($h['price_per_night'] ?? 0) !!} <small>/ night · {{ $h['nights'] ?? $cityNights }} nights</small></div>
                <a class="mlink mt" target="_blank" rel="noopener" href="{{ $hotelLink($h['booking_query'] ?? (($h['name']??'').' '.($h['city']??''))) }}">Check rates ↗</a>
                @if(($h['place_key'] ?? null) && ($placesData[$h['place_key']] ?? null))
                  @php $place = $placesData[$h['place_key']]; @endphp
                  @if(!empty($place['rating']))
                  <div class="place-stars" style="margin-top:8px">
                    @for($i = 1; $i <= 5; $i++){{ $i <= round($place['rating']) ? '★' : '☆' }}@endfor
                    <strong>{{ number_format($place['rating'], 1) }}</strong>
                    @if(!empty($place['reviews_count']))<span class="muted">({{ number_format($place['reviews_count']) }})</span>@endif
                  </div>
                  @endif
                  @if(!empty($place['photos']) && $placesApiKey)
                  <div class="place-photos">
                    @foreach(array_slice($place['photos'], 0, 3) as $photo)
                      <img src="{{ $photoUrl($photo, 400) }}" alt="{{ $place['name'] ?? '' }}" loading="lazy">
                    @endforeach
                  </div>
                  @endif
                  @if(!empty($place['reviews']))
                    @foreach(array_slice($place['reviews'], 0, 1) as $rev)
                    <div class="place-review">
                      <div class="review-meta">
                        <span class="review-author">{{ $rev['author'] ?? '' }}</span>
                        <span class="place-stars">@for($i = 1; $i <= 5; $i++){{ $i <= ($rev['rating'] ?? 0) ? '★' : '☆' }}@endfor</span>
                      </div>
                      <div class="review-text">{{ \Illuminate\Support\Str::limit($rev['text'] ?? '', 100) }}</div>
                    </div>
                    @endforeach
                  @endif
                @endif
              </div>
            @endforeach
          </div>
          @endif
          {{-- Spots --}}
          @if(!empty($citySpots))
          <div class="subh">⭐ Spots to visit</div>
          @foreach($citySpots as $sIdx => $spotData)
            @php $spot = $spotData['place']; $spotItem = $spotData['item']; @endphp
            <div class="spot-card">
              {{-- Spot photo gallery --}}
              @if(!empty($spot['photos']) && $placesApiKey)
              <div class="spot-gallery">
                @foreach(array_slice($spot['photos'], 0, 3) as $spPhoto)
                  <div class="sp">
                    <img src="{{ $photoUrl($spPhoto, 400) }}" alt="{{ $spot['name'] ?? '' }}" loading="lazy">
                  </div>
                @endforeach
              </div>
              @endif
              <h4>
                {{ $spot['name'] ?? ($spotItem['activity'] ?? '') }}
                <span class="entry-cost">{{ ((float)($spotItem['cost'] ?? 0)) <= 0 ? 'Free' : 'Entry '.$sym.number_format((float)($spotItem['cost'] ?? 0)) }}</span>
              </h4>
              @if(!empty($spot['rating']))
                <div class="spot-rating">★ {{ number_format($spot['rating'], 1) }} Google rating{{ !empty($spot['reviews_count']) ? ' ('.number_format($spot['reviews_count']).')' : '' }}</div>
              @endif
              @if(!empty($spot['reviews'][0]['text']))
                <div class="spot-review"><b>From the reviews —</b> {{ \Illuminate\Support\Str::limit($spot['reviews'][0]['text'], 160) }}</div>
              @endif
              @if(!empty($spot['maps_url']))
                <a class="mlink" target="_blank" rel="noopener" href="{{ $spot['maps_url'] }}"><x-icon name="location_on" :size="14" /> Maps &amp; live reviews</a>
              @elseif(!empty($spotItem['map_query']))
                <a class="mlink" target="_blank" rel="noopener" href="{{ $gmaps($spotItem['map_query']) }}"><x-icon name="location_on" :size="14" /> Open in Google Maps</a>
              @endif
            </div>
          @endforeach
          @endif
        </div>
      </div>
      @php $cityIdx++; @endphp
    @endforeach
  </div>

  {{-- Tips --}}
  @if(!empty($plan['tips']))
  <div class="block reveal">
    <h2>Good to know</h2>
    <ul>@foreach($plan['tips'] as $tip)<li style="margin:6px 0">{!! $fmt($tip) !!}</li>@endforeach</ul>
  </div>
  @endif

  {{-- Packing --}}
  @if(!empty($plan['packing']))
  <div class="block reveal">
    <h2>Packing</h2>
    <div class="grid grid-2">
      @foreach($plan['packing'] as $pk)
        <div class="card">
          <h3>{{ $pk['title'] ?? '' }}</h3>
          <ul>@foreach(($pk['items'] ?? []) as $it)<li style="margin:5px 0;font-size:14px">{!! $fmt($it) !!}</li>@endforeach</ul>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Culture --}}
  @if(!empty($plan['culture']))
  <div class="block reveal">
    <h2>Culture — do's &amp; don'ts</h2>
    <div class="grid grid-2">
      @foreach($plan['culture'] as $c)
        <div class="card culture-card">
          <h3>{{ $c['place'] ?? '' }}</h3>
          <div class="culture-cols">
            @if(!empty($c['dos']))
            <div class="culture-col">
              <div class="subh" style="color: var(--do-text); display: flex; align-items: center; gap: 4px;">Do</div>
              <ul class="culture-list">
                @foreach($c['dos'] as $d)
                <li class="culture-item culture-item-do">
                  <x-icon name="check_circle" :size="18" />
                  <span>{!! $fmt($d) !!}</span>
                </li>
                @endforeach
              </ul>
            </div>
            @endif
            @if(!empty($c['donts']))
            <div class="culture-col">
              <div class="subh" style="color: var(--dont-text); display: flex; align-items: center; gap: 4px;">Don't</div>
              <ul class="culture-list">
                @foreach($c['donts'] as $d)
                <li class="culture-item culture-item-dont">
                  <x-icon name="cancel" :size="18" />
                  <span>{!! $fmt($d) !!}</span>
                </li>
                @endforeach
              </ul>
            </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Pre-trip countdown --}}
  @if(!empty($plan['countdown']))
  <div class="block reveal">
    <h2>Pre-trip countdown</h2>
    <div class="timeline">
      @foreach($plan['countdown'] as $c)
        <div><b>{{ $c['when'] ?? '' }}</b><small>{!! $fmt($c['tasks'] ?? '') !!}</small></div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- AI chat --}}
  <div class="block reveal">
    <h2>Ask about this trip</h2>
    <p class="lead">Live answers grounded in Google Search &amp; Maps — "make day 3 cheaper", "best area to stay", "weather in October?".</p>
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
  <div class="block reveal">
    <h2>Sources</h2>
    <p class="lead">Live references the AI used to ground this plan.</p>
    <div class="source-list">
      @foreach($trip->grounding as $g)
        @if(!empty($g['uri']))<a target="_blank" rel="noopener" href="{{ $g['uri'] }}">{!! $g['type']==='maps' ? view('components.icon', ['name' => 'location_on', 'size' => 14])->render() : view('components.icon', ['name' => 'link', 'size' => 14])->render() !!} {{ \Illuminate\Support\Str::limit($g['title'] ?: $g['uri'], 50) }}</a>@endif
      @endforeach
    </div>
  </div>
  @endif

  {{-- Likes & comments --}}
  <div class="block reveal" id="comments">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <button class="like-btn {{ $trip->isLikedBy(auth()->user())?'liked':'' }}" id="likeBtn" {{ auth()->check()?'':'disabled' }}>
        <x-icon name="favorite" :size="20" /> <span id="likeCount">{{ $trip->likes()->count() }}</span>
      </button>
      <span class="muted" style="display:flex;align-items:center;gap:4px"><x-icon name="chat_bubble" :size="18" /> {{ $trip->comments()->count() }}</span>
      @guest<span class="muted" style="font-size:13px">· <a href="{{ route('login') }}">Log in</a> to like &amp; comment</span>@endguest
    </div>
    <h2 style="margin-top:24px;margin-bottom:16px;font-size:18px">Comments</h2>
    @auth
      <form method="POST" action="{{ route('trip.comment',$trip) }}" class="comment-form">
        @csrf<input type="text" name="body" placeholder="Add a comment…" maxlength="1000" required><button class="btn btn-primary btn-sm">Post</button>
      </form>
    @endauth
    @forelse($trip->comments()->with('user')->latest()->get() as $c)
      <div class="comment-item">
        <img src="{{ $c->user->avatar() }}" alt="{{ $c->user->name }}" width="34" height="34">
        <div>
          <div class="comment-head">
            <a href="{{ route('profile',$c->user) }}">{{ $c->user->name }}</a>
            <span>{{ $c->created_at->diffForHumans() }}</span>
          </div>
          <div class="comment-body">{{ $c->body }}</div>
        </div>
      </div>
    @empty
      <p class="muted" style="padding:16px 0;text-align:center">No comments yet. Be the first.</p>
    @endforelse
  </div>

  {{-- Share / actions --}}
  <div class="block reveal">
    <h2>Share &amp; save</h2>
    <div class="row row-2">
      <div class="field">
        <label>Shareable link</label>
        <div style="display:flex;gap:8px">
          <input type="text" id="shareUrl" value="{{ route('trip.show', $trip) }}" readonly>
          <button class="btn btn-ghost" onclick="copyTripUrl()">Copy</button>
        </div>
      </div>
      <div class="field" style="display:flex;align-items:flex-end;gap:10px">
        @if($canManage)
        <a class="btn btn-ghost" href="{{ route('plan.edit', $trip) }}"><x-icon name="edit" :size="16" /> Edit plan</a>
        @endif
        <a class="btn btn-primary" href="{{ route('home') }}">+ New trip</a>
      </div>
    </div>
  </div>
  </div> {{-- End tab-trips --}}

  {{-- Posts Tab --}}
  <div class="tab-content" id="tab-posts" style="display:none">
    @auth
      <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
        <button id="toggle-post-form-btn" class="btn btn-outlined" onclick="toggleForm('post-form-block', 'toggle-post-form-btn', 'Post', 'post_add')">
          <x-icon name="post_add" :size="18" /> New Post
        </button>
      </div>
      <div class="block" id="post-form-block" style="margin-bottom:20px;padding:24px;display:none">
        <h3 style="margin-top:0">Post to this Trip</h3>
        <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" class="post-create-form">
          @csrf
          <input type="hidden" name="trip_id" value="{{ $trip->id }}">
          <input type="hidden" name="type" id="post_type" value="text">
          
          <div class="field" style="margin-bottom:14px">
            <label for="post_title" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Post Title</label>
            <input type="text" name="title" id="post_title" placeholder="Title (e.g. Day 1 at Fushimi Inari!)" required minlength="6" style="width:100%;padding:10px;border-radius:6px;border:1px solid var(--md-outline-variant)">
          </div>
          
          <div class="field" style="margin-bottom:14px">
            <label for="post_body" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Details</label>
            <textarea name="body" id="post_body" rows="3" placeholder="Share your experience..." style="width:100%;resize:vertical;padding:10px;border-radius:6px;border:1px solid var(--md-outline-variant)"></textarea>
          </div>

          <div class="field" style="margin-bottom:14px">
            <label for="post_location" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Location (optional)</label>
            <input type="text" name="location" id="post_location" placeholder="e.g. Kyoto, Japan" style="width:100%;padding:10px;border-radius:6px;border:1px solid var(--md-outline-variant)">
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:18px">
            <div style="display:flex;align-items:center;gap:12px">
              <label class="btn btn-ghost" style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;margin:0">
                <x-icon name="photo_camera" :size="18" />
                <span>Add Photos/Videos</span>
                <input type="file" name="media[]" multiple accept="image/*,video/*" style="display:none" onchange="handlePostMediaSelect(this)">
              </label>
              <span id="media-selected-count" class="muted" style="font-size:13px">No files selected</span>
            </div>
            <button type="submit" class="btn btn-primary">Publish Post</button>
          </div>
        </form>
      </div>
    @else
      <div class="block center" style="margin-bottom:20px;padding:24px">
        <x-icon name="lock" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
        <p class="lead" style="margin:0 0 8px">Log in to post to this Trip</p>
        <a class="btn btn-primary btn-sm" href="{{ route('login') }}">Log In / Register</a>
      </div>
    @endauth

    <div class="posts-feed mt" id="posts-feed-container">
      @forelse($posts as $post)
        @include('partials.post-card', ['post' => $post])
      @empty
        <div class="block center"><x-icon name="article" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" /><p class="lead">No posts yet.</p></div>
      @endforelse
    </div>
  </div>

  {{-- Media Tab --}}
  <div class="tab-content" id="tab-media" style="display:none">
    @auth
      <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
        <button id="toggle-media-form-btn" class="btn btn-outlined" onclick="toggleForm('media-form-block', 'toggle-media-form-btn', 'Media', 'cloud_upload')">
          <x-icon name="cloud_upload" :size="18" /> Upload Media
        </button>
      </div>
      <div class="block" id="media-form-block" style="margin-bottom:20px;padding:24px;display:none">
        <h3 style="margin-top:0">Upload Media to this Trip</h3>
        <form id="media-upload-form" class="media-upload-form" style="border: 2px dashed var(--md-outline-variant); border-radius: 12px; padding: 32px; text-align: center; cursor: pointer; transition: all 0.2s;" ondragover="event.preventDefault(); this.style.borderColor='var(--md-primary)';" ondragleave="this.style.borderColor='var(--md-outline-variant)';" ondrop="handleMediaDrop(event)">
          @csrf
          <input type="hidden" name="mediable_type" value="trip">
          <input type="hidden" name="mediable_id" value="{{ $trip->id }}">
          <x-icon name="cloud_upload" :size="36" style="color:var(--md-primary);margin-bottom:12px;display:block;margin:0 auto 12px" />
          <p class="lead" style="margin:0 0 4px">Drag and drop images or videos here</p>
          <p class="muted" style="font-size:13px;margin:0 0 16px">or click to browse from your device</p>
          <input type="file" name="file" id="media-file-input" accept="image/*,video/*" style="display:none" onchange="uploadMediaFile(this.files[0])">
          <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('media-file-input').click()">Select File</button>
          <div id="upload-status" class="muted" style="font-size:13px;margin-top:12px;display:none">Uploading...</div>
        </form>
      </div>
    @else
      <div class="block center" style="margin-bottom:20px;padding:24px">
        <x-icon name="lock" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
        <p class="lead" style="margin:0 0 8px">Log in to upload media to this Trip</p>
        <a class="btn btn-primary btn-sm" href="{{ route('login') }}">Log In / Register</a>
      </div>
    @endauth

    <div class="media-grid mt" id="media-grid-container">
      @forelse($media as $m)
        <div class="media-item" style="cursor:pointer" onclick="{{ $m->mediable_type === 'App\Models\Post' ? 'openPostViewer('.$m->mediable_id.')' : 'openMediaLightbox(\''.$m->url.'\')' }}">
          @if($m->isVideo())
            <video src="{{ $m->url }}" muted></video>
            <span class="media-play"><x-icon name="play_arrow" :size="32" /></span>
          @else
            <img src="{{ $m->url }}" alt="Travel photo">
          @endif
        </div>
      @empty
        <div class="block center" id="no-media-message"><x-icon name="photo_library" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" /><p class="lead">No media uploaded yet.</p></div>
      @endforelse
    </div>
  </div>

  {{-- Reviews Tab --}}
  <div class="tab-content" id="tab-reviews" style="display:none">
    @auth
      <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
        <button id="toggle-review-form-btn" class="btn btn-outlined" onclick="toggleForm('review-form-block', 'toggle-review-form-btn', 'Review', 'rate_review')">
          <x-icon name="rate_review" :size="18" /> Write Review
        </button>
      </div>
      <div class="block" id="review-form-block" style="margin-bottom:20px;padding:24px;display:none">
        <h3 style="margin-top:0">Write a Review</h3>
        <form action="{{ route('reviews.store') }}" method="POST" class="review-create-form">
          @csrf
          <input type="hidden" name="reviewable_type" value="trip">
          <input type="hidden" name="reviewable_id" value="{{ $trip->id }}">
          
          <div class="field" style="margin-bottom:14px">
            <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Rating</label>
            <div class="rating-stars m3-rating">
              <input type="hidden" name="rating" id="review_rating" value="5">
              @for($i = 1; $i <= 5; $i++)
                <span class="star-btn" data-val="{{ $i }}" onclick="setRating({{ $i }})">
                  <x-icon name="star" :size="28" class="star-icon" />
                </span>
              @endfor
            </div>
          </div>

          <div class="field" style="margin-bottom:14px">
            <label for="review_title" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Review Title (optional)</label>
            <input type="text" name="title" id="review_title" placeholder="e.g. Best itinerary ever!" style="width:100%;padding:10px;border-radius:6px;border:1px solid var(--md-outline-variant)">
          </div>

          <div class="field" style="margin-bottom:14px">
            <label for="review_body" style="font-weight:600;font-size:13px;display:block;margin-bottom:6px">Review Body</label>
            <textarea name="body" id="review_body" rows="4" placeholder="How was your trip? What did you think of the pacing and spots?..." required style="width:100%;resize:vertical;padding:10px;border-radius:6px;border:1px solid var(--md-outline-variant)"></textarea>
          </div>

          <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
      </div>
    @else
      <div class="block center" style="margin-bottom:20px;padding:24px">
        <x-icon name="lock" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
        <p class="lead" style="margin:0 0 8px">Log in to leave a review</p>
        <a class="btn btn-primary btn-sm" href="{{ route('login') }}">Log In / Register</a>
      </div>
    @endauth

    <div class="reviews-list">
      @forelse($reviews as $review)
        <div class="review-card block" style="margin-bottom:16px;padding:20px">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
            <img src="{{ $review->user->avatar() }}" alt="{{ $review->user->name }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover" width="40" height="40">
            <div style="flex:1">
              <div style="font-weight:600;font-size:15px">{{ $review->user->name }}</div>
              <div class="place-stars" style="display:flex;align-items:center;gap:2px">
                @for($i = 1; $i <= 5; $i++)
                  <x-icon name="star" :size="16" style="color:{{ $i <= $review->rating ? 'var(--md-primary)' : 'var(--md-outline-variant)' }}" />
                @endfor
                <strong style="margin-left:6px;font-size:13px;color:var(--md-on-surface-variant)">{{ $review->rating }}/5</strong>
              </div>
            </div>
            <span class="muted" style="font-size:12px">{{ $review->created_at->diffForHumans() }}</span>
          </div>
          @if($review->title)<h4 style="margin:0 0 8px;font-size:16px">{{ $review->title }}</h4>@endif
          <p style="margin:0;font-size:14px;line-height:1.5;color:var(--md-on-surface)">{{ $review->body }}</p>
        </div>
      @empty
        <div class="block center" id="no-reviews-message"><x-icon name="star" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" /><p class="lead">No reviews yet. Be the first to leave one!</p></div>
      @endforelse
    </div>
  </div>

</div>

{{-- Lightbox --}}
<div id="lightbox" class="lightbox" onclick="closeLightbox(event)">
  <div class="lightbox-close" onclick="closeLightbox(event)">&times;</div>
  <button class="lightbox-nav lightbox-prev" onclick="lightboxPrev(event)">&#10094;</button>
  <img id="lightboxImg" class="lightbox-img" src="" alt="Trip photo">
  <button class="lightbox-nav lightbox-next" onclick="lightboxNext(event)">&#10095;</button>
  <div id="lightboxCount" class="lightbox-count"></div>
</div>

{{-- Estimation bar --}}
<div class="est-bar" id="estBar">
  <button class="est-toggle-btn" id="estToggle">▲ Estimate</button>
  <div class="est-bar-content">
    <div class="wrap">
      <div class="est-items" id="estItems">
        <div class="est-item"><x-icon name="hotel" /> <b id="estAccom">—</b></div>
        <div class="est-item"><x-icon name="restaurant" /> <b id="estFood">—</b></div>
        <div class="est-item"><x-icon name="confirmation_number" /> <b id="estAct">—</b></div>
        <div class="est-item"><x-icon name="train" /> <b id="estTrans">—</b></div>
        <div class="est-item"><x-icon name="flight" /> <b id="estFlight">—</b></div>
      </div>
      <div class="est-total">
        <span id="estGrand">—</span>
        <span class="est-fit ok" id="estFit">Within budget</span>
      </div>
    </div>
  </div>
</div>

@push('scripts')
{{-- Full plan data as JSON for JS --}}
<script type="application/json" id="planJson">{!! json_encode($plan) !!}</script>
<div id="page-data"
     data-route="{!! e(json_encode($route->map(fn($r)=>['name'=>$r['name'],'lat'=>(float)$r['lat'],'lng'=>(float)$r['lng']]))) !!}"
     data-route-options="{!! e(json_encode($plan['route_options'] ?? [])) !!}"
     data-cur-trip="{!! e(json_encode($cur)) !!}"
     data-budget-total="{{ $total }}"
     data-trip-budget="{{ $trip->budget_total }}"
     data-trip-days="{{ $trip->days }}"
     data-trip-nights="{{ $trip->nights }}"
     data-trip-travelers="{{ $trip->travelers }}"
     data-share-token="{{ $trip->share_token }}"
     data-photo-key="{!! e($placesApiKey ?: '') !!}"
     data-chat-url="{{ route('trip.chat', $trip) }}"
     data-like-url="{{ route('trip.like', $trip) }}"
     data-trip-id="{{ $trip->id }}"
     style="display:none"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const _pd = document.getElementById('page-data').dataset;
const PLAN = JSON.parse(document.getElementById('planJson').textContent);
const ROUTE = JSON.parse(_pd.route);
const ROUTE_OPTIONS = JSON.parse(_pd.routeOptions);
const TRIP_BUDGET = parseFloat(_pd.tripBudget);
const PLAN_TOTAL = parseFloat(_pd.budgetTotal);
const TRIP_DAYS = parseInt(_pd.tripDays, 10);
const TRIP_NIGHTS = parseInt(_pd.tripNights, 10);
const TRIP_TRAVELLERS = parseInt(_pd.tripTravelers, 10);
const SHARE_TOKEN = _pd.shareToken;
const PHOTO_KEY = _pd.photoKey;
const CUR_TRIP = JSON.parse(_pd.curTrip);
const CUR_SYM_MAP = {'USD':'$','INR':'₹','EUR':'€','GBP':'£','AED':'AED ','SGD':'S$','JPY':'¥'};

/* ===== STATE ===== */
let state = {
  selectedRoute: 0,
  budget: TRIP_BUDGET,
  days: TRIP_DAYS,
  nights: TRIP_NIGHTS,
  travelers: TRIP_TRAVELLERS,
  selectedFlights: [],
  currency: CUR_TRIP,
  estBarVisible: true
};
/* Restore from localStorage */
try {
  const saved = localStorage.getItem('trip-state-' + SHARE_TOKEN);
  if (saved) { const s = JSON.parse(saved); Object.assign(state, s); }
} catch(e) {}
function saveState() {
  try { localStorage.setItem('trip-state-' + SHARE_TOKEN, JSON.stringify(state)); } catch(e) {}
}

/* ===== CURRENCY ===== */
const curSel = document.getElementById('currencySelect');
let curRate = 1, baseRate = 1;

async function fetchFxRate(currency) {
  if (currency === 'USD') return 1;
  try { const res = await fetch('/api/fx/' + currency); const d = await res.json(); if (d.rate) return d.rate; } catch(e) {}
  return null;
}
function fmtMoney(usd) {
  const sym = CUR_SYM_MAP[state.currency] ?? (state.currency + ' ');
  const converted = baseRate ? (usd / baseRate) * curRate : usd;
  return sym + Math.round(converted).toLocaleString();
}
function convertCurrency() {
  const sym = CUR_SYM_MAP[curSel?.value] ?? ((curSel?.value || state.currency) + ' ');
  state.currency = curSel?.value || state.currency;
  document.querySelectorAll('.money[data-amt]').forEach(el => {
    const origAmt = parseFloat(el.dataset.amt);
    if (isNaN(origAmt)) return;
    const srcCur = el.dataset.cur || CUR_TRIP;
    const usdAmt = baseRate && srcCur !== 'USD' ? origAmt / baseRate : origAmt;
    const converted = usdAmt * curRate;
    el.textContent = sym + Math.round(converted).toLocaleString();
  });
  updateBudgetDisplay();
  recalcTotal();
  saveState();
}
curSel?.addEventListener('change', async () => {
  const r = await fetchFxRate(curSel.value);
  if (r !== null) { curRate = r; convertCurrency(); }
});


/* ===== BUDGET BAR WIDTHS ===== */
document.querySelectorAll('.bbar-fill').forEach(el => { el.style.width = el.dataset.pct + '%'; });

/* ===== BUDGET SLIDER ===== */
const budgetSlider = document.getElementById('budgetSlider');
const budgetDisplay = document.getElementById('budgetDisplay');
const fitBanner = document.getElementById('fitBanner');
if (budgetSlider) {
  budgetSlider.value = state.budget;
  budgetSlider.addEventListener('input', () => {
    state.budget = parseFloat(budgetSlider.value);
    updateBudgetDisplay();
    saveState();
  });
}
function updateBudgetDisplay() {
  if (budgetDisplay) budgetDisplay.innerHTML = fmtMoney(state.budget);
  recalcTotal();
}

/* ===== DAY STEPPER ===== */
const dayValue = document.getElementById('dayValue');
const nightValue = document.getElementById('nightValue');
if (dayValue) dayValue.textContent = state.days;
if (nightValue) nightValue.textContent = state.nights;
document.getElementById('dayMinus')?.addEventListener('click', () => {
  if (state.days > 0) {
    state.days--;
    state.nights = Math.min(state.nights, state.days + 1);
    state.nights = Math.max(state.nights, Math.max(0, state.days - 1));
    dayValue.textContent = state.days;
    nightValue.textContent = state.nights;
    saveState();
  }
});
document.getElementById('dayPlus')?.addEventListener('click', () => {
  state.days++;
  state.nights = Math.min(state.nights, state.days + 1);
  state.nights = Math.max(state.nights, Math.max(0, state.days - 1));
  dayValue.textContent = state.days;
  nightValue.textContent = state.nights;
  saveState();
});

/* ===== NIGHT STEPPER ===== */
document.getElementById('nightMinus')?.addEventListener('click', () => {
  if (state.nights > Math.max(0, state.days - 1)) { state.nights--; nightValue.textContent = state.nights; saveState(); }
});
document.getElementById('nightPlus')?.addEventListener('click', () => {
  if (state.nights < state.days + 1) { state.nights++; nightValue.textContent = state.nights; saveState(); }
});

/* ===== TRAVELLER STEPPER ===== */
const travValue = document.getElementById('travValue');
if (travValue) travValue.textContent = state.travelers;
document.getElementById('travMinus')?.addEventListener('click', () => {
  if (state.travelers > 1) { state.travelers--; travValue.textContent = state.travelers; saveState(); }
});
document.getElementById('travPlus')?.addEventListener('click', () => {
  state.travelers++; travValue.textContent = state.travelers; saveState();
});

/* ===== MAP ===== */
let map, routePolylines = [], cityMarkers = [];
function initMap() {
  if (!ROUTE.length || !window.L) return;
  const mapEl = document.getElementById('map');
  if (!mapEl) return;
  map = L.map('map', {scrollWheelZoom: false}).setView([ROUTE[0].lat, ROUTE[0].lng], 4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:18, attribution:'© OpenStreetMap'}).addTo(map);
  const pts = ROUTE.map(r => [r.lat, r.lng]);
  ROUTE.forEach((r, i) => {
    const m = L.marker([r.lat, r.lng]).addTo(map).bindPopup(`<b>${i+1}. ${r.name}</b>`);
    cityMarkers.push(m);
  });
  if (ROUTE_OPTIONS.length) {
    ROUTE_OPTIONS.forEach((opt, idx) => {
      const seq = opt.sequence || [];
      const seqPts = seq.map(city => {
        const match = ROUTE.find(r => r.name.toLowerCase() === city.toLowerCase());
        return match ? [match.lat, match.lng] : null;
      }).filter(Boolean);
      if (seqPts.length > 1) {
        const poly = L.polyline(seqPts, {
          color: idx === state.selectedRoute ? '#0286fe' : '#a8a29e',
          weight: idx === state.selectedRoute ? 4 : 2.5,
          opacity: idx === state.selectedRoute ? 1 : 0.5
        }).addTo(map);
        routePolylines.push(poly);
      } else { routePolylines.push(null); }
    });
  } else {
    L.polyline(pts, {color:'#0286fe', weight:3.5}).addTo(map);
  }
  map.fitBounds(L.latLngBounds(pts).pad(0.25));
}
/* Defer map init until after reveal animation so container has dimensions */
const mapContainer = document.getElementById('map')?.closest('.reveal');
if (mapContainer) {
  const mapObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        initMap();
        mapObs.unobserve(entry.target);
      }
    });
  }, {threshold: 0.01});
  mapObs.observe(mapContainer);
} else {
  initMap();
}
function flyCity(ci) {
  const card = document.getElementById('city' + ci);
  if (ROUTE[ci]) {
    map?.flyTo([ROUTE[ci].lat, ROUTE[ci].lng], 8, {duration:1});
    cityMarkers[ci]?.openPopup();
  }
  if (card) card.scrollIntoView({behavior:'smooth', block:'center'});
}

/* ===== ROUTE TABS & CARDS ===== */
function selectRoute(idx) {
  state.selectedRoute = idx;
  /* Update tab buttons */
  document.querySelectorAll('#routeTabs button').forEach(b => b.classList.toggle('on', parseInt(b.dataset.routeIdx) === idx));
  /* Update route cards */
  document.querySelectorAll('.route-card').forEach(c => {
    c.classList.toggle('route-active', parseInt(c.dataset.routeIdx) === idx);
  });
  /* Update map polylines */
  routePolylines.forEach((poly, i) => {
    if (!poly) return;
    if (i === idx) { poly.setStyle({color:'#0286fe', weight:4, opacity:1}); poly.bringToFront(); }
    else { poly.setStyle({color:'#a8a29e', weight:2.5, opacity:0.5}); }
  });
  saveState();
}
document.querySelectorAll('#routeTabs button').forEach(btn => {
  btn.addEventListener('click', () => selectRoute(parseInt(btn.dataset.routeIdx)));
});
document.querySelectorAll('.route-card').forEach(card => {
  card.addEventListener('click', () => selectRoute(parseInt(card.dataset.routeIdx)));
});
/* Restore route */
if (state.selectedRoute > 0) selectRoute(state.selectedRoute);

/* ===== FLIGHT SELECTION ===== */
function initFlights() {
  const cards = document.querySelectorAll('.flight-card');
  /* Default: select first 2 if no saved state */
  if (state.selectedFlights.length === 0 && cards.length > 0) {
    cards.forEach((c, i) => { if (i < 2) state.selectedFlights.push(i); });
  }
  cards.forEach(card => {
    const idx = parseInt(card.dataset.flightIdx);
    card.classList.toggle('selected', state.selectedFlights.includes(idx));
    card.addEventListener('click', (e) => {
      if (e.target.tagName === 'A') return; /* don't toggle on link click */
      const i = state.selectedFlights.indexOf(idx);
      if (i >= 0) state.selectedFlights.splice(i, 1); else state.selectedFlights.push(idx);
      card.classList.toggle('selected');
      saveState();
      recalcTotal();
    });
  });
}
initFlights();

/* ===== HOTEL SELECTION ===== */
document.querySelectorAll('.hotel-selectable').forEach(card => {
  card.addEventListener('click', (e) => {
    if (e.target.tagName === 'A') return;
    /* Deselect siblings in same city */
    const city = card.dataset.hotelCity;
    document.querySelectorAll(`.hotel-selectable[data-hotel-city="${city}"]`).forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    saveState();
    recalcTotal();
  });
});

/* ===== DRAG & DROP DAYS ===== */
const daysContainer = document.getElementById('daysContainer');
if (daysContainer) {
  let dragSrc = null;
  daysContainer.querySelectorAll('.day[draggable]').forEach(day => {
    day.addEventListener('dragstart', (e) => {
      dragSrc = day;
      day.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', day.dataset.dayIdx);
    });
    day.addEventListener('dragend', () => { day.classList.remove('dragging', 'drag-over'); });
    day.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      if (day !== dragSrc) day.classList.add('drag-over');
    });
    day.addEventListener('dragleave', () => { day.classList.remove('drag-over'); });
    day.addEventListener('drop', (e) => {
      e.preventDefault();
      day.classList.remove('drag-over');
      if (day !== dragSrc && dragSrc) {
        const parent = day.parentNode;
        const allDays = [...parent.children];
        const fromIdx = allDays.indexOf(dragSrc);
        const toIdx = allDays.indexOf(day);
        if (fromIdx < toIdx) parent.insertBefore(dragSrc, day.nextSibling);
        else parent.insertBefore(dragSrc, day);
      }
    });
  });
}

/* ===== LIGHTBOX ===== */
const allDayPhotos = @json($dayPhotos ?? new \stdClass());
let lbImages = [], lbIndex = 0;
function openLightbox(dayIdx, idx) {
  lbImages = allDayPhotos[dayIdx] || [];
  lbIndex = idx || 0;
  updateLightbox();
  document.getElementById('lightbox').classList.add('on');
  document.body.style.overflow = 'hidden';
}
function closeLightbox(e) {
  if (e && e.target !== e.currentTarget && !e.target.classList.contains('lightbox-close')) return;
  document.getElementById('lightbox').classList.remove('on');
  document.body.style.overflow = '';
}
function updateLightbox() {
  document.getElementById('lightboxImg').src = lbImages[lbIndex] || '';
  document.getElementById('lightboxCount').textContent = (lbIndex + 1) + ' / ' + lbImages.length;
}
function lightboxPrev(e) { e.stopPropagation(); lbIndex = (lbIndex - 1 + lbImages.length) % lbImages.length; updateLightbox(); }
function lightboxNext(e) { e.stopPropagation(); lbIndex = (lbIndex + 1) % lbImages.length; updateLightbox(); }
document.addEventListener('keydown', (e) => {
  if (!document.getElementById('lightbox').classList.contains('on')) return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowLeft') lightboxPrev({stopPropagation(){}});
  if (e.key === 'ArrowRight') lightboxNext({stopPropagation(){}});
});

/* ===== ESTIMATION BAR ===== */
const estBar = document.getElementById('estBar');
let estHidden = false;
function toggleEstBar() {
  estHidden = !estHidden;
  estBar.classList.toggle('visible', !estHidden);
  document.getElementById('estToggle').textContent = estHidden ? '▲ Estimate' : '▼ Hide';
  document.body.classList.toggle('has-est-bar', !estHidden);
}
/* Click on toggle button — stop propagation so document click handler doesn't interfere */
document.getElementById('estToggle').addEventListener('click', function(e) {
  e.stopPropagation();
  toggleEstBar();
});
function recalcTotal() {
  /* Accommodation: sum selected hotels */
  let accom = 0;
  document.querySelectorAll('.hotel-selectable.selected').forEach(c => {
    accom += parseFloat(c.dataset.hotelPrice || 0) * parseInt(c.dataset.hotelNights || 1);
  });
  /* Flights: sum selected */
  let flights = 0;
  document.querySelectorAll('.flight-card.selected').forEach(c => {
    flights += parseFloat(c.dataset.flightPrice || 0);
  });
  /* Update flight total card */
  const ftEl = document.getElementById('flightTotalAmt');
  if (ftEl) ftEl.innerHTML = '<span class="money" data-amt="' + flights + '">' + fmtMoney(flights).replace(/[^\d.,]/, '') + '</span>';

  /* Food: avg × days */
  const food = {{ $avgFoodDay }} * state.days;
  /* Activities + transport from plan budget (proportional to days) */
  const act = {{ ($budget['activities'] ?? 0) }};
  const trans = {{ ($budget['local_transport'] ?? 0) + ($budget['intercity_transport'] ?? 0) }};
  const grand = accom + food + act + trans + flights;

  /* Update bar */
  const fmt = (v) => fmtMoney(v);
  document.getElementById('estAccom').textContent = fmt(accom);
  document.getElementById('estFood').textContent = fmt(food);
  document.getElementById('estAct').textContent = fmt(act);
  document.getElementById('estTrans').textContent = fmt(trans);
  document.getElementById('estFlight').textContent = fmt(flights);
  document.getElementById('estGrand').textContent = fmt(grand);

  const fitEl = document.getElementById('estFit');
  if (grand <= state.budget) {
    fitEl.className = 'est-fit ok';
    fitEl.textContent = 'Within budget';
  } else {
    fitEl.className = 'est-fit over';
    fitEl.textContent = 'Over by ' + fmt(grand - state.budget);
  }
}

/* ===== CHAT ===== */
const log = document.getElementById('chatLog');
function addMsg(cls, html) { const d = document.createElement('div'); d.className = 'msg ' + cls; d.innerHTML = html; log.appendChild(d); requestAnimationFrame(function() { log.scrollTop = log.scrollHeight; }); return d; }
document.getElementById('chatForm')?.addEventListener('submit', async e => {
  e.preventDefault();
  const inp = document.getElementById('chatInput'); const q = inp.value.trim(); if (!q) return;
  addMsg('user', q.replace(/</g, '&lt;')); inp.value = '';
  const typing = addMsg('bot', '<span class="typing"><i></i><i></i><i></i></span>');
  try {
    const r = await fetch(_pd.chatUrl, {method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify({question:q})});
    const d = await r.json();
    let html = (d.answer || 'No answer.').replace(/</g, '&lt;').replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
    if (d.grounding && d.grounding.length) {
      html += '<div class="cites">' + d.grounding.filter(g => g.uri).slice(0,5)
        .map(g => `<a target="_blank" rel="noopener" href="${g.uri}">${(g.title||'source').slice(0,32)}</a>`).join('') + '</div>';
    }
    typing.innerHTML = html;
  } catch(err) { typing.textContent = 'Sorry — something went wrong.'; }
});

/* ===== SCROLL REVEAL ===== */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); } });
}, {threshold: 0.01, rootMargin: '0px 0px -40px 0px'});
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

/* Fallback: force-reveal all if observer hasn't fired within 2s (JS error protection) */
setTimeout(() => {
  document.querySelectorAll('.reveal:not(.visible)').forEach(el => el.classList.add('visible'));
}, 2000);

/* ===== ESTIMATION BAR VISIBILITY ===== */
/* Hero observer only auto-shows on scroll; toggle button has full control */
const heroEl = document.querySelector('.hero');
if (heroEl) {
  let heroPassed = false;
  const heroObs = new IntersectionObserver(([entry]) => {
    heroPassed = !entry.isIntersecting;
    /* Only auto-show when scrolling past hero; never auto-hide */
    if (heroPassed && !estHidden) {
      estBar.classList.add('visible');
      document.body.classList.add('has-est-bar');
    }
  }, {threshold: 0});
  heroObs.observe(heroEl);
}

/* ===== LIKE ===== */
const likeBtn = document.getElementById('likeBtn');
if (likeBtn && !likeBtn.disabled) {
  likeBtn.addEventListener('click', async () => {
    try {
      const r = await fetch(_pd.likeUrl, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF, 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json'}});
      if (r.redirected) { window.location.href = r.url; return; }
      if (!r.ok) { showSnackbar('Like failed. Try again.'); return; }
      const d = await r.json();
      document.getElementById('likeCount').textContent = d.count;
      likeBtn.classList.toggle('btn-accent', d.liked);
      likeBtn.classList.toggle('btn-ghost', !d.liked);
    } catch(e) { showSnackbar('Something went wrong.'); }
  });
}

/* ===== INIT ===== */
(async () => {
  const r = await fetchFxRate(CUR_TRIP);
  if (r !== null) baseRate = r;
  curRate = baseRate;
  convertCurrency();
  /* Show est bar after short delay to ensure DOM is ready */
  setTimeout(() => {
    document.body.classList.add('has-est-bar');
    if (!estHidden) estBar.classList.add('visible');
    recalcTotal();
  }, 300);
})();

/* ===== NEW TABS ===== */
window.showTab = function(tab, btn) {
  document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.profile-tab').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + tab).style.display = 'block';
  
  if (btn) {
    btn.classList.add('active');
  } else {
    const targetBtn = Array.from(document.querySelectorAll('.profile-tab')).find(b => b.getAttribute('onclick')?.includes(`'${tab}'`));
    if (targetBtn) targetBtn.classList.add('active');
  }
};

/* Check for tab query param on load */
(function() {
  const urlParams = new URLSearchParams(window.location.search);
  const activeTab = urlParams.get('tab');
  if (activeTab && ['trips', 'posts', 'media', 'reviews'].includes(activeTab)) {
    window.showTab(activeTab);
    
    // Auto-open form if open_form parameter is set
    const openForm = urlParams.get('open_form');
    if (openForm === '1') {
      if (activeTab === 'posts') {
        window.toggleForm('post-form-block', 'toggle-post-form-btn', 'Post', 'post_add');
      } else if (activeTab === 'media') {
        window.toggleForm('media-form-block', 'toggle-media-form-btn', 'Media', 'cloud_upload');
      } else if (activeTab === 'reviews') {
        window.toggleForm('review-form-block', 'toggle-review-form-btn', 'Review', 'rate_review');
      }
    }
  }
})();

window.handlePostMediaSelect = function(input) {
  const countSpan = document.getElementById('media-selected-count');
  const typeInput = document.getElementById('post_type');
  if (input.files && input.files.length > 0) {
    countSpan.textContent = input.files.length + ' file(s) selected';
    let hasVideo = false;
    for (let i = 0; i < input.files.length; i++) {
      if (input.files[i].type.startsWith('video/')) {
        hasVideo = true;
        break;
      }
    }
    typeInput.value = hasVideo ? 'video' : 'photo';
  } else {
    countSpan.textContent = 'No files selected';
    typeInput.value = 'text';
  }
};

window.handleMediaDrop = function(e) {
  e.preventDefault();
  const form = document.getElementById('media-upload-form');
  if (form) form.style.borderColor = 'var(--md-outline-variant)';
  if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
    uploadMediaFile(e.dataTransfer.files[0]);
  }
};

window.uploadMediaFile = async function(file) {
  if (!file) return;
  const status = document.getElementById('upload-status');
  status.style.display = 'block';
  status.textContent = 'Uploading: ' + file.name + '...';
  
  const formData = new FormData();
  formData.append('file', file);
  formData.append('mediable_type', 'trip');
  formData.append('mediable_id', '{{ $trip->id }}');
  formData.append('_token', CSRF);
  
  try {
    const res = await fetch('{{ route('media.store') }}', {
      method: 'POST',
      body: formData
    });
    if (!res.ok) throw new Error('Upload failed');
    const data = await res.json();
    status.textContent = 'Upload complete!';
    setTimeout(() => { status.style.display = 'none'; }, 2000);
    
    const grid = document.getElementById('media-grid-container');
    const noMediaMsg = document.getElementById('no-media-message');
    if (noMediaMsg) noMediaMsg.style.display = 'none';
    
    const div = document.createElement('div');
    div.className = 'media-item';
    div.style.cursor = 'pointer';
    
    const url = data.media.url;
    if (data.media.type === 'video') {
      div.innerHTML = `
        <video src="${url}" muted></video>
        <span class="media-play"><svg class="icon " width="32" height="32" viewBox="0 -960 960 960" fill="currentColor" style="vertical-align:middle;display:inline-flex;flex-shrink:0"><path d="M320-200v-560l440 280-440 280Z"/></svg></span>
      `;
      div.onclick = () => openMediaLightbox(url);
    } else {
      div.innerHTML = `<img src="${url}" alt="Travel photo">`;
      div.onclick = () => openMediaLightbox(url);
    }
    
    grid.insertBefore(div, grid.firstChild);
    
    const countBadge = document.getElementById('media-count-badge');
    if (countBadge) {
      countBadge.textContent = parseInt(countBadge.textContent || 0) + 1;
    }
  } catch(e) {
    status.textContent = 'Error: ' + e.message;
    status.style.color = 'var(--md-error)';
  }
};

window.openMediaLightbox = function(url) {
  lbImages = [url];
  lbIndex = 0;
  updateLightbox();
  document.getElementById('lightbox').classList.add('on');
  document.body.style.overflow = 'hidden';
};

window.setRating = function(rating) {
  document.getElementById('review_rating').value = rating;
  const stars = document.querySelectorAll('.rating-stars .star-btn');
  stars.forEach((star, idx) => {
    star.style.color = idx < rating ? 'var(--md-primary)' : 'var(--md-outline-variant)';
  });
};

window.toggleForm = function(formId, btnId, typeText, defaultIcon) {
  const formBlock = document.getElementById(formId);
  const btn = document.getElementById(btnId);
  if (!formBlock || !btn) return;

  const isHidden = formBlock.style.display === 'none';
  if (isHidden) {
    formBlock.style.display = 'block';
    let btnText = 'Cancel';
    if (typeText === 'Post') btnText = 'Cancel Post';
    else if (typeText === 'Media') btnText = 'Cancel Upload';
    else if (typeText === 'Review') btnText = 'Cancel Review';
    
    btn.innerHTML = `<svg class="icon " width="18" height="18" viewBox="0 -960 960 960" fill="currentColor" style="vertical-align:middle;display:inline-flex;flex-shrink:0"><path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/></svg> ${btnText}`;
    btn.classList.remove('btn-outlined');
    btn.classList.add('btn-ghost');
  } else {
    formBlock.style.display = 'none';
    let btnText = 'New Post';
    if (typeText === 'Media') btnText = 'Upload Media';
    else if (typeText === 'Review') btnText = 'Write Review';
    
    let iconSvg = '';
    if (defaultIcon === 'post_add') {
      iconSvg = `<svg class="icon " width="18" height="18" viewBox="0 -960 960 960" fill="currentColor" style="vertical-align:middle;display:inline-flex;flex-shrink:0"><path d="M720-120v-120H600v-80h120v-120h80v120h120v80H800v120h-80ZM200-160q-33 0-56.5-23.5T120-240v-560q0-33 23.5-56.5T200-880h320l80 80H200v560h440v-160h80v160q0 33-23.5 56.5T640-160H200Zm120-200v-80h200v80H320Zm0-120v-80h280v80H320Zm0-120v-80h280v80H320Z"/></svg>`;
    } else if (defaultIcon === 'cloud_upload') {
      iconSvg = `<svg class="icon " width="18" height="18" viewBox="0 -960 960 960" fill="currentColor" style="vertical-align:middle;display:inline-flex;flex-shrink:0"><path d="M440-200v-244L332-336l-56-56 204-204 204 204-56 56-108-108v244h-80Zm-240 80q-50 0-85-35t-35-85v-120h80v120h560v-120h80v120q0 50-35 85t-85 85H200Z"/></svg>`;
    } else if (defaultIcon === 'rate_review') {
      iconSvg = `<svg class="icon " width="18" height="18" viewBox="0 -960 960 960" fill="currentColor" style="vertical-align:middle;display:inline-flex;flex-shrink:0"><path d="M240-400h320v-80H240v80Zm0-120h480v-80H240v80Zm0-120h480v-80H240v80ZM80-80v-720q0-33 23.5-56.5T160-880h640q33 0 56.5 23.5T880-800v480q0 33-23.5 56.5T800-240H240L80-8ZM160-800v545l75-75h565v-470H160Zm0 0v470-470Z"/></svg>`;
    }
    
    btn.innerHTML = `${iconSvg} ${btnText}`;
    btn.classList.remove('btn-ghost');
    btn.classList.add('btn-outlined');
  }
};

/* ===== TRIP SHARE TRACKING ===== */
window.copyTripUrl = function() {
  var url = document.getElementById('shareUrl').value;
  navigator.clipboard.writeText(url).then(function() {
    var btn = event.target;
    btn.textContent = 'Copied!';
    setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
    fetch('/api/trip/' + _pd.tripId + '/share', { method:'POST', headers:{ 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' } })
      .then(function(r) { if (r.redirected) { window.location.href = r.url; return; } return r.json(); })
      .catch(function() {});
  }).catch(function() {});
};


</script>
@endpush
@endsection
