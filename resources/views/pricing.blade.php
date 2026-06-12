@extends('layouts.app')
@section('title', 'Pricing — Yatri')
@section('meta_description', 'Choose the right Yatri plan — from free Explorer to Legend for creators. AI trip planning, offline itineraries, and group collaboration.')

@section('content')
<header class="hero" style="padding:48px 0 40px"><div class="wrap" style="text-align:center">
  <p class="eyebrow">Simple pricing</p>
  <h1 style="margin:0"><strong>Choose your plan</strong></h1>
  <p class="sub" style="margin:12px auto 0;text-align:center">Start free. Upgrade when you need more power.</p>
</div></header>

<div class="wrap" style="padding:40px 24px 100px">
  <div class="grid grid-4">
    @foreach($plans as $plan)
      <div class="pricing-card @if($plan['featured']) featured @endif" style="display:flex;flex-direction:column">
        <h3 style="margin:0 0 4px">{{ $plan['name'] }}</h3>
        <p style="color:var(--md-on-surface-variant);font-size:13px;margin:0 0 16px">{{ $plan['tagline'] }}</p>
        <div style="margin-bottom:20px">
          @if($plan['price'] === 0)
            <span style="font-family:'Poppins';font-size:36px;font-weight:600">Free</span>
          @else
            <span style="font-family:'Poppins';font-size:36px;font-weight:600">${{ $plan['price'] }}</span>
            <span style="color:var(--md-on-surface-variant);font-size:14px">{{ $plan['period'] }}</span>
          @endif
        </div>
        <ul class="pricing-features">
          @foreach($plan['features'] as $feature)
            <li><x-icon name="check_circle" :size="18" /> {{ $feature }}</li>
          @endforeach
        </ul>
        <div style="margin-top:auto;padding-top:16px">
          <a class="btn @if($plan['featured']) btn-filled @else btn-outlined @endif btn-block" href="{{ route('register') }}">{{ $plan['cta'] }}</a>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
