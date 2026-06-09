@extends('layouts.app')
@section('title', $user->name.' — Yatri')

@section('content')
<header class="hero" style="padding:50px 0 38px"><div class="wrap" style="display:flex;gap:22px;align-items:center;flex-wrap:wrap">
  <img src="{{ $user->avatar() }}" alt="" style="width:88px;height:88px;border-radius:50%;border:3px solid rgba(255,255,255,.3)">
  <div>
    <h1 style="font-size:clamp(26px,4vw,40px)"><strong>{{ $user->name }}</strong></h1>
    @if($user->bio)<p style="color:#d6d3d1;margin-top:6px;max-width:520px">{{ $user->bio }}</p>@endif
    <div style="display:flex;gap:18px;margin-top:12px;color:#d6d3d1;font-size:14px;flex-wrap:wrap">
      <span><b style="color:#fff">{{ $stats['trips'] }}</b> trips</span>
      <span><b style="color:#fff">{{ $stats['followers'] }}</b> followers</span>
      <span><b style="color:#fff">{{ $stats['following'] }}</b> following</span>
      <span><b style="color:#fff">{{ $stats['countries'] }}</b> places</span>
      <span><b style="color:#fff">{{ $stats['total_likes'] }}</b> likes</span>
      <span><b style="color:#fff">{{ $stats['total_days'] }}</b> days traveled</span>
      <span><b style="color:#fff">{{ $stats['total_media'] }}</b> media</span>
    </div>
  </div>
  <div style="margin-left:auto">
    @auth
      @if(auth()->id() !== $user->id)
        @if(auth()->user()->isFollowing($user))
          <form method="POST" action="{{ route('profile.unfollow',$user) }}">@csrf @method('DELETE')<button class="btn btn-ghost">Following ✓</button></form>
        @else
          <form method="POST" action="{{ route('profile.follow',$user) }}">@csrf<button class="btn btn-accent">+ Follow</button></form>
        @endif
      @else
        <a class="btn btn-ghost" href="{{ route('dashboard') }}">Edit / my trips</a>
      @endif
    @else
      <a class="btn btn-accent" href="{{ route('register') }}">Follow on Yatri</a>
    @endauth
  </div>
</div></header>

<div class="wrap">
  <div class="profile-tabs">
    <button class="profile-tab active" onclick="showTab('trips')">🗺️ Trips <span class="tab-count">{{ $stats['trips'] }}</span></button>
    <button class="profile-tab" onclick="showTab('posts')">📝 Posts <span class="tab-count">{{ $posts->count() }}</span></button>
    <button class="profile-tab" onclick="showTab('media')">📷 Media <span class="tab-count">{{ $stats['total_media'] }}</span></button>
    <button class="profile-tab" onclick="showTab('reviews')">⭐ Reviews <span class="tab-count">{{ $reviews->count() }}</span></button>
  </div>

  <div class="tab-content" id="tab-trips">
    @if($trips->count())
      <div class="grid grid-3 mt">
        @foreach($trips as $trip)@include('partials.trip-card')@endforeach
      </div>
    @else
      <div class="block center"><p class="lead">No public trips yet.</p></div>
    @endif
  </div>

  <div class="tab-content" id="tab-posts" style="display:none">
    @if($posts->count())
      <div class="posts-feed mt">
        @foreach($posts as $post)
          @include('partials.post-card')
        @endforeach
      </div>
    @else
      <div class="block center"><p class="lead">No posts yet.</p></div>
    @endif
  </div>

  <div class="tab-content" id="tab-media" style="display:none">
    @if($media->count())
      <div class="media-grid mt">
        @foreach($media as $m)
          <a href="{{ $m->url }}" class="media-item" target="_blank">
            @if($m->isVideo())
              <video src="{{ $m->url }}" muted></video>
              <span class="media-play">▶</span>
            @else
              <img src="{{ $m->url }}" alt="">
            @endif
          </a>
        @endforeach
      </div>
    @else
      <div class="block center"><p class="lead">No media uploaded yet.</p></div>
    @endif
  </div>

  <div class="tab-content" id="tab-reviews" style="display:none">
    @if($reviews->count())
      <div class="reviews-list mt">
        @foreach($reviews as $review)
          <div class="review-card block">
            <div class="review-header">
              <div class="place-stars">
                @for($i = 1; $i <= 5; $i++)
                  {{ $i <= $review->rating ? '★' : '☆' }}
                @endfor
                <strong>{{ $review->rating }}/5</strong>
              </div>
              <span class="muted" style="font-size:12px">{{ $review->created_at->diffForHumans() }}</span>
            </div>
            @if($review->title)
              <h3 style="margin:10px 0 6px">{{ $review->title }}</h3>
            @endif
            <p>{{ $review->body }}</p>
            <div class="review-meta">
              <span class="muted">Review of {{ class_basename($review->reviewable_type) }}</span>
              @if($review->reviewable)
                <span class="tag">{{ $review->reviewable->name ?? 'Unknown' }}</span>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="block center"><p class="lead">No reviews yet.</p></div>
    @endif
  </div>
</div>

<style>
.profile-tabs { display:flex;gap:8px;margin:24px 0;border-bottom:2px solid var(--line);padding-bottom:0 }
.profile-tab { padding:12px 20px;border:none;background:none;cursor:pointer;font-family:'Outfit';font-weight:600;font-size:14px;color:var(--muted);border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .2s }
.profile-tab:hover { color:var(--ink) }
.profile-tab.active { color:var(--accent);border-bottom-color:var(--accent) }
.tab-count { background:var(--bg);padding:2px 8px;border-radius:20px;font-size:12px;margin-left:6px }

.media-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px }
.media-item { position:relative;aspect-ratio:1;border-radius:var(--r-sm);overflow:hidden }
.media-item img,.media-item video { width:100%;height:100%;object-fit:cover }
.media-play { position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;background:rgba(0,0,0,.3) }

.review-card { padding:18px }
.review-header { display:flex;justify-content:space-between;align-items:center }
.review-meta { margin-top:10px;display:flex;gap:8px;align-items:center }
</style>

@push('scripts')
<script>
function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.profile-tab').forEach(el => el.classList.remove('active'));
  
  document.getElementById('tab-' + tab).style.display = 'block';
  event.target.classList.add('active');
}
</script>
@endpush
@endsection