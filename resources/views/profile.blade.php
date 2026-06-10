@extends('layouts.app')
@section('title', $user->name.' — Yatri')

@section('content')
<header class="hero" style="padding:48px 0 36px"><div class="wrap" style="display:flex;gap:22px;align-items:center;flex-wrap:wrap">
  <img src="{{ $user->avatar() }}" alt="{{ $user->name }}" style="width:88px;height:88px;border-radius:50%;border:3px solid rgba(255,255,255,.3);object-fit:cover">
  <div style="flex:1">
    <h1 style="font-size:clamp(24px,4vw,36px);margin:0"><strong>{{ $user->name }}</strong></h1>
    @if($user->bio)<p style="color:rgba(255,255,255,.85);margin-top:6px;max-width:520px">{{ $user->bio }}</p>@endif
    <div style="display:flex;gap:18px;margin-top:12px;color:rgba(255,255,255,.8);font-size:14px;flex-wrap:wrap">
      <span><b style="color:#fff">{{ $stats['trips'] }}</b> trips</span>
      <span><b style="color:#fff">{{ $stats['followers'] }}</b> followers</span>
      <span><b style="color:#fff">{{ $stats['following'] }}</b> following</span>
      <span><b style="color:#fff">{{ $stats['countries'] }}</b> places</span>
      <span><b style="color:#fff">{{ $stats['total_likes'] }}</b> likes</span>
      <span><b style="color:#fff">{{ $stats['total_days'] }}</b> days</span>
    </div>
  </div>
  <div style="margin-left:auto">
    @auth
      @if(auth()->id() !== $user->id)
        @if(auth()->user()->isFollowing($user))
          <form method="POST" action="{{ route('profile.unfollow',$user) }}">@csrf @method('DELETE')<button class="btn btn-ghost"><span class="material-symbols-outlined md-18">check</span> Following</button></form>
        @else
          <form method="POST" action="{{ route('profile.follow',$user) }}">@csrf<button class="btn btn-filled"><span class="material-symbols-outlined md-18">person_add</span> Follow</button></form>
        @endif
      @else
        <a class="btn btn-ghost" href="{{ route('settings') }}"><span class="material-symbols-outlined md-18">settings</span> Settings</a>
      @endif
    @else
      <a class="btn btn-filled" href="{{ route('register') }}"><span class="material-symbols-outlined md-18">person_add</span> Follow on Yatri</a>
    @endauth
  </div>
</div></header>

<div class="wrap">
  <div class="profile-tabs">
    <button class="profile-tab active" onclick="showTab('trips')">
      <span class="material-symbols-outlined">map</span> Trips <span class="tab-count">{{ $stats['trips'] }}</span>
    </button>
    <button class="profile-tab" onclick="showTab('posts')">
      <span class="material-symbols-outlined">article</span> Posts <span class="tab-count">{{ $posts->count() }}</span>
    </button>
    <button class="profile-tab" onclick="showTab('media')">
      <span class="material-symbols-outlined">photo_library</span> Media <span class="tab-count">{{ $stats['total_media'] }}</span>
    </button>
    <button class="profile-tab" onclick="showTab('reviews')">
      <span class="material-symbols-outlined">star</span> Reviews <span class="tab-count">{{ $reviews->count() }}</span>
    </button>
  </div>

  <div class="tab-content" id="tab-trips">
    @if($trips->count())
      <div class="grid grid-3 mt">
        @foreach($trips as $trip)@include('partials.trip-card')@endforeach
      </div>
    @else
      <div class="block center"><span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">map</span><p class="lead">No public trips yet.</p></div>
    @endif
  </div>

  <div class="tab-content" id="tab-posts" style="display:none">
    @if($posts->count())
      <div class="posts-feed mt">
        @foreach($posts as $post)@include('partials.post-card')@endforeach
      </div>
    @else
      <div class="block center"><span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">article</span><p class="lead">No posts yet.</p></div>
    @endif
  </div>

  <div class="tab-content" id="tab-media" style="display:none">
    @if($media->count())
      <div class="media-grid mt">
        @foreach($media as $m)
          <div class="media-item" style="cursor:pointer" onclick="openPostViewer({{ $m->mediable_id }})">
            @if($m->isVideo())
              <video src="{{ $m->url }}" muted></video>
              <span class="media-play"><span class="material-symbols-outlined md-32">play_arrow</span></span>
            @else
              <img src="{{ $m->url }}" alt="Travel photo">
            @endif
          </div>
        @endforeach
      </div>
    @else
      <div class="block center"><span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">photo_library</span><p class="lead">No media uploaded yet.</p></div>
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
                  <span class="material-symbols-outlined md-18" style="color:{{ $i <= $review->rating ? 'var(--md-primary)' : 'var(--md-outline-variant)' }}">star</span>
                @endfor
                <strong style="margin-left:6px">{{ $review->rating }}/5</strong>
              </div>
              <span class="muted" style="font-size:12px">{{ $review->created_at->diffForHumans() }}</span>
            </div>
            @if($review->title)<h3 style="margin:10px 0 6px">{{ $review->title }}</h3>@endif
            <p>{{ $review->body }}</p>
            <div class="review-meta">
              <span class="muted">Review of {{ class_basename($review->reviewable_type) }}</span>
              @if($review->reviewable)<span class="tag">{{ $review->reviewable->name ?? 'Unknown' }}</span>@endif
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="block center"><span class="material-symbols-outlined md-36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px">star</span><p class="lead">No reviews yet.</p></div>
    @endif
  </div>
</div>

@push('scripts')
<script>
function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.profile-tab').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + tab).style.display = 'block';
  event.target.closest('.profile-tab').classList.add('active');
}
</script>
@endpush
@endsection
