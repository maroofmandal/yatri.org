<?php

namespace App\Notifications;

use App\Models\Review;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $reviewer,
        public Review $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reviewer_id' => $this->reviewer->id,
            'reviewer_name' => $this->reviewer->name,
            'reviewer_avatar' => $this->reviewer->avatar(),
            'review_id' => $this->review->id,
            'review_title' => $this->review->title,
            'review_rating' => $this->review->rating,
            'reviewable_type' => class_basename($this->review->reviewable_type),
            'reviewable_id' => $this->review->reviewable_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->reviewer->name . ' left a review')
            ->line($this->reviewer->name . ' left a ' . $this->review->rating . '-star review.')
            ->action('View', url('/'))
            ->line('Thank you for using Yatri!');
    }
}