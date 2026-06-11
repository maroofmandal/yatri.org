<div class="tcard">
  <a href="{{ route('trip.show', $trip) }}" class="tcard-body">
    <div class="tcard-head">
      <span class="tag tag-primary"><x-icon name="payments" :size="14" /> <span class="money" data-amt="{{ (float)$trip->budget_total }}" data-cur="{{ $trip->currency }}">{{ strtoupper($trip->currency) }} {{ number_format($trip->budget_total) }}</span></span>
      <span class="tag"><x-icon name="schedule" :size="14" /> {{ $trip->days }} days</span>
      @if($trip->fit_status === 'fit')<span class="tag tag-success"><x-icon name="check_circle" :size="14" /> on budget</span>@endif
    </div>
    <h3 style="margin:0 0 6px">{{ $trip->title }}</h3>
    <p class="muted" style="font-size:13px;margin:0">{{ $trip->origin }} → {{ collect($trip->destinations)->pluck('name')->take(3)->implode(' · ') }}</p>
  </a>
  <div class="tcard-foot">
    @if($trip->user)
      <a href="{{ route('profile', $trip->user) }}" class="tauthor"><img src="{{ $trip->user->avatar() }}" alt="{{ $trip->user->name }}" width="26" height="26"> {{ $trip->user->name }}</a>
    @else
      <span class="muted" style="font-size:12px;display:flex;align-items:center;gap:4px"><x-icon name="person_off" :size="16" /> Guest planner</span>
    @endif
    <span class="tstats"><x-icon name="favorite" :size="16" /> {{ $trip->likes_count ?? $trip->likes()->count() }} <x-icon name="chat_bubble" :size="16" /> {{ $trip->comments_count ?? $trip->comments()->count() }} <x-icon name="visibility" :size="16" /> {{ $trip->views }} <x-icon name="share" :size="16" /> {{ $trip->shares ?? 0 }}</span>
  </div>
</div>
