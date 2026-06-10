<div class="tcard">
  <a href="{{ route('trip.show', $trip) }}" class="tcard-body">
    <div class="tcard-head">
      <span class="tag tag-primary"><span class="material-symbols-outlined md-14">payments</span> {{ strtoupper($trip->currency) }} {{ number_format($trip->budget_total) }}</span>
      <span class="tag"><span class="material-symbols-outlined md-14">schedule</span> {{ $trip->days }} days</span>
      @if($trip->fit_status === 'fit')<span class="tag tag-success"><span class="material-symbols-outlined md-14">check_circle</span> on budget</span>@endif
    </div>
    <h3 style="margin:0 0 6px">{{ $trip->title }}</h3>
    <p class="muted" style="font-size:13px;margin:0">{{ $trip->origin }} → {{ collect($trip->destinations)->pluck('name')->take(3)->implode(' · ') }}</p>
  </a>
  <div class="tcard-foot">
    @if($trip->user)
      <a href="{{ route('profile', $trip->user) }}" class="tauthor"><img src="{{ $trip->user->avatar() }}" alt="{{ $trip->user->name }}"> {{ $trip->user->name }}</a>
    @else
      <span class="muted" style="font-size:12px;display:flex;align-items:center;gap:4px"><span class="material-symbols-outlined md-16">person_off</span> Guest planner</span>
    @endif
    <span class="tstats"><span class="material-symbols-outlined md-16" style="vertical-align:middle">favorite</span> {{ $trip->likes_count ?? $trip->likes()->count() }} · <span class="material-symbols-outlined md-16" style="vertical-align:middle">chat_bubble</span> {{ $trip->comments_count ?? $trip->comments()->count() }}</span>
  </div>
</div>
