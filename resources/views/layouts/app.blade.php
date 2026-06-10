<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->check() ? auth()->user()->theme : 'auto' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="@yield('meta_description', \App\Models\Setting::get('site_name', 'Yatri') . ' — AI-powered travel planner and social network for travelers')">
<title>@yield('title', \App\Models\Setting::get('site_name', 'Yatri') . ' — AI budget trip planner')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
<link rel="icon" href="{{ asset('storage/images/favicon.ico') }}?v={{ config('app.version') }}" sizes="any">
<link rel="icon" href="{{ asset('storage/images/favicon-32x32.png') }}?v={{ config('app.version') }}" sizes="32x32" type="image/png">
<link rel="icon" href="{{ asset('storage/images/favicon-16x16.png') }}?v={{ config('app.version') }}" sizes="16x16" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('storage/images/apple-touch-icon.png') }}?v={{ config('app.version') }}" sizes="180x180">
<link rel="manifest" href="{{ asset('site.webmanifest') }}?v={{ config('app.version') }}">
<meta name="theme-color" content="#0f6dfc">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Yatri">
<meta property="og:image" content="{{ asset('storage/images/yatri-icon.png') }}?v={{ config('app.version') }}">
<meta property="og:image:width" content="256">
<meta property="og:image:height" content="256">
<meta name="twitter:card" content="summary">
<meta name="twitter:image" content="{{ asset('storage/images/yatri-icon.png') }}?v={{ config('app.version') }}">
@stack('head')
<link rel="stylesheet" href="{{ asset('css/yatri.css') }}?v={{ config('app.version') }}">
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
<body data-geo="{{ $geoProvider }}" data-geo-url="{{ route('geo.suggest') }}" class="@auth has-bottom-nav @endauth" @auth @if(auth()->user()->theme === 'dark' || (auth()->user()->theme === 'auto' && request()->cookie('theme-pref') === 'dark')) style="background:var(--md-surface)" @endif @endauth>
@php $currentPage = request()->route()->getName(); @endphp

{{-- ═══ TOP APP BAR ═══ --}}
<header class="topbar"><div class="wrap">
  <div class="topbar-left">
    <button class="icon-btn" aria-label="Menu" onclick="document.querySelector('.nav-drawer').classList.add('open')" style="display:none" id="menu-btn">
      <span class="material-symbols-outlined">menu</span>
    </button>
    <a class="topbar-brand" href="{{ route('home') }}"><span class="dot"></span>{{ \App\Models\Setting::get('site_name', 'Yatri') }}</a>
  </div>
  <nav class="topbar-nav">
    <a href="{{ route('home') }}" @if($currentPage === 'home') style="color:var(--md-primary)" @endif>
      <span class="material-symbols-outlined md-20">explore</span> Explore
    </a>
    <a href="{{ route('rankings') }}" @if($currentPage === 'rankings') style="color:var(--md-primary)" @endif>
      <span class="material-symbols-outlined md-20">leaderboard</span> Rankings
    </a>
    <a href="{{ route('pricing') }}" @if($currentPage === 'pricing') style="color:var(--md-primary)" @endif>
      <span class="material-symbols-outlined md-20">payments</span> Pricing
    </a>
  </nav>
  <div class="topbar-right">
    @stack('nav-right')
    @auth
      <a class="btn btn-filled btn-sm" href="{{ route('planner') }}" style="display:flex;align-items:center;gap:6px">
        <span class="material-symbols-outlined md-18">add</span> Plan a trip
      </a>
      <a class="icon-btn" href="{{ route('notifications.index') }}" aria-label="Notifications">
        <span class="material-symbols-outlined">notifications</span>
        @if($unreadCount > 0)<span class="badge" data-count="{{ $unreadCount }}">{{ $unreadCount }}</span>@endif
      </a>
      <div class="profile-dropdown-wrap" id="profile-dropdown-wrap">
        <img src="{{ auth()->user()->avatar() }}" alt="" class="topbar-avatar" onclick="toggleProfileDropdown()" id="profile-avatar-btn">
        <div class="profile-dropdown" id="profile-dropdown">
          <div class="profile-dropdown-user">
            <img src="{{ auth()->user()->avatar() }}" alt="">
            <div>
              <div class="name">{{ auth()->user()->name }}</div>
              <div class="email">{{ auth()->user()->email }}</div>
            </div>
          </div>
          <a class="profile-dropdown-item" href="{{ route('profile', auth()->user()) }}">
            <span class="material-symbols-outlined">person</span> My profile
          </a>
          <a class="profile-dropdown-item" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined">dashboard</span> Dashboard
          </a>
          <a class="profile-dropdown-item" href="{{ route('settings') }}">
            <span class="material-symbols-outlined">settings</span> Settings
          </a>
          @if(auth()->user()->isAdmin())
          <a class="profile-dropdown-item" href="{{ route('admin.dashboard') }}">
            <span class="material-symbols-outlined">admin_panel_settings</span> Admin
          </a>
          @endif
          <div class="profile-dropdown-divider"></div>
          <div style="padding:6px 12px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--md-on-surface-variant);margin-bottom:6px">Theme</div>
            <div class="theme-options" style="display:flex;gap:4px">
              <button class="theme-option-btn {{ (auth()->user()->theme ?? 'auto') === 'light' ? 'active' : '' }}" onclick="setTheme('light')" title="Light" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <span class="material-symbols-outlined" style="font-size:20px;color:var(--md-on-surface-variant)">light_mode</span>
              </button>
              <button class="theme-option-btn {{ (auth()->user()->theme ?? 'auto') === 'dark' ? 'active' : '' }}" onclick="setTheme('dark')" title="Dark" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <span class="material-symbols-outlined" style="font-size:20px;color:var(--md-on-surface-variant)">dark_mode</span>
              </button>
              <button class="theme-option-btn {{ (auth()->user()->theme ?? 'auto') === 'auto' ? 'active' : '' }}" onclick="setTheme('auto')" title="Auto" style="flex:1;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-sm);background:var(--md-surface-container);cursor:pointer;transition:all .15s">
                <span class="material-symbols-outlined" style="font-size:20px;color:var(--md-on-surface-variant)">contrast</span>
              </button>
            </div>
          </div>
          <div class="profile-dropdown-divider"></div>
          <form method="post" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button type="submit" class="profile-dropdown-item">
              <span class="material-symbols-outlined">logout</span> Log out
            </button>
          </form>
        </div>
      </div>
    @else
      <a class="btn btn-text btn-sm" href="{{ route('login') }}">Log in</a>
      <a class="btn btn-filled btn-sm" href="{{ route('register') }}">Sign up</a>
    @endauth
  </div>
</div></header>

{{-- ═══ NAVIGATION DRAWER (Mobile Sidebar) ═══ --}}
<div class="nav-drawer">
  <div class="nav-drawer-overlay" onclick="this.parentElement.classList.remove('open')"></div>
  <div class="nav-drawer-panel">
    <div class="nav-drawer-header">
    <a class="topbar-brand" href="{{ route('home') }}"><img src="{{ asset('storage/images/yatri-logo.png') }}?v={{ config('app.version') }}" alt="Yatri" height="28"></a>
      <button class="nav-drawer-close" aria-label="Close" onclick="document.querySelector('.nav-drawer').classList.remove('open')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="nav-drawer-links">
      @auth
        <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;margin-bottom:8px">
          <img src="{{ auth()->user()->avatar() }}" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
          <div>
            <div style="font-weight:600;font-size:14px">{{ auth()->user()->name }}</div>
            <div style="font-size:12px;color:var(--md-on-surface-variant)">{{ auth()->user()->email }}</div>
          </div>
        </div>
        <div class="nav-drawer-divider"></div>
      @endauth

      <a class="nav-drawer-item @if($currentPage === 'home') active @endif" href="{{ route('home') }}">
        <span class="material-symbols-outlined">explore</span> Explore
      </a>
      <a class="nav-drawer-item @if($currentPage === 'rankings') active @endif" href="{{ route('rankings') }}">
        <span class="material-symbols-outlined">leaderboard</span> Rankings
      </a>
      <a class="nav-drawer-item @if($currentPage === 'pricing') active @endif" href="{{ route('pricing') }}">
        <span class="material-symbols-outlined">payments</span> Pricing
      </a>
      <a class="nav-drawer-item" href="{{ route('planner') }}">
        <span class="material-symbols-outlined">route</span> Plan a trip
      </a>

      @auth
        <div class="nav-drawer-divider"></div>
        <a class="nav-drawer-item" href="{{ route('posts.create') }}">
          <span class="material-symbols-outlined">add_circle</span> Create Post
        </a>
        <a class="nav-drawer-item @if($currentPage === 'dashboard') active @endif" href="{{ route('dashboard') }}">
          <span class="material-symbols-outlined">dashboard</span> My trips
        </a>
        <a class="nav-drawer-item @if($currentPage === 'profile') active @endif" href="{{ route('profile', auth()->user()) }}">
          <span class="material-symbols-outlined">person</span> Profile
        </a>
        <a class="nav-drawer-item @if($currentPage === 'notifications.index') active @endif" href="{{ route('notifications.index') }}">
          <span class="material-symbols-outlined">notifications</span> Notifications
          @if($unreadCount > 0)<span style="margin-left:auto;background:var(--md-error);color:var(--md-on-error);font-size:11px;font-weight:700;padding:2px 8px;border-radius:var(--md-shape-full)">{{ $unreadCount }}</span>@endif
        </a>
        @if(auth()->user()->isAdmin())
        <a class="nav-drawer-item" href="{{ route('admin.dashboard') }}">
          <span class="material-symbols-outlined">admin_panel_settings</span> Admin
        </a>
        @endif
        <div class="nav-drawer-divider"></div>
        <a class="nav-drawer-item" href="{{ route('settings') }}">
          <span class="material-symbols-outlined">settings</span> Settings
        </a>

        <div class="nav-drawer-footer">
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-drawer-item" style="width:100%;color:var(--md-error)">
              <span class="material-symbols-outlined">logout</span> Log out
            </button>
          </form>
        </div>
      @else
        <div class="nav-drawer-divider"></div>
        <a class="nav-drawer-item" href="{{ route('login') }}">
          <span class="material-symbols-outlined">login</span> Log in
        </a>
        <a class="nav-drawer-item" href="{{ route('register') }}">
          <span class="material-symbols-outlined">person_add</span> Sign up
        </a>
      @endauth
    </div>
  </div>
</div>

@if(session('ok'))<div class="wrap"><div class="flash flash-ok"><span class="material-symbols-outlined md-20">check_circle</span> {{ session('ok') }}</div></div>@endif
@if(session('error'))<div class="wrap"><div class="flash flash-err"><span class="material-symbols-outlined md-20">error</span> {{ session('error') }}</div></div>@endif

@yield('content')

<footer style="padding:32px 0 40px;color:var(--md-on-surface-variant);font-size:13px;text-align:center;border-top:1px solid var(--md-outline-variant)">
  <div class="wrap">
    {{ \App\Models\Setting::get('site_name', 'Yatri') }} · AI budget trip planner — itineraries grounded with live Google Search &amp; Maps data via Gemini. © {{ date('Y') }}
  </div>
</footer>

{{-- ═══ BOTTOM NAVIGATION BAR (Mobile M3) ═══ --}}
@auth
<nav class="bottom-nav" id="bottom-nav">
  <div class="bottom-nav-inner">
    <a class="bottom-nav-item @if($currentPage === 'home') active @endif" href="{{ route('home') }}">
      <span class="material-symbols-outlined">home</span>
      <span>Home</span>
    </a>
    <a class="bottom-nav-item @if(in_array($currentPage, ['dashboard','trip.show'])) active @endif" href="{{ route('dashboard') }}">
      <span class="material-symbols-outlined">map</span>
      <span>Trips</span>
    </a>
    <a class="bottom-nav-fab" href="{{ route('planner') }}">
      <div class="fab-circle">
        <span class="material-symbols-outlined">add</span>
      </div>
      <span>Plan</span>
    </a>
    <a class="bottom-nav-item @if($currentPage === 'notifications.index') active @endif" href="{{ route('notifications.index') }}">
      <span class="material-symbols-outlined">notifications</span>
      @if($unreadCount > 0)<span class="nav-badge" data-count="{{ $unreadCount }}">{{ $unreadCount }}</span>@endif
      <span>Alerts</span>
    </a>
    <a class="bottom-nav-item @if($currentPage === 'profile') active @endif" href="{{ route('profile', auth()->user()) }}">
      <span class="material-symbols-outlined">person</span>
      <span>Profile</span>
    </a>
  </div>
</nav>
@endauth

{{-- Post image viewer --}}
<div class="post-viewer" id="postViewer">
  <button class="pv-close" onclick="closePostViewer()"><span class="material-symbols-outlined">close</span></button>
  <div class="pv-body">
    <div class="pv-image-panel">
      <button class="pv-nav-btn pv-nav-prev" onclick="pvNav(-1)"><span class="material-symbols-outlined">chevron_left</span></button>
      <div class="pv-image-wrap" id="pvImageWrap">
        <img id="pvImage" src="" alt="">
      </div>
      <button class="pv-nav-btn pv-nav-next" onclick="pvNav(1)"><span class="material-symbols-outlined">chevron_right</span></button>
      <div class="pv-counter" id="pvCounter"></div>
      <div class="pv-zoom-controls">
        <button class="pv-zoom-btn" onclick="pvZoom(-.25)" title="Zoom out"><span class="material-symbols-outlined" style="font-size:18px">zoom_out</span></button>
        <button class="pv-zoom-btn" onclick="pvZoom(.25)" title="Zoom in"><span class="material-symbols-outlined" style="font-size:18px">zoom_in</span></button>
        <button class="pv-zoom-btn" onclick="pvReset()" title="Reset"><span class="material-symbols-outlined" style="font-size:16px">aspect_ratio</span></button>
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
          <span class="material-symbols-outlined">favorite</span>
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
    const icon = btn.querySelector('.material-symbols-outlined');
    if (icon) icon.style.color = '';
  });
  document.querySelectorAll('.theme-option-btn').forEach(btn => {
    const title = btn.getAttribute('title');
    if (title === theme) {
      btn.style.borderColor = 'var(--md-primary)';
      btn.style.background = 'var(--md-primary-container)';
      const icon = btn.querySelector('.material-symbols-outlined');
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
  @endauth
}

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
function sharePost(url, title) {
  if (navigator.share) {
    navigator.share({ title, url }).catch(() => {});
  } else {
    navigator.clipboard.writeText(url).then(() => {
      const btn = event.target.closest('.pcard-action') || event.target;
      const orig = btn.innerHTML;
      btn.innerHTML = '<span class="material-symbols-outlined md-20">check</span> Copied';
      setTimeout(() => btn.innerHTML = orig, 2000);
    }).catch(() => {});
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
    const icon = btn.querySelector('.material-symbols-outlined');
    if (data.liked) {
      btn.classList.add('liked');
      if (icon) icon.classList.add('filled');
    } else {
      btn.classList.remove('liked');
      if (icon) icon.classList.remove('filled');
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
    const html = '<div class="comment-item"><a href="/u/' + data.comment.user.name + '"><img src="' + (data.comment.user.avatar_url || 'https://ui-avatars.com/api/?background=c2412c&color=fff&name=' + encodeURIComponent(data.comment.user.name)) + '" alt="" class="comment-avatar"></a><div class="comment-content"><div class="comment-header"><a href="/u/' + data.comment.user.name + '"><strong>' + data.comment.user.name + '</strong></a><span class="muted" style="font-size:11px">just now</span></div><p class="comment-body">' + data.comment.body + '</p></div></div>';
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
    const html = '<div class="reply-item"><a href="/u/' + data.reply.user.name + '"><img src="' + (data.reply.user.avatar_url || 'https://ui-avatars.com/api/?background=c2412c&color=fff&name=' + encodeURIComponent(data.reply.user.name)) + '" alt="" class="reply-avatar"></a><div class="reply-content"><div class="reply-header"><a href="/u/' + data.reply.user.name + '"><strong>' + data.reply.user.name + '</strong></a><span class="muted" style="font-size:11px">just now</span></div><p class="reply-body">' + data.reply.body + '</p></div></div>';
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
  document.getElementById('pvSidebarHead').innerHTML = '<img src="' + pvData.author.avatar + '" alt=""><a href="' + pvData.author.url + '">' + pvData.author.name + '</a>';

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
      '<img src="' + c.user.avatar + '" alt="">' +
      '<div class="pv-comment-body">' +
        '<a class="pv-comment-author" href="/u/' + c.user.name + '">' + c.user.name + '</a>' +
        '<div class="pv-comment-text">' + c.body.replace(/</g, '&lt;') + '</div>' +
        '<div class="pv-comment-time">' + c.created_at + '</div>' +
      '</div>' +
    '</div>'
  ).join('');
  el.scrollTop = el.scrollHeight;
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
      const icon = cardBtn.querySelector('.material-symbols-outlined');
      const countEl = cardBtn.querySelector('.like-count');
      cardBtn.classList.toggle('liked', data.liked);
      if (icon) icon.classList.toggle('filled', data.liked);
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
  .catch(() => {});
}

/* Enter to submit comment */
document.getElementById('pvCommentInput')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') { e.preventDefault(); pvSubmitComment(); }
});
</script>
</body>
</html>
