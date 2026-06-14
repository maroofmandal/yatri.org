@extends('layouts.app')
@section('title', \App\Models\Setting::get('site_name','Yatri').' — discover trips & plan with AI')
@section('meta_description', 'Discover real, costed trips from travelers. Plan your perfect budget trip with AI — hotels, transport, and activities that fit your budget.')

@section('hero')
@guest
<header class="hero"><div class="wrap">
  <p class="eyebrow">Travel social network + AI budget planner</p>
  <h1><strong>Where next?</strong>
    <span class="sub">Discover real, costed trips from travelers — then plan your own in seconds, fit to your budget by AI.</span>
  </h1>
  <div style="margin-top:28px">
    <a class="btn btn-filled btn-lg" href="{{ route('planner') }}">
      <x-icon name="route" :size="20" /> Plan a trip
    </a>
  </div>
</div></header>
@endguest
@endsection

@section('right_sidebar')
  @if($destinations->count())
  <div class="sidebar-widget">
    <h3>Popular destinations</h3>
    <div class="chips">
      @foreach($destinations as $d)
        <a class="chip-link" href="{{ route('trips.explore', ['destination' => $d->name]) }}">
          <x-icon name="location_on" :size="16" /> {{ $d->name }}
        </a>
      @endforeach
    </div>
  </div>
  @endif

  @if($latestTrips->count())
  <div class="sidebar-widget">
    <h3>Latest trips</h3>
    <div style="display:flex;flex-direction:column;gap:14px">
      @foreach($latestTrips as $trip)
        @include('partials.trip-card')
      @endforeach
    </div>
    <div style="margin-top:14px;text-align:center">
      <a class="btn btn-outlined btn-sm" href="{{ route('trips.explore') }}" style="width:100%;justify-content:center">
        <x-icon name="explore" :size="18" /> Explore all trips
      </a>
    </div>
  </div>
  @endif
@endsection

@section('content')
  {{-- Posts header --}}
  <div style="display:flex;justify-content:space-between;align-items:center;margin:30px 0 14px;flex-wrap:wrap;gap:10px">
    <h2 style="margin:0">Latest posts</h2>
  </div>

  @if($posts->count())
    <div class="posts-feed" id="postsFeed">
      @foreach($posts as $post)
        @include('partials.post-card')
      @endforeach
    </div>

    {{-- Infinite scroll trigger --}}
    @if($posts->hasMorePages())
      <div id="loadMoreTrigger" data-next-page="2" data-last-page="{{ $posts->lastPage() }}" style="text-align:center;padding:24px 0">
        <div id="loadMoreSpinner" style="display:none">
          <span style="display:inline-block;width:18px;height:18px;border:2.5px solid var(--md-outline-variant);border-radius:50%;border-top-color:var(--md-primary);animation:spin .8s linear infinite"></span>
        </div>
        <button id="loadMoreBtn" class="btn btn-ghost btn-sm" onclick="loadMorePosts()">Load more posts</button>
      </div>
    @endif
  @else
    <div class="block center">
      <x-icon name="article" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
      <p class="lead">No posts yet. Be the first to share your travel story!</p>
      @auth
        <a class="btn btn-filled" href="{{ route('posts.create') }}"><x-icon name="add" :size="18" /> Create Post</a>
      @else
        <a class="btn btn-filled" href="{{ route('register') }}"><x-icon name="person_add" :size="18" /> Sign up to post</a>
      @endauth
    </div>
  @endif
@endsection

@push('scripts')
<script>
(function() {
  const trigger = document.getElementById('loadMoreTrigger');
  if (!trigger) return;

  let loading = false;
  let currentPage = parseInt(trigger.dataset.nextPage);
  const lastPage = parseInt(trigger.dataset.lastPage);
  const feed = document.getElementById('postsFeed');
  const btn = document.getElementById('loadMoreBtn');
  const spinner = document.getElementById('loadMoreSpinner');

  function loadMore() {
    if (loading || currentPage > lastPage) return;
    loading = true;
    if (btn) btn.style.display = 'none';
    if (spinner) spinner.style.display = 'block';

    fetch(window.location.pathname + '?page=' + currentPage, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(html => {
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const newFeed = doc.getElementById('postsFeed');
      if (newFeed && newFeed.children.length > 0) {
        Array.from(newFeed.children).forEach(el => feed.appendChild(el));
        currentPage++;
        trigger.dataset.nextPage = currentPage;
        if (currentPage > lastPage) {
          trigger.remove();
        } else {
          if (btn) btn.style.display = '';
          if (spinner) spinner.style.display = 'none';
        }
      } else {
        trigger.remove();
      }
      loading = false;
    })
    .catch(() => {
      loading = false;
      if (btn) btn.style.display = '';
      if (spinner) spinner.style.display = 'none';
    });
  }

  // Expose for manual click
  window.loadMorePosts = loadMore;

  // IntersectionObserver for automatic loading
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting) loadMore();
    }, { rootMargin: '300px' });
    observer.observe(trigger);
  }
})();
</script>
@endpush
