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

  {{-- Feed --}}
  <div style="display:flex;justify-content:space-between;align-items:center;margin:30px 0 14px;flex-wrap:wrap;gap:10px">
    <h2 style="margin:0">Trending trips</h2>
    @auth
      <div class="seg">
        <label><input type="radio" name="feedfilter" {{ request('filter')!=='following'?'checked':'' }} onclick="location='{{ route('home') }}'"><span>For you</span></label>
        <label><input type="radio" name="feedfilter" {{ request('filter')==='following'?'checked':'' }} onclick="location='{{ route('home', ['filter'=>'following']) }}'"><span>Following</span></label>
      </div>
    @endauth
  </div>

  @if($trips->count())
    <div class="grid grid-3">
      @foreach($trips as $trip)
        @include('partials.trip-card')
      @endforeach
    </div>
    @if($trips->hasPages())
    <div class="pager mt2" style="display:flex;gap:8px;align-items:center;justify-content:center">
      @if($trips->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5"><span class="material-symbols-outlined md-18">chevron_left</span> Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $trips->previousPageUrl() }}"><span class="material-symbols-outlined md-18">chevron_left</span> Prev</a>@endif
      <span class="muted" style="font-size:13px">Page {{ $trips->currentPage() }} / {{ $trips->lastPage() }}</span>
      @if($trips->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $trips->nextPageUrl() }}">Next <span class="material-symbols-outlined md-18">chevron_right</span></a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next <span class="material-symbols-outlined md-18">chevron_right</span></span>@endif
    </div>
    @endif
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
