@php
  $carouselImages = [];
  if ($trip->image) {
    $carouselImages[] = \Illuminate\Support\Facades\Storage::url($trip->image);
  }
  $postImages = $trip->relationLoaded('posts')
    ? $trip->posts->flatMap->media->where('type', 'photo')->take(4)
    : collect();
  foreach ($postImages as $m) {
    $carouselImages[] = $m->url;
  }
  if ($trip->relationLoaded('media')) {
    $tripMedia = $trip->media->where('type', 'photo')->take(5 - count($carouselImages));
    foreach ($tripMedia as $m) {
      $carouselImages[] = $m->url;
    }
  }
  $carouselImages = array_values(array_unique(array_slice($carouselImages, 0, 5)));
@endphp

<div class="tcard">
  {{-- IMAGE CAROUSEL --}}
  <div class="tcard-carousel" id="tcarousel-{{ $trip->id }}" style="position:relative">
    @if(count($carouselImages) > 0)
      @foreach($carouselImages as $i => $img)
        <div class="c-item {{ $i === 0 ? 'active' : '' }}" style="background-image:url('{{ $img }}')"></div>
      @endforeach
      @if(count($carouselImages) > 1)
        <button class="carousel-nav prev" onclick="carouselTrip('tcarousel-{{ $trip->id }}', -1)" aria-label="Previous image">‹</button>
        <button class="carousel-nav next" onclick="carouselTrip('tcarousel-{{ $trip->id }}', 1)" aria-label="Next image">›</button>
        <div class="carousel-dots">
          @foreach($carouselImages as $i => $img)
            <button class="carousel-dot {{ $i === 0 ? 'active' : '' }}" onclick="carouselTripDot('tcarousel-{{ $trip->id }}', {{ $i }})" aria-label="Image {{ $i+1 }}"></button>
          @endforeach
        </div>
      @endif
    @else
      <div class="c-item active" style="background:{{ $trip->fallbackGradient() }}"></div>
    @endif
  </div>

  {{-- BODY --}}
  <a href="{{ route('trip.show', $trip) }}" class="tcard-body">
    <div class="tcard-head">
      <span class="tag tag-primary"><x-icon name="payments" :size="14" /> <span class="money" data-amt="{{ (float)$trip->budget_total }}" data-cur="{{ $trip->currency }}">{{ strtoupper($trip->currency) }} {{ number_format($trip->budget_total) }}</span></span>
      <span class="tag"><x-icon name="schedule" :size="14" /> {{ $trip->days }} days</span>
      @if($trip->fit_status === 'fit')<span class="tag tag-success"><x-icon name="check_circle" :size="14" /> on budget</span>@endif
    </div>
    <h3 style="margin:0 0 6px">{{ $trip->title }}</h3>
    <p class="muted" style="font-size:13px;margin:0">{{ $trip->origin }} → {{ collect($trip->destinations)->pluck('name')->take(3)->implode(' · ') }}</p>
  </a>

  {{-- FOOTER: equal-width stats, no user --}}
  <div class="tcard-foot">
    <span class="tstats">
      <span><x-icon name="favorite" :size="16" /> {{ $trip->likes_count ?? $trip->likes()->count() }}</span>
      <span><x-icon name="chat_bubble" :size="16" /> {{ $trip->comments_count ?? $trip->comments()->count() }}</span>
      <span><x-icon name="visibility" :size="16" /> {{ $trip->views }}</span>
      <span><x-icon name="share" :size="16" /> {{ $trip->shares ?? 0 }}</span>
    </span>
  </div>
</div>