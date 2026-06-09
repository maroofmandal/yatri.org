@extends('layouts.app')
@section('title', 'My Dashboard — Yatri')

@section('content')
<div class="wrap" style="padding-top:36px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div>
      <h2>My Dashboard</h2>
      <p class="lead">Your trips, posts, and travel stats.</p>
    </div>
    <div style="display:flex;gap:8px">
      <a class="btn btn-ghost" href="{{ route('posts.create') }}">+ Post</a>
      <a class="btn btn-accent" href="{{ route('planner') }}">+ New trip</a>
    </div>
  </div>

  <div class="profile-tabs" style="margin-top:32px">
    <button class="profile-tab active" onclick="showDashboardTab('trips')">🗺️ Trips <span class="tab-count">{{ $trips->count() }}</span></button>
    <button class="profile-tab" onclick="showDashboardTab('posts')">📝 Posts <span class="tab-count">{{ $posts->count() }}</span></button>
  </div>

  <div class="tab-content" id="dash-tab-trips">
    @if($trips->isEmpty())
      <div class="block center">
        <p class="lead">No trips yet.</p>
        <a class="btn btn-primary" href="{{ route('planner') }}">Plan your first trip</a>
      </div>
    @else
      <div class="grid grid-3 mt">
        @foreach($trips as $t)
          <a class="card" href="{{ route('trip.show', $t) }}" style="color:inherit">
            <h3>{{ $t->title }}</h3>
            <p class="muted" style="font-size:13.5px;margin:6px 0">{{ $t->origin }} · {{ $t->days }} days · {{ $t->travelers }} pax</p>
            <span class="tag">{{ strtoupper($t->currency) }} {{ number_format($t->budget_total) }}</span>
            <span class="tag" style="background:#f1f5f9;color:#334155">{{ ucfirst($t->status) }}</span>
          </a>
        @endforeach
      </div>
    @endif
  </div>

  <div class="tab-content" id="dash-tab-posts" style="display:none">
    @if($posts->isEmpty())
      <div class="block center">
        <p class="lead">No posts yet.</p>
        <a class="btn btn-accent" href="{{ route('posts.create') }}">Create your first post</a>
      </div>
    @else
      <div class="posts-feed mt">
        @foreach($posts as $post)
          @include('partials.post-card')
        @endforeach
      </div>
    @endif
  </div>
</div>

<style>
.profile-tabs { display:flex;gap:8px;border-bottom:2px solid var(--line);padding-bottom:0 }
.profile-tab { padding:12px 20px;border:none;background:none;cursor:pointer;font-family:'Outfit';font-weight:600;font-size:14px;color:var(--muted);border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .2s }
.profile-tab:hover { color:var(--ink) }
.profile-tab.active { color:var(--accent);border-bottom-color:var(--accent) }
.tab-count { background:var(--bg);padding:2px 8px;border-radius:20px;font-size:12px;margin-left:6px }
</style>

@push('scripts')
<script>
function showDashboardTab(tab) {
  document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.profile-tab').forEach(el => el.classList.remove('active'));
  
  document.getElementById('dash-tab-' + tab).style.display = 'block';
  event.target.classList.add('active');
}
</script>
@endpush
@endsection