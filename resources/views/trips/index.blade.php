@extends('layouts.app')
@section('title', 'Explore trips — Yatri')
@section('meta_description', 'Browse AI-planned trips from travelers worldwide. See budgets, itineraries, and real costs for destinations across the globe.')

@section('content')
<div class="wrap" style="padding-top:36px;padding-bottom:100px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h2 style="margin:0">Explore trips</h2>
    <a class="btn btn-filled btn-sm" href="{{ route('planner') }}">
      <x-icon name="add" :size="18" /> Plan a trip
    </a>
  </div>

  @if($trips->count())
    <div class="grid grid-3">
      @foreach($trips as $trip)
        @include('partials.trip-card')
      @endforeach
    </div>
    @if($trips->hasPages())
      <div class="pager mt2" style="display:flex;gap:8px;align-items:center;justify-content:center">
        @if($trips->onFirstPage())<span class="btn btn-ghost btn-sm" style="opacity:.5"><x-icon name="chevron_left" :size="18" /> Prev</span>@else<a class="btn btn-ghost btn-sm" href="{{ $trips->previousPageUrl() }}"><x-icon name="chevron_left" :size="18" /> Prev</a>@endif
        <span class="muted" style="font-size:13px">Page {{ $trips->currentPage() }} / {{ $trips->lastPage() }}</span>
        @if($trips->hasMorePages())<a class="btn btn-ghost btn-sm" href="{{ $trips->nextPageUrl() }}">Next <x-icon name="chevron_right" :size="18" /></a>@else<span class="btn btn-ghost btn-sm" style="opacity:.5">Next <x-icon name="chevron_right" :size="18" /></span>@endif
      </div>
    @endif
  @else
    <div class="block center">
      <x-icon name="explore" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
      <p class="lead">No public trips yet — be the first to share one.</p>
      <a class="btn btn-filled" href="{{ route('planner') }}">Plan a trip</a>
    </div>
  @endif
</div>
@endsection
