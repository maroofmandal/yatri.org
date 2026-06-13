<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->check() ? auth()->user()->theme : 'light' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="@yield('meta_description', \App\Models\Setting::get('site_name', 'Yatri') . ' — AI-powered travel planner and social network for travelers')">
<title>@yield('title', \App\Models\Setting::get('site_name', 'Yatri') . ' — AI budget trip planner')</title>
@php $_route = request()->route() ? request()->route()->getName() : ''; $_noindex = in_array($_route, ['login', 'register', 'notifications.index', 'settings', 'dashboard']); @endphp
<meta name="robots" content="{{ $_noindex ? 'noindex,nofollow' : 'index,follow' }}">
<link rel="canonical" href="{{ url()->current() }}">
<link rel="preload" href="{{ asset('fonts/Poppins-400.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/Poppins-500.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/Poppins-600.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/Poppins-700.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="icon" href="{{ asset('storage/images/favicon.ico') }}?v={{ config('app.version') }}" sizes="any">
<link rel="icon" href="{{ asset('storage/images/favicon-32x32.png') }}?v={{ config('app.version') }}" sizes="32x32" type="image/png">
<link rel="icon" href="{{ asset('storage/images/favicon-16x16.png') }}?v={{ config('app.version') }}" sizes="16x16" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('storage/images/apple-touch-icon.png') }}?v={{ config('app.version') }}" sizes="180x180">
<link rel="manifest" href="{{ asset('site.webmanifest') }}?v={{ config('app.version') }}">
<meta name="theme-color" content="#0286fe">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Yatri">
<meta property="og:title" content="@yield('og_title', \App\Models\Setting::get('site_name', 'Yatri'))">
<meta property="og:description" content="@yield('meta_description', \App\Models\Setting::get('site_name', 'Yatri') . ' — AI-powered travel planner and social network for travelers')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:image" content="@yield('og_image', asset('storage/images/yatri-icon.png') . '?v=' . config('app.version'))">
<meta property="og:image:width" content="@yield('og_image_width', '256')">
<meta property="og:image:height" content="@yield('og_image_height', '256')">
<meta property="og:site_name" content="Yatri">
<meta name="twitter:card" content="@yield('twitter_card', 'summary')">
<meta name="twitter:title" content="@yield('title', \App\Models\Setting::get('site_name', 'Yatri') . ' — AI budget trip planner')">
<meta name="twitter:description" content="@yield('meta_description', \App\Models\Setting::get('site_name', 'Yatri') . ' — AI-powered travel planner and social network for travelers')">
<meta name="twitter:image" content="@yield('og_image', asset('storage/images/yatri-icon.png') . '?v=' . config('app.version'))">
@stack('head')
<style>:root,[data-theme="light"]{--md-primary:#005cbb;--md-on-primary:#fff;--md-primary-container:#dae2ff;--md-on-primary-container:#001a41;--md-secondary:#565f71;--md-on-secondary:#fff;--md-secondary-container:#dae3f9;--md-on-secondary-container:#131c2b;--md-tertiary:#0084b0;--md-on-tertiary:#fff;--md-tertiary-container:#c6e7ff;--md-on-tertiary-container:#001e2e;--md-error:#ba1a1a;--md-on-error:#fff;--md-error-container:#ffdad6;--md-on-error-container:#410002;--md-surface:#fdfbff;--md-on-surface:#1a1c1e;--md-surface-variant:#e1e3e8;--md-on-surface-variant:#44474e;--md-surface-dim:#dcdee4;--md-surface-bright:#fdfbff;--md-surface-container-lowest:#fff;--md-surface-container-low:#f6f7fa;--md-surface-container:#eff0f4;--md-surface-container-high:#eaebef;--md-surface-container-highest:#e4e5e9;--md-outline:#74777f;--md-outline-variant:#c4c6cf;--md-inverse-surface:#2f3033;--md-inverse-on-surface:#f1f0f4;--md-inverse-primary:#8ed0fd;--md-scrim:#000;--md-shadow:#000;--md-elevation-1:0 1px 3px 1px rgba(0,0,0,.15),0 1px 2px rgba(0,0,0,.3);--md-elevation-2:0 2px 6px 2px rgba(0,0,0,.15),0 1px 2px rgba(0,0,0,.3);--md-elevation-3:0 4px 8px 3px rgba(0,0,0,.15),0 1px 3px rgba(0,0,0,.3);--md-elevation-4:0 6px 10px 4px rgba(0,0,0,.15),0 2px 3px rgba(0,0,0,.3);--md-elevation-5:0 8px 12px 6px rgba(0,0,0,.15),0 4px 4px rgba(0,0,0,.3);--md-shape-xs:4px;--md-shape-sm:8px;--md-shape-md:12px;--md-shape-lg:16px;--md-shape-xl:28px;--md-shape-full:9999px;--md-state-hover:rgba(26,28,30,.08);--md-state-focus:rgba(26,28,30,.12);--md-state-pressed:rgba(26,28,30,.12);color-scheme:light}@media(prefers-color-scheme:dark){:root:not([data-theme="light"]){--md-primary:#8ed0fd;--md-on-primary:#002d6f;--md-primary-container:#004297;--md-on-primary-container:#dae2ff;--md-secondary:#bdc7db;--md-on-secondary:#273141;--md-secondary-container:#3d4858;--md-on-secondary-container:#dae3f9;--md-tertiary:#75d0ff;--md-on-tertiary:#003450;--md-tertiary-container:#004c6d;--md-on-tertiary-container:#c6e7ff;--md-error:#ffb4ab;--md-on-error:#690005;--md-error-container:#93000a;--md-on-error-container:#ffdad6;--md-surface:#1a1c1e;--md-on-surface:#e2e3e7;--md-surface-variant:#44474e;--md-on-surface-variant:#c4c6cf;--md-surface-dim:#1a1c1e;--md-surface-bright:#3a3c41;--md-surface-container-lowest:#0f1113;--md-surface-container-low:#1a1c1e;--md-surface-container:#1e2023;--md-surface-container-high:#282a2e;--md-surface-container-highest:#333539;--md-outline:#8e9099;--md-outline-variant:#44474e;--md-inverse-surface:#e2e3e7;--md-inverse-on-surface:#2f3033;--md-inverse-primary:#0286fe;--md-scrim:#000;--md-shadow:#000;color-scheme:dark}}[data-theme="dark"]{--md-primary:#8ed0fd;--md-on-primary:#002d6f;--md-primary-container:#004297;--md-on-primary-container:#dae2ff;--md-secondary:#bdc7db;--md-on-secondary:#273141;--md-secondary-container:#3d4858;--md-on-secondary-container:#dae3f9;--md-tertiary:#75d0ff;--md-on-tertiary:#003450;--md-tertiary-container:#004c6d;--md-on-tertiary-container:#c6e7ff;--md-error:#ffb4ab;--md-on-error:#690005;--md-error-container:#93000a;--md-on-error-container:#ffdad6;--md-surface:#1a1c1e;--md-on-surface:#e2e3e7;--md-surface-variant:#44474e;--md-on-surface-variant:#c4c6cf;--md-surface-dim:#1a1c1e;--md-surface-bright:#3a3c41;--md-surface-container-lowest:#0f1113;--md-surface-container-low:#1a1c1e;--md-surface-container:#1e2023;--md-surface-container-high:#282a2e;--md-surface-container-highest:#333539;--md-outline:#8e9099;--md-outline-variant:#44474e;--md-inverse-surface:#e2e3e7;--md-inverse-on-surface:#2f3033;--md-inverse-primary:#0286fe;--md-scrim:#000;--md-shadow:#000;color-scheme:dark}*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}body{font-family:'Poppins',system-ui,sans-serif;font-size:16px;line-height:1.5;color:var(--md-on-surface);background:var(--md-surface);-webkit-font-smoothing:antialiased;overflow-x:hidden}h1,h2,h3,h4,h5,h6{font-family:'Poppins',system-ui,sans-serif;font-weight:500;line-height:1.2;letter-spacing:-.02em;color:var(--md-on-surface)}a{color:var(--md-primary);text-decoration:none}.wrap{max-width:1120px;margin:0 auto;padding:0 24px}.topbar{position:sticky;top:0;z-index:1200;background:rgba(253,252,255,.85);backdrop-filter:blur(20px) saturate(1.8);border-bottom:1px solid var(--md-outline-variant)}[data-theme="dark"] .topbar{background:rgba(26,28,30,.85)}@media(prefers-color-scheme:dark){:root:not([data-theme="light"]) .topbar{background:rgba(26,28,30,.85)}}.topbar .wrap{display:flex;align-items:center;justify-content:space-between;padding:10px 24px;gap:12px;height:64px}.topbar-left{display:flex;align-items:center;gap:16px}.topbar-brand{display:flex;align-items:center;gap:8px;text-decoration:none}.topbar-brand img{display:block;height:28px;width:auto}@media(max-width:480px){.topbar-brand img{height:24px}}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:none;cursor:pointer;font-family:'Poppins',sans-serif;font-weight:600;font-size:14px;padding:10px 24px;border-radius:var(--md-shape-full);transition:all .15s;text-decoration:none;white-space:nowrap;line-height:1.4}.btn-filled{background:var(--md-primary);color:var(--md-on-primary)}.btn-outlined{background:transparent;color:var(--md-primary);border:1px solid var(--md-outline)}.btn-text{background:transparent;color:var(--md-primary);padding:10px 12px}.btn-sm{padding:8px 18px;font-size:13px}.btn-lg{padding:14px 32px;font-size:16px}.topbar-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover}.tauthor img{width:26px;height:26px;border-radius:50%;object-fit:cover}.notification-avatar{width:44px;height:44px;border-radius:50%;overflow:hidden}.notification-avatar img{width:100%;height:100%;object-fit:cover}.comment-avatar{width:32px;height:32px;border-radius:50%;object-fit:cover}.reply-avatar{width:24px;height:24px;border-radius:50%;object-fit:cover}</style>
<link rel="stylesheet" href="{{ asset('css/yatri.css') }}?v={{ config('app.version') }}">

<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Organization",
  "name": "Yatri",
  "url": "{{ url('/') }}",
  "logo": "{{ asset('storage/images/yatri-icon.png') }}",
  "description": "AI-powered travel planner and social network for travelers",
  "sameAs": []
}
</script>

<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebSite",
  "name": "Yatri",
  "url": "{{ url('/') }}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "{{ url('/') }}?q={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
</script>
</head>
@php
  $geoProvider = \App\Models\Setting::get('geocode_provider', config('providers.geocode', 'photon'));
  $yatriMapsKey = \App\Models\Setting::get('google_places_api_key')
      ?: \App\Models\Setting::get('google_maps_api_key')
      ?: config('gemini.places_key')
      ?: config('gemini.maps_key');
  $useGoogle = $geoProvider === 'google' && $yatriMapsKey;
  $unreadCount = auth()->check() ? auth()->user()->getUnreadNotificationsCount() : 0;
@endphp
<body data-geo="{{ $geoProvider }}" data-geo-url="{{ route('geo.suggest') }}" class="has-bottom-nav" @auth @if(auth()->user()->theme === 'dark' || (auth()->user()->theme === 'auto' && request()->cookie('theme-pref') === 'dark')) style="background:var(--md-surface)" @endif @endauth>
@php $currentPage = request()->route()->getName(); @endphp

{{-- ═══ TOP APP BAR ═══ --}}
<header class="topbar"><div class="wrap">
  <div class="topbar-left">
    <button class="icon-btn" aria-label="Menu" onclick="document.querySelector('.nav-drawer').classList.add('open')" style="display:none" id="menu-btn">
      <x-icon name="menu" />
    </button>
    <a class="topbar-brand" href="{{ route('home') }}"><picture><source srcset="{{ asset('storage/images/yatri-logo.webp') }}?v={{ config('app.version') }} 1x, {{ asset('storage/images/yatri-logo-2x.webp') }}?v={{ config('app.version') }} 2x" type="image/webp"><img src="{{ asset('storage/images/yatri-logo.png') }}?v={{ config('app.version') }}" alt="Yatri" width="96" height="28" style="height:28px"></picture></a>
  </div>
  <nav class="topbar-nav">
    <a href="{{ route('home') }}" @if($currentPage === 'home') style="color:var(--md-primary)" @endif>
      <x-icon name="explore" :size="20" /> Explore
    </a>
    <a href="{{ route('rankings') }}" @if($currentPage === 'rankings') style="color:var(--md-primary)" @endif>
      <x-icon name="leaderboard" :size="20" /> Rankings
    </a>
    <a href="{{ route('pricing') }}" @if($currentPage === 'pricing') style="color:var(--md-primary)" @endif>
      <x-icon name="payments" :size="20" /> Pricing
    </a>
  </nav>
  <div class="topbar-right">
    @stack('nav-right')
    @auth
      <a class="btn btn-filled btn-sm" href="{{ route('planner') }}" style="display:flex;align-items:center;gap:6px">
        <x-icon name="add" :size="18" /> Plan a trip
      </a>
      <a class="icon-btn" href="{{ route('notifications.index') }}" aria-label="Notifications">
        <x-icon name="notifications" />
        @if($unreadCount > 0)<span class="badge" data-count="{{ $unreadCount }}">{{ $unreadCount }}</span>@endif
      </a>
      <div class="profile-dropdown-wrap" id="profile-dropdown-wrap">
        <img src="{{ auth()->user()->avatar() }}" alt="{{ auth()->user()->name }}" class="topbar-avatar" width="36" height="36" onclick="toggleProfileDropdown()" id="profile-avatar-btn">
        <div class="profile-dropdown" id="profile-dropdown">
          <div class="profile-dropdown-user">
            <img src="{{ auth()->user()->avatar() }}" alt="{{ auth()->user()->name }}" width="40" height="40">
            <div>
              <div class="name">{{ auth()->user()->name }}</div>
              <div class="email">{{ auth()->user()->email }}</div>
            </div>
          </div>
          <a class="profile-dropdown-item" href="{{ route('profile', auth()->user()) }}">
            <x-icon name="person" /> My profile
          </a>
          <a class="profile-dropdown-item" href="{{ route('dashboard') }}">
            <x-icon name="dashboard" /> Dashboard
          </a>
          <a class="profile-dropdown-item" href="{{ route('settings') }}">
            <x-icon name="settings" /> Settings
          </a>
          @if(auth()->user()->isAdmin())
          <a class="profile-dropdown-item" href="{{ route('admin.dashboard') }}">
            <x-icon name="admin_panel_settings" /> Admin
          </a>
          @endif
          <div class="profile-dropdown-divider"></div>
          <div style="padding:6px 12px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--md-on-surface-variant);margin-bottom:6px">Theme</div>
            <div class="theme-options" style="display:flex;gap:4px">
              <button class="theme-option-btn {{ (auth()->user()->theme ?? 'auto') === 'light' ? 'active' : '' }}" onclick="setTheme('light')" title="Light" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <x-icon name="light_mode" :size="20" style="color:var(--md-on-surface-variant)" />
              </button>
              <button class="theme-option-btn {{ (auth()->user()->theme ?? 'auto') === 'dark' ? 'active' : '' }}" onclick="setTheme('dark')" title="Dark" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <x-icon name="dark_mode" :size="20" style="color:var(--md-on-surface-variant)" />
              </button>
              <button class="theme-option-btn {{ (auth()->user()->theme ?? 'auto') === 'auto' ? 'active' : '' }}" onclick="setTheme('auto')" title="Auto" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <x-icon name="contrast" :size="20" style="color:var(--md-on-surface-variant)" />
              </button>
            </div>
          </div>
          <div class="profile-dropdown-divider"></div>
          <div style="padding:6px 12px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--md-on-surface-variant);margin-bottom:6px">Currency</div>
            <select id="globalCurrency" onchange="Yc.set(this.value)" style="width:100%;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-full);background:var(--md-surface-container);color:var(--md-on-surface);font-size:13px;font-weight:500;font-family:Poppins,system-ui,sans-serif;line-height:1.4">
              <option value="USD">$ USD</option>
              <option value="INR">₹ INR</option>
              <option value="EUR">€ EUR</option>
              <option value="GBP">£ GBP</option>
              <option value="AED">AED</option>
              <option value="SGD">S$ SGD</option>
              <option value="JPY">¥ JPY</option>
            </select>
          </div>
          <div class="profile-dropdown-divider"></div>
          <form method="post" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button type="submit" class="profile-dropdown-item">
              <x-icon name="logout" /> Log out
            </button>
          </form>
        </div>
      </div>
    @else
      <div class="profile-dropdown-wrap" id="profile-dropdown-wrap">
        <button class="icon-btn topbar-guest-avatar" onclick="toggleProfileDropdown()" id="profile-avatar-btn" aria-label="Account">
          <x-icon name="account_circle" />
        </button>
        <div class="profile-dropdown" id="profile-dropdown">
          <a class="profile-dropdown-item" href="{{ route('login') }}">
            <x-icon name="login" /> Log in
          </a>
          <a class="profile-dropdown-item" href="{{ route('register') }}">
            <x-icon name="person_add" /> Sign up
          </a>
          <div class="profile-dropdown-divider"></div>
          <div style="padding:6px 12px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--md-on-surface-variant);margin-bottom:6px">Theme</div>
            <div class="theme-options" style="display:flex;gap:4px">
              <button class="theme-option-btn active" onclick="setTheme('light')" title="Light" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <x-icon name="light_mode" :size="20" style="color:var(--md-on-surface-variant)" />
              </button>
              <button class="theme-option-btn" onclick="setTheme('dark')" title="Dark" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <x-icon name="dark_mode" :size="20" style="color:var(--md-on-surface-variant)" />
              </button>
              <button class="theme-option-btn" onclick="setTheme('auto')" title="Auto" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <x-icon name="contrast" :size="20" style="color:var(--md-on-surface-variant)" />
              </button>
            </div>
          </div>
          <div class="profile-dropdown-divider"></div>
          <div style="padding:6px 12px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--md-on-surface-variant);margin-bottom:6px">Currency</div>
            <select id="globalCurrency" onchange="Yc.set(this.value)" style="width:100%;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-full);background:var(--md-surface-container);color:var(--md-on-surface);font-size:13px;font-weight:500;font-family:Poppins,system-ui,sans-serif;line-height:1.4">
              <option value="USD">$ USD</option>
              <option value="INR">₹ INR</option>
              <option value="EUR">€ EUR</option>
              <option value="GBP">£ GBP</option>
              <option value="AED">AED</option>
              <option value="SGD">S$ SGD</option>
              <option value="JPY">¥ JPY</option>
            </select>
          </div>
        </div>
      </div>
    @endauth
  </div>
</div></header>

{{-- ═══ NAVIGATION DRAWER (Mobile Sidebar) ═══ --}}
<div class="nav-drawer">
  <div class="nav-drawer-overlay" onclick="this.parentElement.classList.remove('open')"></div>
  <div class="nav-drawer-panel">
    <div class="nav-drawer-header">
    <a class="topbar-brand" href="{{ route('home') }}"><picture><source srcset="{{ asset('storage/images/yatri-logo.webp') }}?v={{ config('app.version') }} 1x, {{ asset('storage/images/yatri-logo-2x.webp') }}?v={{ config('app.version') }} 2x" type="image/webp"><img src="{{ asset('storage/images/yatri-logo.png') }}?v={{ config('app.version') }}" alt="Yatri" width="96" height="28" style="height:28px"></picture></a>
      <button class="nav-drawer-close" aria-label="Close" onclick="document.querySelector('.nav-drawer').classList.remove('open')">
        <x-icon name="close" />
      </button>
    </div>
    <div class="nav-drawer-links">
      @auth
        <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;margin-bottom:8px">
          <img src="{{ auth()->user()->avatar() }}" alt="{{ auth()->user()->name }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover" width="40" height="40">
          <div>
            <div style="font-weight:600;font-size:14px">{{ auth()->user()->name }}</div>
            <div style="font-size:12px;color:var(--md-on-surface-variant)">{{ auth()->user()->email }}</div>
          </div>
        </div>
        <div class="nav-drawer-divider"></div>
      @endauth

      <a class="nav-drawer-item @if($currentPage === 'home') active @endif" href="{{ route('home') }}">
        <x-icon name="explore" /> Explore
      </a>
      <a class="nav-drawer-item @if($currentPage === 'rankings') active @endif" href="{{ route('rankings') }}">
        <x-icon name="leaderboard" /> Rankings
      </a>
      <a class="nav-drawer-item @if($currentPage === 'pricing') active @endif" href="{{ route('pricing') }}">
        <x-icon name="payments" /> Pricing
      </a>
      <a class="nav-drawer-item" href="{{ route('planner') }}">
        <x-icon name="route" /> Plan a trip
      </a>

      @auth
        <div class="nav-drawer-divider"></div>
        <a class="nav-drawer-item" href="{{ route('posts.create') }}">
          <x-icon name="add_circle" /> Create Post
        </a>
        <a class="nav-drawer-item @if($currentPage === 'dashboard') active @endif" href="{{ route('dashboard') }}">
          <x-icon name="dashboard" /> My trips
        </a>
        <a class="nav-drawer-item @if($currentPage === 'profile') active @endif" href="{{ route('profile', auth()->user()) }}">
          <x-icon name="person" /> Profile
        </a>
        <a class="nav-drawer-item @if($currentPage === 'notifications.index') active @endif" href="{{ route('notifications.index') }}">
          <x-icon name="notifications" /> Notifications
          @if($unreadCount > 0)<span style="margin-left:auto;background:var(--md-error);color:var(--md-on-error);font-size:11px;font-weight:700;padding:2px 8px;border-radius:var(--md-shape-full)">{{ $unreadCount }}</span>@endif
        </a>
        @if(auth()->user()->isAdmin())
        <a class="nav-drawer-item" href="{{ route('admin.dashboard') }}">
          <x-icon name="admin_panel_settings" /> Admin
        </a>
        @endif
        <div class="nav-drawer-divider"></div>
        <a class="nav-drawer-item" href="{{ route('settings') }}">
          <x-icon name="settings" /> Settings
        </a>

        <div class="nav-drawer-footer">
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-drawer-item" style="width:100%;color:var(--md-error)">
              <x-icon name="logout" /> Log out
            </button>
          </form>
        </div>
      @else
        <div class="nav-drawer-divider"></div>
        <a class="nav-drawer-item" href="{{ route('login') }}">
          <x-icon name="login" /> Log in
        </a>
        <a class="nav-drawer-item" href="{{ route('register') }}">
          <x-icon name="person_add" /> Sign up
        </a>
      @endauth
    </div>
  </div>
</div>

@if(session('ok'))<div class="wrap"><div class="flash flash-ok"><x-icon name="check_circle" :size="20" /> {{ session('ok') }}</div></div>@endif
@if(session('error'))<div class="wrap"><div class="flash flash-err"><x-icon name="error" :size="20" /> {{ session('error') }}</div></div>@endif

<main>
<div class="site-columns wrap">
  <aside class="col-left">
    <nav class="sidebar-nav">
      <a class="side-link @if($currentPage === 'home') active @endif" href="{{ route('home') }}">
        <x-icon name="explore" /> Explore
      </a>
      <a class="side-link @if($currentPage === 'rankings') active @endif" href="{{ route('rankings') }}">
        <x-icon name="leaderboard" /> Rankings
      </a>
      <a class="side-link @if($currentPage === 'pricing') active @endif" href="{{ route('pricing') }}">
        <x-icon name="payments" /> Pricing
      </a>
      <a class="side-link" href="{{ route('planner') }}">
        <x-icon name="route" /> Plan a trip
      </a>
      @auth
        <div class="side-divider"></div>
        <a class="side-link" href="{{ route('posts.create') }}">
          <x-icon name="add_circle" /> Create Post
        </a>
        <a class="side-link @if($currentPage === 'dashboard') active @endif" href="{{ route('dashboard') }}">
          <x-icon name="dashboard" /> My trips
        </a>
        <a class="side-link @if($currentPage === 'notifications.index') active @endif" href="{{ route('notifications.index') }}">
          <x-icon name="notifications" /> Notifications
          @if($unreadCount > 0)<span style="margin-left:auto;background:var(--md-error);color:var(--md-on-error);font-size:11px;font-weight:700;padding:2px 8px;border-radius:var(--md-shape-full)">{{ $unreadCount }}</span>@endif
        </a>
        @if(auth()->user()->isAdmin())
        <a class="side-link" href="{{ route('admin.dashboard') }}">
          <x-icon name="admin_panel_settings" /> Admin
        </a>
        @endif
        <div class="side-divider"></div>
        <a class="side-link @if($currentPage === 'profile') active @endif" href="{{ route('profile', auth()->user()) }}">
          <x-icon name="person" /> Profile
        </a>
        <a class="side-link" href="{{ route('settings') }}">
          <x-icon name="settings" /> Settings
        </a>
        <form method="post" action="{{ route('logout') }}" style="margin:0">
          @csrf
          <button type="submit" class="side-link" style="width:100%;color:var(--md-error);background:none;border:none;cursor:pointer;font-family:inherit;font-size:14px;font-weight:500;display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:var(--md-shape-full);transition:background .15s">
            <x-icon name="logout" /> Log out
          </button>
        </form>
      @else
        <div class="side-divider"></div>
        <a class="side-link" href="{{ route('login') }}">
          <x-icon name="login" /> Log in
        </a>
        <a class="side-link" href="{{ route('register') }}">
          <x-icon name="person_add" /> Sign up
        </a>
      @endauth
    </nav>
  </aside>
  <div class="col-main">
    @yield('hero')
    @yield('content')
  </div>
  <aside class="col-right">
  </aside>
</div>
</main>

<footer style="padding:32px 0 40px;color:var(--md-on-surface-variant);font-size:13px;text-align:center;border-top:1px solid var(--md-outline-variant)">
  <div class="wrap">
    <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:8px 24px;margin-bottom:12px">
      <a href="{{ route('about') }}" style="color:inherit;text-decoration:none">About</a>
      <a href="{{ route('privacy') }}" style="color:inherit;text-decoration:none">Privacy</a>
      <a href="{{ route('terms') }}" style="color:inherit;text-decoration:none">Terms</a>
      <a href="{{ route('contact') }}" style="color:inherit;text-decoration:none">Contact</a>
      <a href="{{ route('pricing') }}" style="color:inherit;text-decoration:none">Pricing</a>
    </div>
    <div>{{ \App\Models\Setting::get('site_name', 'Yatri') }} · AI budget trip planner — itineraries grounded with live Google Search &amp; Maps data via Gemini. © {{ date('Y') }}</div>
  </div>
</footer>

{{-- ═══ BOTTOM NAVIGATION BAR (Mobile M3) ═══ --}}
<nav class="bottom-nav" id="bottom-nav">
  <div class="bottom-nav-inner">
    <a class="bottom-nav-item @if($currentPage === 'home') active @endif" href="{{ route('home') }}">
      <x-icon name="home" />
      <span>Home</span>
    </a>
    <a class="bottom-nav-item @if(in_array($currentPage, ['dashboard','trip.show'])) active @endif" href="{{ route('dashboard') }}">
      <x-icon name="map" />
      <span>Trips</span>
    </a>
    <a class="bottom-nav-fab" href="{{ route('planner') }}">
      <div class="fab-circle">
        <x-icon name="add" />
      </div>
      <span>Plan</span>
    </a>
    <a class="bottom-nav-item @if($currentPage === 'notifications.index') active @endif" href="{{ route('notifications.index') }}">
      <x-icon name="notifications" />
      @if($unreadCount > 0)<span class="nav-badge" data-count="{{ $unreadCount }}">{{ $unreadCount }}</span>@endif
      <span>Alerts</span>
    </a>
    <a class="bottom-nav-item @if($currentPage === 'profile') active @endif" href="{{ auth()->check() ? route('profile', auth()->user()) : route('login') }}">
      <x-icon name="person" />
      <span>Profile</span>
    </a>
  </div>
</nav>

{{-- Post image viewer --}}
<div class="post-viewer" id="postViewer">
  <button class="pv-close" onclick="closePostViewer()"><x-icon name="close" /></button>
  <div class="pv-body">
    <div class="pv-image-panel">
      <button class="pv-nav-btn pv-nav-prev" onclick="pvNav(-1)"><x-icon name="chevron_left" /></button>
      <div class="pv-image-wrap" id="pvImageWrap">
        <img id="pvImage" src="" alt="">
      </div>
      <button class="pv-nav-btn pv-nav-next" onclick="pvNav(1)"><x-icon name="chevron_right" /></button>
      <div class="pv-counter" id="pvCounter"></div>
      <div class="pv-zoom-controls">
        <button class="pv-zoom-btn" onclick="pvZoom(-.25)" title="Zoom out"><x-icon name="zoom_out" :size="18" /></button>
        <button class="pv-zoom-btn" onclick="pvZoom(.25)" title="Zoom in"><x-icon name="zoom_in" :size="18" /></button>
        <button class="pv-zoom-btn" onclick="pvReset()" title="Reset"><x-icon name="aspect_ratio" :size="16" /></button>
      </div>
      <div class="pv-zoom-level" id="pvZoomLevel">100%</div>
    </div>
    <div class="pv-sidebar" id="pvSidebar">
      <div class="pv-sidebar-head" id="pvSidebarHead"></div>
      <div class="pv-comments" id="pvComments">
        <div class="pv-loading"><div class="spinner"></div></div>
      </div>
      <div class="pv-sidebar-foot" id="pvSidebarFoot">
        <button class="pv-like-btn" id="pvLikeBtn" onclick="pvToggleLike()">
          <x-icon name="favorite" />
          <span id="pvLikeCount">0</span>
        </button>
        <div class="pv-comment-form">
          <input type="text" id="pvCommentInput" placeholder="Write a comment..." maxlength="1000">
          <button class="btn btn-filled btn-sm" onclick="pvSubmitComment()">Post</button>
        </div>
      </div>
    </div>
  </div>
</div>

@stack('scripts')
<script>
// ── Theme Management ──
function applyTheme(theme) {
  const root = document.documentElement;
  if (theme === 'auto') {
    root.removeAttribute('data-theme');
  } else {
    root.setAttribute('data-theme', theme);
  }
  document.querySelectorAll('.theme-option-btn').forEach(btn => {
    btn.style.borderColor = '';
    btn.style.background = '';
    const icon = btn.querySelector('.icon');
    if (icon) icon.style.color = '';
  });
  document.querySelectorAll('.theme-option-btn').forEach(btn => {
    const title = btn.getAttribute('title');
    if (title === theme) {
      btn.style.borderColor = 'var(--md-primary)';
      btn.style.background = 'var(--md-primary-container)';
      const icon = btn.querySelector('.icon');
      if (icon) icon.style.color = 'var(--md-on-primary-container)';
    }
  });
}

function setTheme(theme) {
  applyTheme(theme);
  @auth
  fetch('{{ route("settings.theme") }}', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ theme: theme })
  });
  @else
  try { localStorage.setItem('yatri-theme', theme); } catch(e) {}
  @endauth
}
@guest
/* Restore guest theme on load */
try {
  const savedTheme = localStorage.getItem('yatri-theme');
  if (savedTheme) applyTheme(savedTheme);
} catch(e) {}
@endguest

// ── Profile Dropdown ──
function toggleProfileDropdown() {
  const dd = document.getElementById('profile-dropdown');
  dd.classList.toggle('open');
}
document.addEventListener('click', function(e) {
  const wrap = document.getElementById('profile-dropdown-wrap');
  const dd = document.getElementById('profile-dropdown');
  if (wrap && dd && !wrap.contains(e.target)) {
    dd.classList.remove('open');
  }
});

// ── Mobile Menu Button ──
function checkMobile() {
  const btn = document.getElementById('menu-btn');
  if (btn) {
    btn.style.display = window.innerWidth <= 760 ? 'flex' : 'none';
  }
}
checkMobile();
window.addEventListener('resize', checkMobile);

// ── Mobile Drawer ──
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelector('.nav-drawer').classList.remove('open');
  }
});

// ── Notification Polling ──
@auth
function checkNotifications() {
  fetch('{{ route("notifications.unreadCount") }}')
    .then(r => r.json())
    .then(data => {
      document.querySelectorAll('.badge, .nav-badge').forEach(badge => {
        if (data.count > 0) {
          badge.textContent = data.count;
          badge.setAttribute('data-count', data.count);
          badge.style.display = 'flex';
        } else {
          badge.setAttribute('data-count', '0');
          badge.style.display = 'none';
        }
      });
    });
}
checkNotifications();
setInterval(checkNotifications, 30000);
@endauth
</script>
<script>
// ── Social Interaction Functions ──
function sharePost(postId, url, title) {
  var track = function() {
    fetch('/posts/' + postId + '/share', { method:'POST', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json' } })
      .then(function(r) { if (r.redirected) { window.location.href = r.url; return; } return r.json(); })
      .then(function(d) { if (d && d.shares) { var el = document.getElementById('share-count-' + postId); if (el) el.textContent = d.shares; } })
      .catch(function() {});
  };
  if (navigator.share) {
    navigator.share({ title, url }).then(track).catch(function() {});
  } else {
    navigator.clipboard.writeText(url).then(function() {
      track();
      var btn = event.target.closest('.pcard-action') || event.target;
      var orig = btn.innerHTML;
      btn.innerHTML = '<svg class="icon" width="20" height="20" viewBox="0 -960 960 960" fill="currentColor"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/><\/svg> Copied';
      setTimeout(function() { btn.innerHTML = orig; }, 2000);
    }).catch(function() {});
  }
}
function toggleLike(postId) {
  fetch('/posts/' + postId + '/like', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json'
    }
  })
  .then(r => { if (r.redirected) { window.location.href = r.url; return; } return r.json(); })
  .then(data => {
    if (!data) return;
    const btn = document.querySelector('[data-post-id="' + postId + '"]');
    const countEl = btn.querySelector('.like-count');
    const icon = btn.querySelector('.icon');
    if (data.liked) {
      btn.classList.add('liked');
          } else {
      btn.classList.remove('liked');
          }
    countEl.textContent = data.count;
  })
  .catch(() => {});
}

function toggleComments(postId) {
  const el = document.getElementById('comments-' + postId);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function submitComment(e, postId) {
  e.preventDefault();
  const form = e.target;
  const input = form.querySelector('input');
  const body = input.value.trim();
  if (!body) return;
  fetch('/posts/' + postId + '/comment', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ body })
  })
  .then(r => { if (r.redirected) { window.location.href = r.url; return; } return r.json(); })
  .then(data => {
    if (!data) return;
    const list = document.getElementById('comment-list-' + postId);
    const html = '<div class="comment-item"><a href="/u/' + data.comment.user.name + '"><img src="' + (data.comment.user.avatar_url || 'https://ui-avatars.com/api/?background=c2412c&color=fff&name=' + encodeURIComponent(data.comment.user.name)) + '" alt="" class="comment-avatar" width="32" height="32"></a><div class="comment-content"><div class="comment-header"><a href="/u/' + data.comment.user.name + '"><strong>' + data.comment.user.name + '</strong></a><span class="muted" style="font-size:11px">just now</span></div><p class="comment-body">' + data.comment.body + '</p></div></div>';
    list.insertAdjacentHTML('beforeend', html);
    input.value = '';
  })
  .catch(() => {});
}

function toggleReplyForm(commentId) {
  const form = document.getElementById('reply-form-' + commentId);
  form.style.display = form.style.display === 'none' ? 'flex' : 'none';
}

function submitReply(e, commentId) {
  e.preventDefault();
  const form = e.target;
  const input = form.querySelector('input');
  const body = input.value.trim();
  if (!body) return;
  fetch('/comments/' + commentId + '/reply', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ body })
  })
  .then(r => r.json())
  .then(data => {
    let container = form.previousElementSibling;
    if (!container || !container.classList.contains('comment-replies')) {
      const newContainer = document.createElement('div');
      newContainer.className = 'comment-replies';
      form.parentNode.insertBefore(newContainer, form);
      container = form.previousElementSibling;
    }
    const html = '<div class="reply-item"><a href="/u/' + data.reply.user.name + '"><img src="' + (data.reply.user.avatar_url || 'https://ui-avatars.com/api/?background=c2412c&color=fff&name=' + encodeURIComponent(data.reply.user.name)) + '" alt="" class="reply-avatar" width="24" height="24"></a><div class="reply-content"><div class="reply-header"><a href="/u/' + data.reply.user.name + '"><strong>' + data.reply.user.name + '</strong></a><span class="muted" style="font-size:11px">just now</span></div><p class="reply-body">' + data.reply.body + '</p></div></div>';
    container.insertAdjacentHTML('beforeend', html);
    input.value = '';
    form.style.display = 'none';
  });
}
</script>
<script>window.YATRI_GEO=document.body.dataset.geo;</script>
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
      fetch(document.body.dataset.geoUrl+'?q='+encodeURIComponent(q))
        .then(function(r){return r.json();}).then(render).catch(close);
    }, 250);
  });
  input.addEventListener('blur', function(){ setTimeout(close, 150); });
}

function initYatriPlaces(){
  document.querySelectorAll('[data-places]').forEach(function(el){ attachPlaces(el); });
}
if(window.YATRI_GEO!=='google'){
  if(document.readyState!=='loading') initYatriPlaces();
  else document.addEventListener('DOMContentLoaded', initYatriPlaces);
}else if(window.google && window.google.maps && window.google.maps.places){
  initYatriPlaces();
}
</script>

{{-- Post viewer JS --}}
<script>
let pvData = null, pvIndex = 0, pvZoomLvl = 1, pvPanX = 0, pvPanY = 0, pvIsDragging = false, pvDragStart = {x:0,y:0};

function openPostViewer(postId) {
  const viewer = document.getElementById('postViewer');
  viewer.classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('pvComments').innerHTML = '<div class="pv-loading"><div class="spinner"></div></div>';
  document.getElementById('pvSidebarHead').innerHTML = '';
  document.getElementById('pvLikeBtn').classList.remove('liked');
  pvReset();

  fetch('/posts/viewer/' + postId)
    .then(r => r.json())
    .then(data => {
      pvData = data;
      pvIndex = 0;
      renderViewer();
    })
    .catch(() => {
      document.getElementById('pvComments').innerHTML = '<div class="pv-comments-empty">Failed to load post.</div>';
    });
}

function closePostViewer() {
  document.getElementById('postViewer').classList.remove('open');
  document.body.style.overflow = '';
  pvData = null;
}

function renderViewer() {
  if (!pvData || !pvData.images.length) return;
  const img = pvData.images[pvIndex];
  document.getElementById('pvImage').src = img.url;
  document.getElementById('pvCounter').textContent = (pvIndex + 1) + ' / ' + pvData.images.length;

  /* Show/hide nav buttons */
  document.querySelectorAll('.pv-nav-btn').forEach(b => b.style.display = pvData.images.length > 1 ? '' : 'none');

  /* Sidebar head */
  document.getElementById('pvSidebarHead').innerHTML = '<img src="' + pvData.author.avatar + '" alt="" width="36" height="36"><a href="' + pvData.author.url + '">' + pvData.author.name + '</a>';

  /* Like */
  const likeBtn = document.getElementById('pvLikeBtn');
  likeBtn.classList.toggle('liked', pvData.liked);
  document.getElementById('pvLikeCount').textContent = pvData.likes_count;

  /* Comment input visibility */
  document.getElementById('pvCommentInput').disabled = !pvData.can_comment;
  document.getElementById('pvCommentInput').placeholder = pvData.can_comment ? 'Write a comment...' : 'Log in to comment';

  /* Comments */
  renderComments();
}

function renderComments() {
  const el = document.getElementById('pvComments');
  if (!pvData.comments.length) {
    el.innerHTML = '<div class="pv-comments-empty">No comments yet. Be the first.</div>';
    return;
  }
  el.innerHTML = pvData.comments.map(c =>
    '<div class="pv-comment">' +
      '<img src="' + c.user.avatar + '" alt="" width="30" height="30">' +
      '<div class="pv-comment-body">' +
        '<a class="pv-comment-author" href="/u/' + c.user.name + '">' + c.user.name + '</a>' +
        '<div class="pv-comment-text">' + c.body.replace(/</g, '&lt;') + '</div>' +
        '<div class="pv-comment-time">' + c.created_at + '</div>' +
      '</div>' +
    '</div>'
  ).join('');
  requestAnimationFrame(function() { el.scrollTop = el.scrollHeight; });
}

function pvNav(dir) {
  if (!pvData || !pvData.images.length) return;
  pvIndex = (pvIndex + dir + pvData.images.length) % pvData.images.length;
  pvReset();
  renderViewer();
}

/* Zoom */
function pvZoom(delta) {
  pvZoomLvl = Math.max(0.25, Math.min(5, pvZoomLvl + delta));
  applyZoom();
}

function pvReset() {
  pvZoomLvl = 1; pvPanX = 0; pvPanY = 0;
  applyZoom();
}

function applyZoom() {
  const img = document.getElementById('pvImage');
  img.style.transform = 'scale(' + pvZoomLvl + ') translate(' + pvPanX + 'px, ' + pvPanY + 'px)';
  document.getElementById('pvZoomLevel').textContent = Math.round(pvZoomLvl * 100) + '%';
}

/* Pan */
const wrap = document.getElementById('pvImageWrap');
wrap.addEventListener('mousedown', function(e) {
  if (pvZoomLvl <= 1) return;
  pvIsDragging = true;
  pvDragStart = {x: e.clientX - pvPanX, y: e.clientY - pvPanY};
  wrap.classList.add('dragging');
  e.preventDefault();
});
document.addEventListener('mousemove', function(e) {
  if (!pvIsDragging) return;
  pvPanX = e.clientX - pvDragStart.x;
  pvPanY = e.clientY - pvDragStart.y;
  applyZoom();
});
document.addEventListener('mouseup', function() {
  pvIsDragging = false;
  wrap.classList.remove('dragging');
});

/* Scroll to zoom */
wrap.addEventListener('wheel', function(e) {
  e.preventDefault();
  pvZoom(e.deltaY > 0 ? -0.1 : 0.1);
}, {passive: false});

/* Double-click to zoom toggle */
wrap.addEventListener('dblclick', function(e) {
  if (pvZoomLvl > 1.5) { pvReset(); }
  else { pvZoom(1); }
});

/* Touch support */
let pvTouchDist = 0;
wrap.addEventListener('touchstart', function(e) {
  if (e.touches.length === 2) {
    pvTouchDist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
  }
}, {passive: true});
wrap.addEventListener('touchmove', function(e) {
  if (e.touches.length === 2) {
    e.preventDefault();
    const dist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
    const delta = (dist - pvTouchDist) * 0.01;
    pvZoom(delta);
    pvTouchDist = dist;
  }
}, {passive: false});

/* Keyboard */
document.addEventListener('keydown', function(e) {
  if (!document.getElementById('postViewer').classList.contains('open')) return;
  if (e.key === 'Escape') closePostViewer();
  if (e.key === 'ArrowLeft') pvNav(-1);
  if (e.key === 'ArrowRight') pvNav(1);
});

/* Like */
function pvToggleLike() {
  if (!pvData) return;
  fetch('/posts/' + pvData.id + '/like', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json'
    }
  })
  .then(r => { if (r.redirected) { window.location.href = r.url; return; } return r.json(); })
  .then(data => {
    if (!data) return;
    pvData.liked = data.liked;
    pvData.likes_count = data.count;
    document.getElementById('pvLikeBtn').classList.toggle('liked', data.liked);
    document.getElementById('pvLikeCount').textContent = data.count;
    /* Also update the card's like button if present */
    const cardBtn = document.querySelector('[data-post-id="' + pvData.id + '"]');
    if (cardBtn) {
      const icon = cardBtn.querySelector('.icon');
      const countEl = cardBtn.querySelector('.like-count');
      cardBtn.classList.toggle('liked', data.liked);
            if (countEl) countEl.textContent = data.count;
    }
  })
  .catch(() => {});
}

/* Comment */
function pvSubmitComment() {
  const input = document.getElementById('pvCommentInput');
  const body = input.value.trim();
  if (!body || !pvData) return;
  fetch('/posts/' + pvData.id + '/comment', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ body })
  })
  .then(r => { if (r.redirected) { window.location.href = r.url; return; } return r.json(); })
  .then(data => {
    pvData.comments.push({
      id: data.comment.id,
      body: data.comment.body,
      created_at: 'just now',
      user: {
        name: data.comment.user.name,
        avatar: data.comment.user.avatar_url || 'https://ui-avatars.com/api/?background=c2412c&color=fff&name=' + encodeURIComponent(data.comment.user.name)
      }
    });
    pvData.comments_count++;
    renderComments();
    input.value = '';
    /* Also update the card comment count */
    const cardBtn = document.querySelector('[data-post-id="' + pvData.id + '"]');
    if (cardBtn) {
      const parent = cardBtn.closest('.pcard');
      if (parent) {
        const cc = parent.querySelector('.pcard-action:nth-child(2) span:last-child');
        if (cc) cc.textContent = pvData.comments_count;
      }
    }
  })
  .catch(() => { showSnackbar('Like failed.'); });
}

/* Enter to submit comment */
document.getElementById('pvCommentInput')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') { e.preventDefault(); pvSubmitComment(); }
});

/* ===== GLOBAL CURRENCY ===== */
const Yc = {
  _allRates: null,
  _sel: document.getElementById('globalCurrency'),
  _sym: {'USD':'$','INR':'₹','EUR':'€','GBP':'£','AED':'AED ','SGD':'S$','JPY':'¥'},
  get currency() { return localStorage.getItem('yatri_cur') || 'USD'; },
  async _loadRates() {
    if (this._allRates) return this._allRates;
    try { const r = await fetch('/api/fx'); const d = await r.json(); if (d.rates) { this._allRates = d.rates; return d.rates; } } catch(e) {}
    return {};
  },
  toUsd(amt, cur) {
    if (cur === 'USD' || !this._allRates) return amt;
    var r = this._allRates[cur.toLowerCase()];
    return r ? amt / r : amt;
  },
  fromUsd(amt, cur) {
    if (cur === 'USD' || !this._allRates) return amt;
    var r = this._allRates[cur.toLowerCase()];
    return r ? amt * r : amt;
  },
  async init() {
    if (this._sel) this._sel.value = this.currency;
    await this._loadRates();
    this.convertAll();
  },
  convertAll() {
    var cur = this.currency, sym = this._sym[cur] || (cur + ' ');
    var allR = this._allRates;
    var dispRate = allR ? (allR[cur.toLowerCase()] || 1) : 1;
    document.querySelectorAll('.money[data-amt]').forEach(function(el) {
      var amt = parseFloat(el.dataset.amt), srcCur = el.dataset.cur || 'USD';
      if (isNaN(amt)) return;
      var usd = amt;
      if (srcCur !== 'USD' && allR) { var srcR = allR[srcCur.toLowerCase()]; if (srcR) usd = amt / srcR; }
      el.textContent = sym + Math.round(usd * dispRate).toLocaleString();
    });
  },
  set(val) {
    localStorage.setItem('yatri_cur', val);
    this.init();
  }
};
document.addEventListener('DOMContentLoaded', function() { Yc.init(); });

/* ===== CAROUSEL (post card scroll + trip card crossfade) ===== */
function carouselNav(id, dir) {
  var el = document.getElementById(id);
  if (!el) return;
  var step = el.querySelector('.c-item')?.offsetWidth || 300;
  el.scrollBy({ left: dir * step, behavior: 'smooth' });
  setTimeout(function() { updateDots(el); }, 350);
}
function carouselDot(id, idx) {
  var el = document.getElementById(id);
  if (!el) return;
  var step = el.querySelector('.c-item')?.offsetWidth || 300;
  el.scrollTo({ left: idx * step, behavior: 'smooth' });
  setTimeout(function() { updateDots(el); }, 350);
}
function updateDots(el) {
  var step = el.querySelector('.c-item')?.offsetWidth || 300;
  var idx = Math.round(el.scrollLeft / step);
  var dots = el.closest('.pcard')?.querySelectorAll('.carousel-dot');
  if (dots) dots.forEach(function(d, i) { d.classList.toggle('active', i === idx); });
}
/* Trip card crossfade carousel */
function carouselTrip(id, dir) {
  var el = document.getElementById(id);
  if (!el) return;
  var items = el.querySelectorAll('.c-item');
  var cur = Array.from(items).findIndex(function(i) { return i.classList.contains('active'); });
  var next = (cur + dir + items.length) % items.length;
  items[cur].classList.remove('active');
  items[next].classList.add('active');
  var dots = el.querySelectorAll('.carousel-dot');
  if (dots.length) { dots.forEach(function(d, i) { d.classList.toggle('active', i === next); }); }
}
function carouselTripDot(id, idx) {
  var el = document.getElementById(id);
  if (!el) return;
  var items = el.querySelectorAll('.c-item');
  items.forEach(function(i, n) { i.classList.toggle('active', n === idx); });
  var dots = el.querySelectorAll('.carousel-dot');
  if (dots.length) { dots.forEach(function(d, i) { d.classList.toggle('active', i === idx); }); }
}

/* ===== SNACKBAR ===== */
function showSnackbar(msg) {
  var sb = document.getElementById('snackbar');
  if (!sb) {
    sb = document.createElement('div');
    sb.id = 'snackbar';
    sb.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:10000;background:var(--md-inverse-surface, #2f3033);color:var(--md-inverse-on-surface, #f1f0f4);padding:12px 24px;border-radius:var(--md-shape-sm, 8px);font-size:14px;font-weight:500;box-shadow:var(--md-elevation-3, 0 4px 8px 3px rgba(0,0,0,.15));transition:opacity .3s,transform .3s;opacity:0;transform:translateX(-50%) translateY(16px);pointer-events:none;max-width:90vw;text-align:center;font-family:Poppins,system-ui,sans-serif';
    document.body.appendChild(sb);
  }
  sb.textContent = msg;
  sb.style.opacity = '1';
  sb.style.transform = 'translateX(-50%) translateY(0)';
  clearTimeout(sb._t);
  sb._t = setTimeout(function() {
    sb.style.opacity = '0';
    sb.style.transform = 'translateX(-50%) translateY(16px)';
  }, 3000);
}
</script>
<div id="snackbar" style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(16px);z-index:10000;background:var(--md-inverse-surface, #2f3033);color:var(--md-inverse-on-surface, #f1f0f4);padding:12px 24px;border-radius:var(--md-shape-sm, 8px);font-size:14px;font-weight:500;box-shadow:var(--md-elevation-3);opacity:0;pointer-events:none;max-width:90vw;text-align:center;font-family:Poppins,system-ui,sans-serif;transition:opacity .3s,transform .3s"></div>
</body>
</html>
