@extends('layouts.app')
@section('title', 'My trips')

@section('content')
<div class="wrap" style="padding-top:36px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div>
      <h2>My trips</h2>
      <p class="lead">Everything you've planned with Yatri.</p>
    </div>
    <a class="btn btn-accent" href="{{ route('home') }}">+ New trip</a>
  </div>

  @if($trips->isEmpty())
    <div class="block center">
      <p class="lead">No trips yet.</p>
      <a class="btn btn-primary" href="{{ route('home') }}">Plan your first trip</a>
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
@endsection
