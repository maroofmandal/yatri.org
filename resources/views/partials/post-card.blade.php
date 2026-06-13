<div class="pcard">
  {{-- 1. IMAGES ON TOP (carousel with arrows + dots) --}}
  @if($post->media->count())
    <div class="pcard-media" style="position:relative">
      @if($post->media->count() === 1)
        @if($post->media->first()->isVideo())
          <video controls class="pcard-video" src="{{ $post->media->first()->url }}" style="display:block;width:100%;max-height:400px;object-fit:cover"></video>
        @else
          <img src="{{ $post->media->first()->thumb_url }}" alt="{{ $post->title }}" class="pcard-image" onclick="openPostViewer({{ $post->id }})" style="cursor:pointer;display:block;width:100%;max-height:500px;object-fit:cover" loading="lazy" srcset="{{ $post->media->first()->thumb_sm_url }} 200w, {{ $post->media->first()->thumb_url }} 400w" sizes="(max-width:480px)100vw,500px">
        @endif
      @else
        <div class="pcard-carousel" id="pcarousel-{{ $post->id }}">
          @foreach($post->media as $m)
            <div class="c-item">
              @if($m->isVideo())
                <video controls style="width:100%;aspect-ratio:3/2;object-fit:cover;display:block" src="{{ $m->url }}"></video>
              @else
                <img src="{{ $m->thumb_url }}" alt="{{ $post->title }}" onclick="openPostViewer({{ $post->id }})" loading="lazy" srcset="{{ $m->thumb_sm_url }} 200w, {{ $m->thumb_url }} 400w" sizes="(max-width:480px)100vw,500px">
              @endif
            </div>
          @endforeach
        </div>
        <button class="carousel-nav prev" onclick="carouselNav('pcarousel-{{ $post->id }}', -1)" aria-label="Previous image">‹</button>
        <button class="carousel-nav next" onclick="carouselNav('pcarousel-{{ $post->id }}', 1)" aria-label="Next image">›</button>
        <div class="carousel-dots">
          @foreach($post->media as $i => $m)
            <button class="carousel-dot {{ $i === 0 ? 'active' : '' }}" onclick="carouselDot('pcarousel-{{ $post->id }}', {{ $i }})" aria-label="Image {{ $i+1 }}"></button>
          @endforeach
        </div>
      @endif
    </div>
  @endif

  {{-- 2. USER INFO --}}
  <div class="pcard-head">
    <a href="{{ route('profile', $post->user) }}" class="pcard-author">
      <img src="{{ $post->user->avatar() }}" alt="{{ $post->user->name }}" class="pcard-avatar" width="40" height="40">
      <div>
        <strong>{{ $post->user->name }}</strong>
        <span class="muted" style="font-size:12px">{{ $post->created_at->diffForHumans() }}</span>
      </div>
    </a>
    @if($post->location)
      <span class="pcard-location"><x-icon name="location_on" :size="14" /> {{ $post->location }}</span>
    @endif
  </div>

  {{-- 3. TITLE --}}
  <a href="{{ route('posts.show', $post) }}" style="color:inherit;text-decoration:none">
    <h3 style="padding:0 18px;margin:0 0 8px;font-size:18px">{{ $post->title }}</h3>
  </a>

  {{-- 4. BODY --}}
  @if($post->body)
    <div class="pcard-body">
      <p>{{ \Illuminate\Support\Str::limit($post->body, 200) }}</p>
    </div>
  @endif

  {{-- 5. TRIP LINK --}}
  @if($post->trip)
    <div class="pcard-trip">
      <a href="{{ route('trip.show', $post->trip) }}">
        <x-icon name="map" :size="16" /> {{ $post->trip->title }} ({{ $post->trip->days }} days)
      </a>
    </div>
  @endif

  {{-- 6. FOOTER --}}
  <div class="pcard-foot">
    <button class="pcard-action like-btn {{ $post->isLikedBy(auth()->user()) ? 'liked' : '' }}"
            data-post-id="{{ $post->id }}"
            onclick="toggleLike({{ $post->id }})">
      <x-icon name="favorite" :size="20" />
      <span class="like-count">{{ $post->likes_count }}</span>
    </button>
    <button class="pcard-action" onclick="toggleComments({{ $post->id }})">
      <x-icon name="chat_bubble" :size="20" />
      <span>{{ $post->comments_count }}</span>
    </button>
    <button class="pcard-action" onclick="sharePost({{ $post->id }}, '{{ route('posts.show', $post) }}', '{{ $post->title }}')">
      <x-icon name="share" :size="20" />
      <span id="share-count-{{ $post->id }}">{{ $post->shares ?? 0 }}</span>
    </button>
    <span class="pcard-action" style="cursor:default;opacity:.6">
      <x-icon name="visibility" :size="20" />
      {{ $post->views ?? 0 }}
    </span>
  </div>

  {{-- 7. COMMENTS --}}
  <div class="pcard-comments" id="comments-{{ $post->id }}" style="display:none">
    <div class="comment-list" id="comment-list-{{ $post->id }}">
      @foreach($post->comments->take(3) as $comment)
        @include('partials.comment-item', ['comment' => $comment])
      @endforeach
    </div>
    @auth
      <form class="comment-form" onsubmit="submitComment(event, {{ $post->id }})">
        <input type="text" placeholder="Add a comment..." required maxlength="1000">
        <button type="submit" class="btn btn-filled btn-sm">Post</button>
      </form>
    @endauth
  </div>
</div>