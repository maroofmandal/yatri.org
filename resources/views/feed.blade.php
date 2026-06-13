@extends('layouts.app')
@section('title', \App\Models\Setting::get('site_name','Yatri').' — discover trips & plan with AI')
@section('meta_description', 'Discover real, costed trips from travelers. Plan your perfect budget trip with AI — hotels, transport, and activities that fit your budget.')

@section('hero')
<header class="hero"><div class="wrap">
  <p class="eyebrow">Travel social network + AI budget planner</p>
  <h1><strong>Where next?</strong>
    <span class="sub">Discover real, costed trips from travelers — then plan your own in seconds, fit to your budget by AI.</span>
  </h1>
  <div style="margin-top:28px">
    <a class="btn btn-filled btn-lg" href="{{ route('planner') }}">
      <x-icon name="route" :size="20" /> Plan a trip
    </a>
  </div>
</div></header>
@endsection

@section('content')
  {{-- Posts --}}
  <div style="display:flex;justify-content:space-between;align-items:center;margin:30px 0 14px;flex-wrap:wrap;gap:10px">
    <h2 style="margin:0">Latest posts</h2>
    <a class="btn btn-text btn-sm" href="{{ route('posts.index') }}">
      View all posts <x-icon name="arrow_forward" :size="18" />
    </a>
  </div>

  @if($posts->count())
    <div class="posts-feed">
      @foreach($posts as $post)
        @include('partials.post-card')
      @endforeach
    </div>
  @else
    <div class="block center">
      <x-icon name="article" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
      <p class="lead">No posts yet. Be the first to share your travel story!</p>
      @auth
        <a class="btn btn-filled" href="{{ route('posts.create') }}"><x-icon name="add" :size="18" /> Create Post</a>
      @else
        <a class="btn btn-filled" href="{{ route('register') }}"><x-icon name="person_add" :size="18" /> Sign up to post</a>
      @endauth
    </div>
  @endif

  {{-- Trips --}}
  <div style="display:flex;justify-content:space-between;align-items:center;margin:30px 0 14px;flex-wrap:wrap;gap:10px">
    <h2 style="margin:0">Trending trips</h2>
    <div style="display:flex;align-items:center;gap:10px">
      @auth
        <div class="seg">
          <label><input type="radio" name="feedfilter" {{ request('filter')!=='following'?'checked':'' }} onclick="location='{{ route('home') }}'"><span>For you</span></label>
          <label><input type="radio" name="feedfilter" {{ request('filter')==='following'?'checked':'' }} onclick="location='{{ route('home', ['filter'=>'following']) }}'"><span>Following</span></label>
        </div>
      @endauth
    </div>
  </div>

  @if($trips->count())
    <div>
      @foreach($trips as $trip)
        @include('partials.trip-card')
      @endforeach
    </div>
    <div style="text-align:center;margin-top:16px">
      <a class="btn btn-text" href="{{ route('trips.explore') }}">
        View all trips <x-icon name="arrow_forward" :size="18" />
      </a>
    </div>
  @else
    <div class="block center">
      <x-icon name="explore" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
      <p class="lead">No public trips yet — be the first to share one.</p>
      <a class="btn btn-filled" href="{{ route('planner') }}">Plan a trip</a>
    </div>
  @endif

  {{-- Popular destinations --}}
  @if($destinations->count())
  <div class="block mt2">
    <h2 style="margin:0 0 12px">Popular destinations</h2>
    <div class="chips">
      @foreach($destinations as $d)
        <span class="chip"><x-icon name="location_on" :size="18" /> {{ $d->name }}</span>
      @endforeach
    </div>
  </div>
  @endif
@endsection
