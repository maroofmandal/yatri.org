@extends('layouts.app')
@section('title', 'Pricing — Yatri')

@section('content')
<header class="hero" style="padding:60px 0 46px"><div class="wrap center">
  <p class="eyebrow">Pricing</p>
  <h1><strong>Plan free.</strong> Upgrade when you roam more.
    <span class="sub" style="margin-left:auto;margin-right:auto">Every plan is AI-built and budget-fit. Paid tiers unlock unlimited plans, collaboration and creator tools.</span>
  </h1>
</div></header>

<div class="wrap">
  <div class="grid grid-4 mt">
    @foreach($plans as $p)
      <div class="card {{ $p['featured'] ? 'plan-featured' : '' }}" style="display:flex;flex-direction:column">
        @if($p['featured'])<span class="tag" style="background:var(--accent);color:#fff;align-self:flex-start;margin-bottom:6px">Most popular</span>@endif
        <h3>{{ $p['name'] }}</h3>
        <p class="muted" style="font-size:13px">{{ $p['tagline'] }}</p>
        <div style="font-family:Outfit;font-weight:600;font-size:34px;margin:10px 0">
          {{ $p['price'] == 0 ? 'Free' : '$'.$p['price'] }}<span style="font-size:14px;color:var(--muted);font-weight:500">{{ $p['price']==0 ? '' : $p['period'] }}</span>
        </div>
        <ul style="list-style:none;padding:0;margin:0 0 18px;flex:1">
          @foreach($p['features'] as $f)<li style="padding:7px 0;font-size:14px;border-top:1px solid var(--line)">✓ {{ $f }}</li>@endforeach
        </ul>
        <a class="btn {{ $p['featured'] ? 'btn-accent' : 'btn-ghost' }} btn-block" style="margin-top:auto" href="{{ route('register') }}">{{ $p['cta'] }}</a>
      </div>
    @endforeach
  </div>
  <p class="hint center mt2">Billed monthly · cancel anytime · affiliate booking commissions help fund the free tier.</p>
</div>
@endsection
