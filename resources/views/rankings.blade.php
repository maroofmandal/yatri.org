@extends('layouts.app')
@section('title', 'Rankings — Yatri')

@section('content')
<header class="hero" style="padding:44px 0 36px"><div class="wrap">
  <p class="eyebrow">Community</p>
  <h1 style="margin:0"><strong>Rankings</strong></h1>
  <p class="sub" style="margin-top:8px;color:rgba(255,255,255,.85)">Top travelers by contribution and activity.</p>
</div></header>

<div class="wrap" style="padding-top:28px;padding-bottom:80px">
  <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;margin-bottom:24px">
    <div class="seg">
      <label><input type="radio" name="period" value="all" {{ $period==='all'?'checked':'' }} onclick="location='{{ route('rankings',['period'=>'all','category'=>$category]) }}'"><span>All Time</span></label>
      <label><input type="radio" name="period" value="year" {{ $period==='year'?'checked':'' }} onclick="location='{{ route('rankings',['period'=>'year','category'=>$category]) }}'"><span>This Year</span></label>
      <label><input type="radio" name="period" value="month" {{ $period==='month'?'checked':'' }} onclick="location='{{ route('rankings',['period'=>'month','category'=>$category]) }}'"><span>This Month</span></label>
    </div>
    <div class="seg">
      <label><input type="radio" name="category" value="overall" {{ $category==='overall'?'checked':'' }} onclick="location='{{ route('rankings',['period'=>$period,'category'=>'overall']) }}'"><span>Overall</span></label>
      <label><input type="radio" name="category" value="kilometers" {{ $category==='kilometers'?'checked':'' }} onclick="location='{{ route('rankings',['period'=>$period,'category'=>'kilometers']) }}'"><span>Kilometers</span></label>
      <label><input type="radio" name="category" value="days" {{ $category==='days'?'checked':'' }} onclick="location='{{ route('rankings',['period'=>$period,'category'=>'days']) }}'"><span>Days</span></label>
      <label><input type="radio" name="category" value="followers" {{ $category==='followers'?'checked':'' }} onclick="location='{{ route('rankings',['period'=>$period,'category'=>'followers']) }}'"><span>Followers</span></label>
      <label><input type="radio" name="category" value="likes" {{ $category==='likes'?'checked':'' }} onclick="location='{{ route('rankings',['period'=>$period,'category'=>'likes']) }}'"><span>Likes</span></label>
    </div>
  </div>

  @if($travelers->count())
    <div style="display:flex;flex-direction:column;gap:8px">
      @foreach($travelers as $i => $traveler)
        <a href="{{ route('profile', $traveler) }}" style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:var(--md-surface-container-low);border:1px solid var(--md-outline-variant);border-radius:var(--md-shape-md);text-decoration:none;color:var(--md-on-surface);transition:all .15s" onmouseover="this.style.boxShadow='var(--md-elevation-1)'" onmouseout="this.style.boxShadow='none'">
          <span style="font-family:'Outfit';font-weight:700;font-size:18px;color:var(--md-on-surface-variant);min-width:32px;text-align:center">{{ $i + 1 }}</span>
          <img src="{{ $traveler->avatar() }}" alt="{{ $traveler->name }}" style="width:42px;height:42px;border-radius:50%;object-fit:cover" width="42" height="42">
          <div style="flex:1">
            <div style="font-weight:600;font-size:15px">{{ $traveler->name }}</div>
            <div style="font-size:12px;color:var(--md-on-surface-variant)">{{ $traveler->trips_count ?? 0 }} trips · {{ $traveler->followers_count ?? 0 }} followers</div>
          </div>
          <x-icon name="chevron_right" style="color:var(--md-on-surface-variant)" />
        </a>
      @endforeach
    </div>
  @else
    <div class="block center">
      <x-icon name="leaderboard" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
      <p class="lead">No rankings available yet. Be the first to plan a trip!</p>
      <a class="btn btn-filled" href="{{ route('planner') }}">Plan a trip</a>
    </div>
  @endif
</div>
@endsection
