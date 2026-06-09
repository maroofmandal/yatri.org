@extends('layouts.app')
@section('title', 'Notifications — Yatri')

@section('content')
<header class="hero" style="padding:40px 0 30px"><div class="wrap">
  <p class="eyebrow">Stay updated</p>
  <h1><strong>Notifications</strong></h1>
</div></header>

<div class="wrap" style="max-width:640px;margin-top:32px">
  @if($notifications->count())
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
      <button onclick="markAllRead()" class="btn btn-ghost btn-sm">Mark all as read</button>
    </div>

    <div class="notifications-list">
      @foreach($notifications as $notification)
        <div class="notification-item {{ is_null($notification->read_at) ? 'unread' : '' }}" 
             id="notification-{{ $notification->id }}">
          <div class="notification-avatar">
            @if(isset($notification->data['liker_avatar']))
              <img src="{{ $notification->data['liker_avatar'] }}" alt="">
            @elseif(isset($notification->data['follower_avatar']))
              <img src="{{ $notification->data['follower_avatar'] }}" alt="">
            @elseif(isset($notification->data['commenter_avatar']))
              <img src="{{ $notification->data['commenter_avatar'] }}" alt="">
            @elseif(isset($notification->data['replier_avatar']))
              <img src="{{ $notification->data['replier_avatar'] }}" alt="">
            @else
              <div class="notification-icon">🔔</div>
            @endif
          </div>
          
          <div class="notification-content">
            @if(str_contains($notification->type, 'LikeNotification'))
              <p><strong>{{ $notification->data['liker_name'] }}</strong> liked your {{ $notification->data['likeable_type'] }}</p>
              <span class="muted" style="font-size:12px">{{ $notification->created_at->diffForHumans() }}</span>
            
            @elseif(str_contains($notification->type, 'CommentNotification'))
              <p><strong>{{ $notification->data['commenter_name'] }}</strong> commented on your {{ $notification->data['commentable_type'] }}</p>
              <p class="notification-preview">{{ Str::limit($notification->data['comment_body'], 100) }}</p>
              <span class="muted" style="font-size:12px">{{ $notification->created_at->diffForHumans() }}</span>
            
            @elseif(str_contains($notification->type, 'ReplyNotification'))
              <p><strong>{{ $notification->data['replier_name'] }}</strong> replied to your comment</p>
              <p class="notification-preview">{{ Str::limit($notification->data['reply_body'], 100) }}</p>
              <span class="muted" style="font-size:12px">{{ $notification->created_at->diffForHumans() }}</span>
            
            @elseif(str_contains($notification->type, 'FollowNotification'))
              <p><strong>{{ $notification->data['follower_name'] }}</strong> started following you</p>
              <span class="muted" style="font-size:12px">{{ $notification->created_at->diffForHumans() }}</span>
            
            @else
              <p>{{ json_encode($notification->data) }}</p>
              <span class="muted" style="font-size:12px">{{ $notification->created_at->diffForHumans() }}</span>
            @endif
          </div>

          @if(is_null($notification->read_at))
            <button onclick="markAsRead('{{ $notification->id }}')" class="notification-read-btn">✓</button>
          @endif
        </div>
      @endforeach
    </div>

    {{ $notifications->links() }}
  @else
    <div class="block center">
      <p class="lead">No notifications yet. Start following travelers and engaging with their content!</p>
      <a class="btn btn-accent" href="{{ route('home') }}">Explore</a>
    </div>
  @endif
</div>

<style>
.notifications-list { display:flex;flex-direction:column;gap:8px }
.notification-item { display:flex;gap:14px;padding:16px;background:var(--card);border:1px solid var(--line);border-radius:var(--r);align-items:flex-start;transition:background .2s }
.notification-item:hover { background:var(--bg) }
.notification-item.unread { background:#fef2f2;border-color:#fecaca }
.notification-avatar { width:44px;height:44px;border-radius:50%;overflow:hidden;flex-shrink:0 }
.notification-avatar img { width:100%;height:100%;object-fit:cover }
.notification-icon { width:44px;height:44px;border-radius:50%;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:20px }
.notification-content { flex:1 }
.notification-content p { margin:0 0 4px;font-size:14px }
.notification-preview { color:var(--muted);font-size:13px;margin-top:4px }
.notification-read-btn { background:none;border:1px solid var(--line);border-radius:50%;width:28px;height:28px;cursor:pointer;color:var(--green);font-size:14px;flex-shrink:0 }
.notification-read-btn:hover { background:var(--bg) }
</style>

@push('scripts')
<script>
function markAsRead(id) {
    fetch('/notifications/' + id + '/read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(() => {
        document.getElementById('notification-' + id).classList.remove('unread');
        document.getElementById('notification-' + id).querySelector('.notification-read-btn')?.remove();
    });
}

function markAllRead() {
    fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(() => {
        document.querySelectorAll('.notification-item.unread').forEach(el => {
            el.classList.remove('unread');
            el.querySelector('.notification-read-btn')?.remove();
        });
    });
}
</script>
@endpush
@endsection