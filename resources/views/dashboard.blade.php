@extends('layouts.app')
@section('title', 'My Dashboard — Yatri')

@section('content')
<div class="wrap" style="padding-top:36px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div>
      <h2 style="margin:0">My Dashboard</h2>
      <p class="lead">Your trips, posts, and travel stats.</p>
    </div>
    <div style="display:flex;gap:8px">
      <a class="btn btn-outlined" href="{{ route('posts.create') }}">
        <span class="material-symbols-outlined md-18">add</span> Post
      </a>
      <a class="btn btn-filled" href="{{ route('planner') }}">
        <span class="material-symbols-outlined md-18">add</span> New trip
      </a>
    </div>
  </div>

  <div class="profile-tabs" style="margin-top:32px">
    <button class="profile-tab active" onclick="showDashboardTab('trips')">
      <span class="material-symbols-outlined">map</span> Trips <span class="tab-count">{{ $trips->count() }}</span>
    </button>
    <button class="profile-tab" onclick="showDashboardTab('posts')">
      <span class="material-symbols-outlined">article</span> Posts <span class="tab-count">{{ $posts->count() }}</span>
    </button>
  </div>

  <div class="tab-content" id="dash-tab-trips">
    @if($trips->isEmpty())
      <div class="block center">
        <span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">map</span>
        <p class="lead">No trips yet.</p>
        <a class="btn btn-filled" href="{{ route('planner') }}">Plan your first trip</a>
      </div>
    @else
      <div class="grid grid-3 mt">
        @foreach($trips as $t)
          <a class="card" href="{{ route('trip.show', $t) }}" style="color:inherit;text-decoration:none">
            <h3 style="margin:0 0 6px">{{ $t->title }}</h3>
            <p class="muted" style="font-size:13px;margin:0 0 10px">{{ $t->origin }} · {{ $t->days }} days · {{ $t->travelers }} pax</p>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <span class="tag">{{ strtoupper($t->currency) }} {{ number_format($t->budget_total) }}</span>
              <span class="tag" style="background:var(--md-surface-container-high);color:var(--md-on-surface-variant)">{{ ucfirst($t->status) }}</span>
            </div>
          </a>
        @endforeach
      </div>
    @endif
  </div>

  <div class="tab-content" id="dash-tab-posts" style="display:none">
    @if($posts->isEmpty())
      <div class="block center">
        <span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">article</span>
        <p class="lead">No posts yet.</p>
        <a class="btn btn-filled" href="{{ route('posts.create') }}">Create your first post</a>
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

@push('scripts')
<script>
function showDashboardTab(tab) {
  document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.profile-tab').forEach(el => el.classList.remove('active'));
  document.getElementById('dash-tab-' + tab).style.display = 'block';
  event.target.closest('.profile-tab').classList.add('active');
}
</script>
@endpush
@endsection
