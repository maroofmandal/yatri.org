<?php

namespace App\Notifications;

use App\Models\Reply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $replier,
        public Reply $reply
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'replier_id' => $this->replier->id,
            'replier_name' => $this->replier->name,
            'replier_avatar' => $this->replier->avatar(),
            'reply_id' => $this->reply->id,
            'reply_body' => $this->reply->body,
            'comment_id' => $this->reply->comment_id,
            'comment_body' => $this->reply->comment->body,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->replier->name . ' replied to your comment')
            ->line($this->replier->name . ' replied: "' . $this->reply->body . '"')
            ->action('View', url('/'))
            ->line('Thank you for using Yatri!');
    }
}