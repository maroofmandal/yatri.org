@extends('layouts.app')
@section('title', 'Notifications — Yatri')

@section('content')
<div class="wrap" style="max-width:680px;padding-top:36px;padding-bottom:100px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div>
      <h2 style="margin:0">Notifications</h2>
      <p class="lead" style="margin:4px 0 0">Stay updated on your activity.</p>
    </div>
    @if($notifications->count())
      <button onclick="markAllRead()" class="btn btn-text btn-sm">
        <x-icon name="done_all" :size="18" /> Mark all read
      </button>
    @endif
  </div>

  @if($notifications->count())
    <div class="notifications-list">
      @foreach($notifications as $notification)
        <div class="notification-item {{ is_null($notification->read_at) ? 'unread' : '' }}" id="notification-{{ $notification->id }}">
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
              <div class="notification-icon"><x-icon name="notifications" /></div>
            @endif
          </div>
          <div class="notification-content">
            @if(str_contains($notification->type, 'LikeNotification'))
              <p><strong>{{ $notification->data['liker_name'] }}</strong> liked your {{ $notification->data['likeable_type'] }}</p>
            @elseif(str_contains($notification->type, 'CommentNotification'))
              <p><strong>{{ $notification->data['commenter_name'] }}</strong> commented on your {{ $notification->data['commentable_type'] }}</p>
              <p class="notification-preview">{{ Str::limit($notification->data['comment_body'], 100) }}</p>
            @elseif(str_contains($notification->type, 'ReplyNotification'))
              <p><strong>{{ $notification->data['replier_name'] }}</strong> replied to your comment</p>
              <p class="notification-preview">{{ Str::limit($notification->data['reply_body'], 100) }}</p>
            @elseif(str_contains($notification->type, 'FollowNotification'))
              <p><strong>{{ $notification->data['follower_name'] }}</strong> started following you</p>
            @else
              <p>{{ json_encode($notification->data) }}</p>
            @endif
            <span class="muted" style="font-size:12px">{{ $notification->created_at->diffForHumans() }}</span>
          </div>
          @if(is_null($notification->read_at))
            <button onclick="markAsRead('{{ $notification->id }}')" class="notification-read-btn">
              <x-icon name="check" :size="16" />
            </button>
          @endif
        </div>
      @endforeach
    </div>
    {{ $notifications->links() }}
  @else
    <div class="block center">
      <x-icon name="notifications_none" :size="36" style="color:var(--md-on-surface-variant);display:block;margin:0 auto 12px" />
      <p class="lead">No notifications yet. Start following travelers and engaging with their content!</p>
      <a class="btn btn-filled" href="{{ route('home') }}">
        <x-icon name="explore" :size="18" /> Explore
      </a>
    </div>
  @endif
</div>

@push('scripts')
<script>
function markAsRead(id) {
  fetch('/notifications/' + id + '/read', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' }
  }).then(() => {
    document.getElementById('notification-' + id).classList.remove('unread');
    document.getElementById('notification-' + id).querySelector('.notification-read-btn')?.remove();
  });
}
function markAllRead() {
  fetch('/notifications/read-all', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' }
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
