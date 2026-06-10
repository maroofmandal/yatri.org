<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->check() ? auth()->user()->theme : 'auto' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Admin') · Yatri</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="preload" as="style" href="{{ asset('css/yatri.css') }}" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="{{ asset('css/yatri.css') }}"></noscript>
<style>
.adm{display:flex;min-height:100vh}
.adm-side{width:240px;background:var(--md-inverse-surface);color:var(--md-inverse-on-surface);flex-shrink:0;padding:20px 0;position:sticky;top:0;height:100vh;overflow:auto}
.adm-side .b{font-family:Outfit;font-weight:700;color:var(--md-inverse-on-surface);font-size:18px;padding:0 22px 18px;display:flex;gap:10px;align-items:center}
.adm-side .b .dot{width:8px;height:8px;border-radius:50%;background:var(--md-inverse-primary)}
.adm-side a{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.7);padding:11px 22px;font-size:14px;font-weight:500;border-left:3px solid transparent;text-decoration:none;transition:all .15s}
.adm-side a:hover{color:#fff;background:rgba(255,255,255,.06);text-decoration:none}
.adm-side a.on{color:#fff;border-left-color:var(--md-inverse-primary);background:rgba(255,255,255,.1)}
.adm-side a .icon{font-size:20px}
.adm-side .sep{border-top:1px solid rgba(255,255,255,.12);margin:12px 0}
.adm-main{flex:1;padding:28px 32px;overflow:auto;background:var(--md-surface)}
.adm-h{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.adm-h h1{font-size:26px;font-weight:600}
table.t{width:100%;border-collapse:collapse;background:var(--md-surface-container-low);border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-md);overflow:hidden}
table.t th{text-align:left;font-family:Outfit;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--md-on-surface-variant);padding:11px 14px;border-bottom:1px solid var(--md-outline-variant);background:var(--md-surface-container)}
table.t td{padding:11px 14px;border-bottom:1px solid var(--md-outline-variant);font-size:13px;vertical-align:middle}
table.t tr:last-child td{border-bottom:none}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.stat{background:var(--md-surface-container-low);border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-md);padding:18px}
.stat .n{font-family:Outfit;font-weight:600;font-size:28px;color:var(--md-primary)}
.stat .l{color:var(--md-on-surface-variant);font-size:11px;text-transform:uppercase;letter-spacing:.05em;margin-top:4px}
.badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;border-radius:var(--md-shape-full);padding:3px 10px}
.badge.ok{background:var(--md-tertiary-container);color:var(--md-on-tertiary-container)}
.badge.warn{background:var(--md-error-container);color:var(--md-on-error-container)}
.badge.mut{background:var(--md-surface-container-high);color:var(--md-on-surface-variant)}
.badge.blue{background:var(--md-secondary-container);color:var(--md-on-secondary-container)}
.inline-form{display:inline}
@media(max-width:760px){.adm{flex-direction:column}.adm-side{width:100%;height:auto;position:static}}
</style>
</head>
<body>
<div class="adm">
  <aside class="adm-side">
    <div class="b"><span class="dot"></span> Yatri Admin</div>
    @php $is = fn($p) => request()->routeIs($p) ? 'on' : ''; @endphp
    <a class="{{ $is('admin.dashboard') }}" href="{{ route('admin.dashboard') }}"><x-icon name="dashboard" /> Dashboard</a>
    <a class="{{ $is('admin.trips.*') }}" href="{{ route('admin.trips.index') }}"><x-icon name="map" /> Trips</a>
    <a class="{{ $is('admin.users.*') }}" href="{{ route('admin.users.index') }}"><x-icon name="people" /> Users</a>
    <a class="{{ $is('admin.destinations.*') }}" href="{{ route('admin.destinations.index') }}"><x-icon name="location_on" /> Destinations</a>
    <a class="{{ $is('admin.gemini.*') }}" href="{{ route('admin.gemini.index') }}"><x-icon name="smart_toy" /> Gemini usage</a>
    <a class="{{ $is('admin.settings.*') }}" href="{{ route('admin.settings.edit') }}"><x-icon name="settings" /> Settings</a>
    <div class="sep"></div>
    <a href="{{ route('home') }}"><x-icon name="open_in_new" /> View site</a>
    <form method="post" action="{{ route('logout') }}">@csrf<a href="#" onclick="this.closest('form').submit();return false"><x-icon name="logout" /> Log out</a></form>
  </aside>
  <main class="adm-main">
    @if(session('ok'))<div class="flash flash-ok"><x-icon name="check_circle" :size="20" /> {{ session('ok') }}</div>@endif
    @if(session('error'))<div class="flash flash-err"><x-icon name="error" :size="20" /> {{ session('error') }}</div>@endif
    @yield('admin')
  </main>
</div>
@stack('scripts')
</body>
</html>
