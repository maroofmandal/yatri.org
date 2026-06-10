<div class="comment-item">
  <a href="{{ route('profile', $comment->user) }}">
    <img src="{{ $comment->user->avatar() }}" alt="{{ $comment->user->name }}" class="comment-avatar" width="32" height="32">
  </a>
  <div class="comment-content">
    <div class="comment-header">
      <a href="{{ route('profile', $comment->user) }}"><strong>{{ $comment->user->name }}</strong></a>
      <span class="muted" style="font-size:11px">{{ $comment->created_at->diffForHumans() }}</span>
    </div>
    <p class="comment-body">{{ $comment->body }}</p>
    @auth
      <button class="comment-reply-btn" onclick="toggleReplyForm({{ $comment->id }})">
        <x-icon name="reply" :size="14" /> Reply
      </button>
    @endauth

    @if($comment->childReplies->count())
      <div class="comment-replies">
        @foreach($comment->childReplies as $reply)
          <div class="reply-item">
            <a href="{{ route('profile', $reply->user) }}">
              <img src="{{ $reply->user->avatar() }}" alt="{{ $reply->user->name }}" class="reply-avatar" width="24" height="24">
            </a>
            <div class="reply-content">
              <div class="reply-header">
                <a href="{{ route('profile', $reply->user) }}"><strong>{{ $reply->user->name }}</strong></a>
                <span class="muted" style="font-size:10px">{{ $reply->created_at->diffForHumans() }}</span>
              </div>
              <p class="reply-body">{{ $reply->body }}</p>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    @auth
      <form class="reply-form" id="reply-form-{{ $comment->id }}" style="display:none" onsubmit="submitReply(event, {{ $comment->id }})">
        <input type="text" placeholder="Write a reply..." required maxlength="500">
        <button type="submit" class="btn btn-filled btn-sm">Reply</button>
      </form>
    @endauth
  </div>
</div>
