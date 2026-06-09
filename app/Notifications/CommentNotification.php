<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $commenter,
        public Comment $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $commentable = $this->comment->trip ?? $this->comment->post;
        
        return [
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name,
            'commenter_avatar' => $this->commenter->avatar(),
            'comment_id' => $this->comment->id,
            'comment_body' => $this->comment->body,
            'commentable_type' => $this->comment->trip_id ? 'Trip' : 'Post',
            'commentable_id' => $this->comment->trip_id ?? $this->comment->post_id,
            'commentable_title' => $commentable->title ?? 'your content',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->commenter->name . ' commented on your post')
            ->line($this->commenter->name . ' commented: "' . $this->comment->body . '"')
            ->action('View', url('/'))
            ->line('Thank you for using Yatri!');
    }
}