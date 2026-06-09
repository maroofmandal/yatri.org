@extends('layouts.app')
@section('title', $post->title.' — Yatri')
@section('meta_description', $post->meta_description)

@section('content')
<div class="wrap" style="max-width:640px;margin-top:40px">
  <article>
    <header style="margin-bottom:24px">
      <h1 style="font-size:clamp(24px,4vw,32px)">{{ $post->title }}</h1>
      <div style="display:flex;align-items:center;gap:12px;margin-top:12px">
        <a href="{{ route('profile', $post->user) }}" style="display:flex;align-items:center;gap:8px;color:inherit;text-decoration:none">
          <img src="{{ $post->user->avatar() }}" alt="" style="width:40px;height:40px;border-radius:50%">
          <div>
            <strong>{{ $post->user->name }}</strong>
            <span class="muted" style="font-size:12px;display:block">{{ $post->created_at->diffForHumans() }}</span>
          </div>
        </a>
        @if($post->location)
          <span class="muted" style="margin-left:auto">📍 {{ $post->location }}</span>
        @endif
      </div>
    </header>

    @if($post->media->count())
      <div style="margin-bottom:24px">
        @if($post->media->count() === 1)
          @if($post->media->first()->isVideo())
            <video controls style="width:100%;border-radius:var(--r)" src="{{ $post->media->first()->url }}"></video>
          @else
            <img src="{{ $post->media->first()->url }}" alt="{{ $post->title }}" style="width:100%;border-radius:var(--r)">
          @endif
        @else
          <div class="photo-carousel">
            @foreach($post->media as $m)
              @if($m->isVideo())
                <div class="c-item" style="background:#000">
                  <video controls style="width:100%;height:100%;object-fit:cover" src="{{ $m->url }}"></video>
                </div>
              @else
                <div class="c-item" style="background-image:url('{{ $m->url }}')">
                  <img src="{{ $m->url }}" alt="" style="width:100%;height:100%;object-fit:cover">
                </div>
              @endif
            @endforeach
          </div>
        @endif
      </div>
    @endif

    @if($post->body)
      <div style="font-size:16px;line-height:1.7;margin-bottom:24px">
        {!! nl2br(e($post->body)) !!}
      </div>
    @endif

    @if($post->trip)
      <div class="pcard-trip" style="margin-bottom:24px">
        <a href="{{ route('trip.show', $post->trip) }}">
          🗺️ {{ $post->trip->title }} ({{ $post->trip->days }} days)
        </a>
      </div>
    @endif

    <div class="pcard-foot" style="border-top:1px solid var(--line);padding-top:16px">
      <button class="pcard-action like-btn {{ $post->isLikedBy(auth()->user()) ? 'liked' : '' }}" 
              data-post-id="{{ $post->id }}"
              onclick="toggleLike({{ $post->id }})">
        <span class="like-icon">♥</span>
        <span class="like-count">{{ $post->likes_count }}</span>
      </button>
      <button class="pcard-action" onclick="toggleComments({{ $post->id }})">
        💬 <span>{{ $post->comments_count }}</span>
      </button>
      <button class="pcard-action">
        🔗 Share
      </button>
    </div>

    <div class="pcard-comments" id="comments-{{ $post->id }}" style="display:block;margin-top:24px">
      <h3 style="margin-bottom:16px">Comments</h3>
      <div class="comment-list" id="comment-list-{{ $post->id }}">
        @foreach($post->comments as $comment)
          @include('partials.comment-item', ['comment' => $comment])
        @endforeach
      </div>
      
      @auth
        <form class="comment-form" onsubmit="submitComment(event, {{ $post->id }})" style="margin-top:16px">
          <input type="text" placeholder="Add a comment..." required maxlength="1000">
          <button type="submit" class="btn btn-accent btn-sm">Post</button>
        </form>
      @endauth
    </div>
  </article>

  @if(auth()->id() === $post->user_id || auth()->user()->isAdmin())
    <form method="POST" action="{{ route('posts.destroy', $post) }}" style="margin-top:24px" onsubmit="return confirm('Delete this post?')">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--accent)">Delete Post</button>
    </form>
  @endif
</div>
@endsection