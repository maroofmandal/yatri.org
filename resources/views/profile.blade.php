@extends('layouts.app')
@section('title', $user->name.' — Yatri')

@section('content')
<header class="hero" style="padding:50px 0 38px"><div class="wrap" style="display:flex;gap:22px;align-items:center;flex-wrap:wrap">
  <img src="{{ $user->avatar() }}" alt="" style="width:88px;height:88px;border-radius:50%;border:3px solid rgba(255,255,255,.3)">
  <div>
    <h1 style="font-size:clamp(26px,4vw,40px)"><strong>{{ $user->name }}</strong></h1>
    @if($user->bio)<p style="color:#d6d3d1;margin-top:6px;max-width:520px">{{ $user->bio }}</p>@endif
    <div style="display:flex;gap:18px;margin-top:12px;color:#d6d3d1;font-size:14px;flex-wrap:wrap">
      <span><b style="color:#fff">{{ $stats['trips'] }}</b> trips</span>
      <span><b style="color:#fff">{{ $stats['followers'] }}</b> followers</span>
      <span><b style="color:#fff">{{ $stats['following'] }}</b> following</span>
      <span><b style="color:#fff">{{ $stats['countries'] }}</b> places</span>
    </div>
  </div>
  <div style="margin-left:auto">
    @auth
      @if(auth()->id() !== $user->id)
        @if(auth()->user()->isFollowing($user))
          <form method="POST" action="{{ route('profile.unfollow',$user) }}">@csrf @method('DELETE')<button class="btn btn-ghost">Following ✓</button></form>
        @else
          <form method="POST" action="{{ route('profile.follow',$user) }}">@csrf<button class="btn btn-accent">+ Follow</button></form>
        @endif
      @else
        <a class="btn btn-ghost" href="{{ route('dashboard') }}">Edit / my trips</a>
      @endif
    @else
      <a class="btn btn-accent" href="{{ route('register') }}">Follow on Yatri</a>
    @endauth
  </div>
</div></header>

<div class="wrap">
  <h2 class="mt">Trips</h2>
  @if($trips->count())
    <div class="grid grid-3 mt">
      @foreach($trips as $trip)@include('partials.trip-card')@endforeach
    </div>
  @else
    <div class="block center"><p class="lead">No public trips yet.</p></div>
  @endif
</div>
@endsection
