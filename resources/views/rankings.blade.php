@extends('layouts.app')
@section('title', 'Travel Rankings — Yatri')

@section('content')
<header class="hero" style="padding:50px 0 38px"><div class="wrap">
  <p class="eyebrow">Top travelers worldwide</p>
  <h1><strong>Travel Rankings</strong></h1>
  <div style="margin-top:20px;color:#d6d3d1;font-size:14px;display:flex;gap:20px;flex-wrap:wrap">
    <span>🌍 <strong style="color:#fff">{{ number_format($stats['total_countries']) }}</strong> destinations</span>
    <span>🗺️ <strong style="color:#fff">{{ number_format($stats['total_trips']) }}</strong> trips planned</span>
    <span>👥 <strong style="color:#fff">{{ number_format($stats['total_users']) }}</strong> travelers</span>
  </div>
</div></header>

<div class="wrap">
  <div style="display:flex;justify-content:space-between;align-items:center;margin:30px 0 14px;flex-wrap:wrap;gap:10px">
    <div class="seg">
      <label><input type="radio" name="period" value="all" {{ $period === 'all' ? 'checked' : '' }} onclick="location='{{ route('rankings', ['period'=>'all','category'=>$category]) }}'"><span>All Time</span></label>
      <label><input type="radio" name="period" value="year" {{ $period === 'year' ? 'checked' : '' }} onclick="location='{{ route('rankings', ['period'=>'year','category'=>$category]) }}'"><span>This Year</span></label>
      <label><input type="radio" name="period" value="month" {{ $period === 'month' ? 'checked' : '' }} onclick="location='{{ route('rankings', ['period'=>'month','category'=>$category]) }}'"><span>This Month</span></label>
    </div>

    <div class="seg">
      <label><input type="radio" name="category" value="overall" {{ $category === 'overall' ? 'checked' : '' }} onclick="location='{{ route('rankings', ['period'=>$period,'category'=>'overall']) }}'"><span>Overall</span></label>
      <label><input type="radio" name="category" value="kilometers" {{ $category === 'kilometers' ? 'checked' : '' }} onclick="location='{{ route('rankings', ['period'=>$period,'category'=>'kilometers']) }}'"><span>Kilometers</span></label>
      <label><input type="radio" name="category" value="days" {{ $category === 'days' ? 'checked' : '' }} onclick="location='{{ route('rankings', ['period'=>$period,'category'=>'days']) }}'"><span>Days</span></label>
      <label><input type="radio" name="category" value="followers" {{ $category === 'followers' ? 'checked' : '' }} onclick="location='{{ route('rankings', ['period'=>$period,'category'=>'followers']) }}'"><span>Followers</span></label>
      <label><input type="radio" name="category" value="likes" {{ $category === 'likes' ? 'checked' : '' }} onclick="location='{{ route('rankings', ['period'=>$period,'category'=>'likes']) }}'"><span>Likes</span></label>
    </div>
  </div>

  @if($travelers->count())
    <div class="rankings-grid">
      @foreach($travelers as $index => $traveler)
        <div class="ranking-card {{ $index < 3 ? 'ranking-top' : '' }}">
          <div class="ranking-position">
            @if($index === 0)
              <span class="rank-badge gold">🥇</span>
            @elseif($index === 1)
              <span class="rank-badge silver">🥈</span>
            @elseif($index === 2)
              <span class="rank-badge bronze">🥉</span>
            @else
              <span class="rank-num">#{{ $index + 1 }}</span>
            @endif
          </div>
          
          <a href="{{ route('profile', $traveler) }}" class="ranking-user">
            <img src="{{ $traveler->avatar() }}" alt="" class="ranking-avatar">
            <div>
              <strong>{{ $traveler->name }}</strong>
              @if($traveler->bio)
                <span class="muted" style="font-size:12px;display:block">{{ Str::limit($traveler->bio, 50) }}</span>
              @endif
            </div>
          </a>

          <div class="ranking-stats">
            <div class="ranking-stat">
              <span class="stat-value">{{ number_format($traveler->trips_count) }}</span>
              <span class="stat-label">Trips</span>
            </div>
            <div class="ranking-stat">
              <span class="stat-value">{{ number_format($traveler->total_days_traveled) }}</span>
              <span class="stat-label">Days</span>
            </div>
            <div class="ranking-stat">
              <span class="stat-value">{{ number_format($traveler->total_kilometers) }}</span>
              <span class="stat-label">KM</span>
            </div>
            <div class="ranking-stat">
              <span class="stat-value">{{ number_format($traveler->followers_count) }}</span>
              <span class="stat-label">Followers</span>
            </div>
            <div class="ranking-stat">
              <span class="stat-value">{{ number_format($traveler->total_likes_received) }}</span>
              <span class="stat-label">Likes</span>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="block center">
      <p class="lead">No rankings available yet. Be the first to plan a trip!</p>
      <a class="btn btn-accent" href="{{ route('planner') }}">Plan a Trip</a>
    </div>
  @endif
</div>

<style>
.rankings-grid { display:flex;flex-direction:column;gap:12px;margin:24px 0 }
.ranking-card { display:flex;align-items:center;gap:16px;background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:18px 22px;box-shadow:var(--shadow);transition:transform .2s,box-shadow .2s }
.ranking-card:hover { transform:translateY(-2px);box-shadow:var(--shadow-lg) }
.ranking-top { background:linear-gradient(135deg,#fef9e7,#fff);border-color:#fde68a }
.ranking-position { min-width:50px;text-align:center }
.rank-badge { font-size:28px }
.rank-num { font-family:'Outfit';font-weight:700;font-size:18px;color:var(--muted) }
.ranking-user { display:flex;align-items:center;gap:12px;color:inherit;text-decoration:none;flex:1 }
.ranking-avatar { width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--line) }
.ranking-top .ranking-avatar { border-color:#f59e0b }
.ranking-stats { display:flex;gap:16px }
.ranking-stat { text-align:center;min-width:60px }
.stat-value { display:block;font-family:'Outfit';font-weight:700;font-size:16px;color:var(--ink) }
.stat-label { display:block;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em }

@media (max-width: 768px) {
  .ranking-stats { gap:8px }
  .ranking-stat { min-width:50px }
  .stat-value { font-size:14px }
}
</style>
@endsection