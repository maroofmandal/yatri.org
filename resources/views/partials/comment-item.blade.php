<div class="comment-item">
  <a href="{{ route('profile', $comment->user) }}">
    <img src="{{ $comment->user->avatar() }}" alt="" class="comment-avatar">
  </a>
  <div class="comment-content">
    <div class="comment-header">
      <a href="{{ route('profile', $comment->user) }}"><strong>{{ $comment->user->name }}</strong></a>
      <span class="muted" style="font-size:11px">{{ $comment->created_at->diffForHumans() }}</span>
    </div>
    <p class="comment-body">{{ $comment->body }}</p>
    @auth
      <button class="comment-reply-btn" onclick="toggleReplyForm({{ $comment->id }})">
        <span class="material-symbols-outlined md-14" style="vertical-align:middle">reply</span> Reply
      </button>
    @endauth

    @if($comment->childReplies->count())
      <div class="comment-replies">
        @foreach($comment->childReplies as $reply)
          <div class="reply-item">
            <a href="{{ route('profile', $reply->user) }}">
              <img src="{{ $reply->user->avatar() }}" alt="" class="reply-avatar">
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
