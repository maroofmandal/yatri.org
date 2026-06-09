<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LikeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $liker,
        public string $likeableType,
        public int $likeableId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $likeable = $this->likeableType::find($this->likeableId);
        
        return [
            'liker_id' => $this->liker->id,
            'liker_name' => $this->liker->name,
            'liker_avatar' => $this->liker->avatar(),
            'likeable_type' => class_basename($this->likeableType),
            'likeable_id' => $this->likeableId,
            'likeable_title' => $likeable->title ?? $likeable->body ?? 'your content',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->liker->name . ' liked your ' . class_basename($this->likeableType))
            ->line($this->liker->name . ' liked your ' . class_basename($this->likeableType) . '.')
            ->action('View', url('/'))
            ->line('Thank you for using Yatri!');
    }
}