<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $follower
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
            'follower_avatar' => $this->follower->avatar(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->follower->name . ' started following you')
            ->line($this->follower->name . ' started following you on Yatri.')
            ->action('View Profile', url('/u/' . $this->follower->name))
            ->line('Thank you for using Yatri!');
    }
}