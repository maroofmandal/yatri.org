<div class="tcard">
  <a href="{{ route('trip.show', $trip) }}" class="tcard-body" style="color:inherit">
    <div class="tcard-head">
      <span class="tag">{{ strtoupper($trip->currency) }} {{ number_format($trip->budget_total) }}</span>
      <span class="tag" style="background:#f1f5f9;color:#334155">{{ $trip->days }} days</span>
      @if($trip->fit_status === 'fit')<span class="tag" style="background:#f0fdf4;color:#166534">on budget</span>@endif
    </div>
    <h3>{{ $trip->title }}</h3>
    <p class="muted" style="font-size:13px;margin:4px 0 10px">{{ $trip->origin }} → {{ collect($trip->destinations)->pluck('name')->take(3)->implode(' · ') }}</p>
  </a>
  <div class="tcard-foot">
    @if($trip->user)
      <a href="{{ route('profile', $trip->user) }}" class="tauthor"><img src="{{ $trip->user->avatar() }}" alt=""> {{ $trip->user->name }}</a>
    @else
      <span class="muted" style="font-size:12.5px">Guest planner</span>
    @endif
    <span class="tstats">♥ {{ $trip->likes_count ?? $trip->likes()->count() }} · 💬 {{ $trip->comments_count ?? $trip->comments()->count() }}</span>
  </div>
</div>
