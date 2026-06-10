@extends('layouts.app')
@section('title', 'Privacy Policy — Yatri')
@section('meta_description', 'Yatri privacy policy — how we collect, use, and protect your personal data.')

@section('content')
<div class="wrap" style="padding-top:48px;padding-bottom:100px;max-width:720px">
  <p class="eyebrow">Legal</p>
  <h1 style="margin:0 0 24px"><strong>Privacy Policy</strong></h1>

  <div style="color:var(--md-on-surface-variant);font-size:15px;line-height:1.7;display:flex;flex-direction:column;gap:20px">
    <p><em>Last updated: June 2026</em></p>

    <h2 style="margin:0;color:var(--md-on-surface)"><strong>1. Information We Collect</strong></h2>
    <p>When you use Yatri, we collect:</p>
    <ul style="padding-left:20px">
      <li><strong>Account data</strong> — name, email address, and profile photo if you register.</li>
      <li><strong>Trip data</strong> — destinations, dates, budgets, preferences, and generated itineraries you create or save.</li>
      <li><strong>Usage data</strong> — pages visited, features used, and interactions to improve the service.</li>
      <li><strong>Location data</strong> — destination searches and geocoded locations you explicitly input.</li>
    </ul>

    <h2 style="margin:0;color:var(--md-on-surface)"><strong>2. How We Use Your Data</strong></h2>
    <ul style="padding-left:20px">
      <li>Generate personalized trip itineraries using AI.</li>
      <li>Improve and personalize your experience on the platform.</li>
      <li>Send service-related communications (e.g., password resets).</li>
      <li>Display public trips and rankings based on community activity.</li>
    </ul>

    <h2 style="margin:0;color:var(--md-on-surface)"><strong>3. AI & Data Processing</strong></h2>
    <p>Trips are generated using Google Gemini. Your trip details (destinations, dates, budget, preferences) are sent to Gemini solely to produce your itinerary. We do not use your trip data to train AI models.</p>

    <h2 style="margin:0;color:var(--md-on-surface)"><strong>4. Data Retention</strong></h2>
    <p>We retain your account data until you delete your account. Trip data is retained as long as your account is active. You can delete individual trips at any time.</p>

    <h2 style="margin:0;color:var(--md-on-surface)"><strong>5. Third-Party Services</strong></h2>
    <p>Yatri integrates with Google Maps Platform and Google Search to enrich itineraries with live data. These services operate under their own privacy policies.</p>

    <h2 style="margin:0;color:var(--md-on-surface)"><strong>6. Your Rights</strong></h2>
    <p>You can access, update, or delete your data at any time from your account settings. To request full data deletion, contact us.</p>

    <h2 style="margin:0;color:var(--md-on-surface)"><strong>7. Cookies</strong></h2>
    <p>We use essential cookies for authentication and session management. No tracking or advertising cookies are used.</p>

    <h2 style="margin:0;color:var(--md-on-surface)"><strong>8. Contact</strong></h2>
    <p>Questions about this policy? <a href="{{ route('contact') }}" style="color:var(--md-primary)">Contact us</a>.</p>
  </div>
</div>
@endsection
