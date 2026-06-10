@extends('layouts.app')
@section('title', \App\Models\Setting::get('site_name','Yatri').' — discover trips & plan with AI')

@section('content')
<header class="hero"><div class="wrap">
  <p class="eyebrow">Travel social network + AI budget planner</p>
  <h1><strong>Where next?</strong>
    <span class="sub">Discover real, costed trips from travelers — then plan your own in seconds, fit to your budget by AI.</span>
  </h1>
  <div style="margin-top:28px">
    <a class="btn btn-filled btn-lg" href="{{ route('planner') }}">
      <span class="material-symbols-outlined md-20">route</span> Plan a trip
    </a>
  </div>
</div></header>

<div class="wrap">

  {{-- Posts --}}
  <div style="display:flex;justify-content:space-between;align-items:center;margin:30px 0 14px;flex-wrap:wrap;gap:10px">
    <h2 style="margin:0">Latest posts</h2>
    <a class="btn btn-text btn-sm" href="{{ route('posts.index') }}">
      View all posts <span class="material-symbols-outlined md-18">arrow_forward</span>
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
      <span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">article</span>
      <p class="lead">No posts yet. Be the first to share your travel story!</p>
      @auth
        <a class="btn btn-filled" href="{{ route('posts.create') }}"><span class="material-symbols-outlined md-18">add</span> Create Post</a>
      @else
        <a class="btn btn-filled" href="{{ route('register') }}"><span class="material-symbols-outlined md-18">person_add</span> Sign up to post</a>
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
    <div class="grid grid-3">
      @foreach($trips as $trip)
        @include('partials.trip-card')
      @endforeach
    </div>
    <div style="text-align:center;margin-top:16px">
      <a class="btn btn-text" href="{{ route('trips.explore') }}">
        View all trips <span class="material-symbols-outlined md-18">arrow_forward</span>
      </a>
    </div>
  @else
    <div class="block center">
      <span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">explore</span>
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
        <span class="chip"><span class="material-symbols-outlined md-18">location_on</span> {{ $d->name }}</span>
      @endforeach
    </div>
  </div>
  @endif
</div>

@endsection
