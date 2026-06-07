<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Admin') · Yatri</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="stylesheet" href="{{ asset('css/yatri.css') }}">
<style>
  .adm{display:flex;min-height:100vh}
  .adm-side{width:230px;background:#1c1917;color:#d6d3d1;flex-shrink:0;padding:20px 0;position:sticky;top:0;height:100vh;overflow:auto}
  .adm-side .b{font-family:Outfit;font-weight:700;color:#fff;font-size:18px;padding:0 22px 18px;display:flex;gap:8px;align-items:center}
  .adm-side .b .dot{width:9px;height:9px;border-radius:50%;background:var(--accent)}
  .adm-side a{display:block;color:#a8a29e;padding:11px 22px;font-size:14px;font-weight:600;border-left:3px solid transparent;text-decoration:none}
  .adm-side a:hover{color:#fff;background:rgba(255,255,255,.04)}
  .adm-side a.on{color:#fff;border-left-color:var(--accent);background:rgba(194,65,44,.14)}
  .adm-side .sep{border-top:1px solid #3a3531;margin:12px 0}
  .adm-main{flex:1;padding:28px 32px;overflow:auto;background:var(--bg)}
  .adm-h{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:12px}
  .adm-h h1{font-size:26px;font-weight:600}
  table.t{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--line);border-radius:var(--r);overflow:hidden}
  table.t th{text-align:left;font-family:Outfit;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);padding:11px 14px;border-bottom:1px solid var(--line);background:var(--bg)}
  table.t td{padding:11px 14px;border-bottom:1px solid var(--line);font-size:13.5px;vertical-align:middle}
  table.t tr:last-child td{border-bottom:none}
  .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:18px}
  .stat .n{font-family:Outfit;font-weight:600;font-size:28px}
  .stat .l{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em;margin-top:4px}
  .badge{display:inline-block;font-size:11px;font-weight:600;border-radius:20px;padding:2px 9px}
  .badge.ok{background:#f0fdf4;color:#166534}.badge.warn{background:#fef2f2;color:#991b1b}
  .badge.mut{background:#f1f5f9;color:#334155}.badge.blue{background:#eff6ff;color:#1e40af}
  .inline-form{display:inline}
  .pager{margin-top:16px}
  @media(max-width:760px){.adm{flex-direction:column}.adm-side{width:100%;height:auto;position:static}}
</style>
</head>
<body>
<div class="adm">
  <aside class="adm-side">
    <div class="b"><span class="dot"></span> Yatri Admin</div>
    @php $is = fn($p) => request()->routeIs($p) ? 'on' : ''; @endphp
    <a class="{{ $is('admin.dashboard') }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a class="{{ $is('admin.trips.*') }}" href="{{ route('admin.trips.index') }}">Trips</a>
    <a class="{{ $is('admin.users.*') }}" href="{{ route('admin.users.index') }}">Users</a>
    <a class="{{ $is('admin.destinations.*') }}" href="{{ route('admin.destinations.index') }}">Destinations</a>
    <a class="{{ $is('admin.gemini.*') }}" href="{{ route('admin.gemini.index') }}">Gemini usage</a>
    <a class="{{ $is('admin.settings.*') }}" href="{{ route('admin.settings.edit') }}">Settings</a>
    <div class="sep"></div>
    <a href="{{ route('home') }}">↗ View site</a>
    <form method="post" action="{{ route('logout') }}">@csrf<a href="#" onclick="this.closest('form').submit();return false">Log out</a></form>
  </aside>
  <main class="adm-main">
    @if(session('ok'))<div class="flash flash-ok">{{ session('ok') }}</div>@endif
    @if(session('error'))<div class="flash flash-err">{{ session('error') }}</div>@endif
    @yield('admin')
  </main>
</div>
@stack('scripts')
</body>
</html>
