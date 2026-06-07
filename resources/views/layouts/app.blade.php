<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', \App\Models\Setting::get('site_name', 'Yatri') . ' — AI budget trip planner')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="{{ asset('favicon.ico') }}">
@stack('head')
<link rel="stylesheet" href="{{ asset('css/yatri.css') }}">
</head>
<body>
<nav class="nav"><div class="wrap">
  <button class="nav-toggle" aria-label="Menu" onclick="document.querySelector('.nav-drawer').classList.add('open')">
    <span></span><span></span><span></span>
  </button>
  <a class="brand" href="{{ route('home') }}"><span class="dot"></span>{{ \App\Models\Setting::get('site_name', 'Yatri') }}</a>
  <div class="links">
    <a href="{{ route('home') }}">Explore</a>
    <a href="{{ route('pricing') }}">Pricing</a>
    <a class="btn btn-accent btn-sm" href="{{ route('planner') }}">✨ Plan a trip</a>
    @auth
      <a href="{{ route('dashboard') }}">My trips</a>
      <a href="{{ route('profile', auth()->user()) }}">Profile</a>
      @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}">Admin</a>@endif
      <form method="post" action="{{ route('logout') }}" style="display:inline">@csrf<button class="btn btn-ghost btn-sm">Log out</button></form>
    @else
      <a href="{{ route('login') }}">Log in</a>
      <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Sign up</a>
    @endauth
  </div>
</div></nav>

{{-- Mobile drawer (Android-style left sidebar) --}}
<div class="nav-drawer">
  <div class="nav-drawer-overlay" onclick="this.parentElement.classList.remove('open')"></div>
  <div class="nav-drawer-panel">
    <div class="nav-drawer-header">
      <a class="brand" href="{{ route('home') }}"><span class="dot"></span>{{ \App\Models\Setting::get('site_name', 'Yatri') }}</a>
      <button class="nav-drawer-close" aria-label="Close" onclick="document.querySelector('.nav-drawer').classList.remove('open')">&times;</button>
    </div>
    <div class="nav-drawer-links">
      <a href="{{ route('home') }}">Explore</a>
      <a href="{{ route('pricing') }}">Pricing</a>
      <a href="{{ route('planner') }}">✨ Plan a trip</a>
      @auth
        <a href="{{ route('dashboard') }}">My trips</a>
        <a href="{{ route('profile', auth()->user()) }}">Profile</a>
        @if(auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}">Admin</a>@endif
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-block" style="margin-top:12px">Log out</button></form>
      @else
        <a href="{{ route('login') }}">Log in</a>
        <a class="btn btn-primary btn-block" style="margin-top:12px" href="{{ route('register') }}">Sign up</a>
      @endauth
    </div>
  </div>
</div>

@if(session('ok'))<div class="wrap"><div class="flash flash-ok">{{ session('ok') }}</div></div>@endif
@if(session('error'))<div class="wrap"><div class="flash flash-err">{{ session('error') }}</div></div>@endif

@yield('content')

<footer><div class="wrap">
  {{ \App\Models\Setting::get('site_name', 'Yatri') }} · AI budget trip planner — itineraries grounded with live Google Search &amp; Maps data via Gemini. © {{ date('Y') }}
</div></footer>
@stack('scripts')
<script>
(function(){
  // Close mobile drawer on back-button or Escape
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
      document.querySelector('.nav-drawer').classList.remove('open');
    }
  });
})();
</script>
@php
  $geoProvider = \App\Models\Setting::get('geocode_provider', config('providers.geocode', 'photon'));
  $yatriMapsKey = \App\Models\Setting::get('google_places_api_key')
      ?: \App\Models\Setting::get('google_maps_api_key')
      ?: config('gemini.places_key')
      ?: config('gemini.maps_key');
  $useGoogle = $geoProvider === 'google' && $yatriMapsKey;
@endphp
<script>window.YATRI_GEO="{{ $geoProvider }}";</script>
@if($useGoogle)
<script>
(function(){
  var s=document.createElement('script');
  s.src='https://maps.googleapis.com/maps/api/js?key={{ $yatriMapsKey }}&libraries=places&loading=async&callback=initYatriPlaces';
  s.async=true; s.defer=true;
  document.head.appendChild(s);
})();
</script>
@endif
<script>
/**
 * Yatri location autocomplete — provider-agnostic.
 *  - google   → Google Places JS widget
 *  - else     → backend proxy /geo/suggest (photon | geoapify | nominatim)
 * onChange always receives {name, geometry:{location:{lat(),lng()}}} so callers are uniform.
 */
function attachPlaces(input, opts){
  opts = opts||{};
  if(!input || input.dataset.placesAttached) return;
  input.dataset.placesAttached='1';
  input.removeAttribute('list');
  input.setAttribute('autocomplete','off');

  if(window.YATRI_GEO==='google' && window.google && window.google.maps && window.google.maps.places){
    var ac = new google.maps.places.Autocomplete(input, {types:['(cities)'], fields:['name','geometry']});
    ac.addListener('place_changed', function(){
      var p = ac.getPlace();
      if(p && p.geometry){ input.dataset.lat=p.geometry.location.lat(); input.dataset.lng=p.geometry.location.lng(); }
      if(typeof opts.onChange==='function') opts.onChange(p);
    });
    return;
  }
  customAutocomplete(input, opts);
}

function customAutocomplete(input, opts){
  var box=document.createElement('div'); box.className='geo-drop'; box.style.display='none';
  if(getComputedStyle(input.parentNode).position==='static') input.parentNode.style.position='relative';
  input.parentNode.appendChild(box);
  var timer, items=[];
  function close(){ box.style.display='none'; }
  function render(list){
    items=list||[];
    if(!items.length){ close(); return; }
    box.innerHTML=items.map(function(x,i){return '<div class="geo-item" data-i="'+i+'">'+x.name+'</div>';}).join('');
    box.style.display='block';
    box.querySelectorAll('.geo-item').forEach(function(el){
      el.onmousedown=function(e){ e.preventDefault(); pick(items[+el.dataset.i]); };
    });
  }
  function pick(x){
    input.value=x.name; input.dataset.lat=x.lat; input.dataset.lng=x.lng; close();
    if(typeof opts.onChange==='function') opts.onChange({name:x.name, geometry:{location:{lat:function(){return x.lat;}, lng:function(){return x.lng;}}}});
  }
  input.addEventListener('input', function(){
    input.dataset.lat=''; input.dataset.lng='';
    clearTimeout(timer);
    var q=input.value.trim();
    if(q.length<2){ close(); return; }
    timer=setTimeout(function(){
      fetch('{{ route('geo.suggest') }}?q='+encodeURIComponent(q))
        .then(function(r){return r.json();}).then(render).catch(close);
    }, 250);
  });
  input.addEventListener('blur', function(){ setTimeout(close, 150); });
}

function initYatriPlaces(){
  document.querySelectorAll('[data-places]').forEach(function(el){ attachPlaces(el); });
}
// Google calls initYatriPlaces via callback; for proxy providers init on load.
if(window.YATRI_GEO!=='google'){
  if(document.readyState!=='loading') initYatriPlaces();
  else document.addEventListener('DOMContentLoaded', initYatriPlaces);
}else if(window.google && window.google.maps && window.google.maps.places){
  initYatriPlaces();
}
</script>
</body>
</html>
