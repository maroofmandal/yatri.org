@extends('layouts.app')
@section('title', 'About — Yatri')
@section('meta_description', 'Yatri is an AI-powered budget trip planner that builds custom itineraries grounded with live Google Search & Maps data via Gemini.')

@section('content')
<div class="wrap" style="padding-top:48px;padding-bottom:100px;max-width:720px">
  <p class="eyebrow">About</p>
  <h1 style="margin:0 0 24px"><strong>What is Yatri?</strong></h1>

  <div style="color:var(--md-on-surface-variant);font-size:15px;line-height:1.7;display:flex;flex-direction:column;gap:20px">
    <p>
      Yatri is an <strong>AI budget trip planner</strong> — you tell us where you want to go, for how long, and your rough budget, and we build a complete day-by-day itinerary grounded with live data from Google Search and Google Maps.
    </p>
    <p>
      Unlike generic travel blogs or static guides, every Yatri itinerary is <strong>personalized, real-time, and actionable</strong>. We factor in actual flight and hotel prices, local transport costs, entry fees, and restaurant budgets so your plan reflects real-world numbers — not guesses.
    </p>

    <h2 style="margin:32px 0 0;color:var(--md-on-surface)"><strong>How it works</strong></h2>

    <div style="display:flex;flex-direction:column;gap:4px">
      <p><strong style="color:var(--md-on-surface)">1. Tell us your trip details</strong><br>Destination, duration, budget, travel style (backpacker, comfortable, luxury), and what you care about — food, nature, history, nightlife, or all of the above.</p>
      <p><strong style="color:var(--md-on-surface)">2. AI builds your itinerary</strong><br>Powered by Google Gemini, we research attractions, restaurants, hotels, transport options, and hidden gems — then assemble a logical day-by-day plan with timing and cost estimates.</p>
      <p><strong style="color:var(--md-on-surface)">3. Refine with live data</strong><br>Each itinerary is enriched with Google Search and Maps data. You can chat with the AI to tweak, swap activities, or ask for alternatives.</p>
      <p><strong style="color:var(--md-on-surface)">4. Share & explore</strong><br>Publish your trip, follow other travelers, browse rankings of the most popular destinations, and discover trips shared by the community.</p>
    </div>

    <h2 style="margin:32px 0 0;color:var(--md-on-surface)"><strong>Why Yatri?</strong></h2>
    <p>
      <strong>Yatri</strong> (यात्री) means <em>traveler</em> in Sanskrit and Hindi. We built this because planning a trip is genuinely hard — you open 47 browser tabs, cross-reference blog posts from 2019, guess at prices, and hope it all works out. Yatri does that work in minutes.
    </p>
    <p>
      Every itinerary is <strong>free to generate</strong>. Premium plans unlock offline access, unlimited saves, group collaboration, and priority AI processing.
    </p>

    <h2 style="margin:32px 0 0;color:var(--md-on-surface)"><strong>Built with</strong></h2>
    <p>Google Gemini · Google Maps Platform · Google Search · Laravel · Livewire</p>

    <p style="margin-top:32px;padding-top:24px;border-top:1px solid var(--md-outline-variant)">
      <x-icon name="favorite" :size="16" style="color:var(--md-error)" /> Made for travelers, by travelers.
    </p>
  </div>
</div>
@endsection
