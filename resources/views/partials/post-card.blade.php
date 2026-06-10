<div class="pcard">
  <div class="pcard-head">
    <a href="{{ route('profile', $post->user) }}" class="pcard-author">
      <img src="{{ $post->user->avatar() }}" alt="" class="pcard-avatar">
      <div>
        <strong>{{ $post->user->name }}</strong>
        <span class="muted" style="font-size:12px">{{ $post->created_at->diffForHumans() }}</span>
      </div>
    </a>
    @if($post->location)
      <span class="pcard-location"><span class="material-symbols-outlined md-14">location_on</span> {{ $post->location }}</span>
    @endif
  </div>

  <a href="{{ route('posts.show', $post) }}" style="color:inherit;text-decoration:none">
    <h3 style="padding:0 18px;margin:0 0 8px;font-size:18px">{{ $post->title }}</h3>
  </a>

  @if($post->body)
    <div class="pcard-body">
      <p>{{ \Illuminate\Support\Str::limit($post->body, 200) }}</p>
    </div>
  @endif

  @if($post->media->count())
    <div class="pcard-media">
      @if($post->media->count() === 1)
        @if($post->media->first()->isVideo())
          <video controls class="pcard-video" src="{{ $post->media->first()->url }}"></video>
        @else
          <img src="{{ $post->media->first()->url }}" alt="" class="pcard-image" onclick="openPostViewer({{ $post->id }})" style="cursor:pointer">
        @endif
      @else
        <div class="photo-carousel">
          @foreach($post->media as $m)
            @if($m->isVideo())
              <div class="c-item" style="background:#000">
                <video controls style="width:100%;height:100%;object-fit:cover" src="{{ $m->url }}"></video>
              </div>
            @else
              <div class="c-item" style="background-image:url('{{ $m->url }}')" onclick="openPostViewer({{ $post->id }})">
                <img src="{{ $m->url }}" alt="" style="width:100%;height:100%;object-fit:cover">
              </div>
            @endif
          @endforeach
        </div>
      @endif
    </div>
  @endif

  @if($post->trip)
    <div class="pcard-trip">
      <a href="{{ route('trip.show', $post->trip) }}">
        <span class="material-symbols-outlined md-16">map</span> {{ $post->trip->title }} ({{ $post->trip->days }} days)
      </a>
    </div>
  @endif

  <div class="pcard-foot">
    <button class="pcard-action like-btn {{ $post->isLikedBy(auth()->user()) ? 'liked' : '' }}"
            data-post-id="{{ $post->id }}"
            onclick="toggleLike({{ $post->id }})">
      <span class="material-symbols-outlined md-20 {{ $post->isLikedBy(auth()->user()) ? 'filled' : '' }}">favorite</span>
      <span class="like-count">{{ $post->likes_count }}</span>
    </button>
    <button class="pcard-action" onclick="toggleComments({{ $post->id }})">
      <span class="material-symbols-outlined md-20">chat_bubble</span>
      <span>{{ $post->comments_count }}</span>
    </button>
    <button class="pcard-action" onclick="sharePost('{{ route('posts.show', $post) }}', '{{ $post->title }}')">
      <span class="material-symbols-outlined md-20">share</span>
      Share
    </button>
  </div>

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
