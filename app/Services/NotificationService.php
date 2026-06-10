<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Reply;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\CommentNotification;
use App\Notifications\FollowNotification;
use App\Notifications\LikeNotification;
use App\Notifications\ReplyNotification;
use App\Notifications\ReviewNotification;

class NotificationService
{
    public function sendLikeNotification(User $liker, Like $like): void
    {
        $likeable = $like->likeable;
        
        if (!$likeable) {
            return;
        }

        // Don't notify if liking own content
        if ($likeable instanceof Trip && $likeable->user_id === $liker->id) {
            return;
        }
        
        if ($likeable instanceof Post && $likeable->user_id === $liker->id) {
            return;
        }
        
        if ($likeable instanceof Review && $likeable->user_id === $liker->id) {
            return;
        }

        $notifiable = match(true) {
            $likeable instanceof Trip => $likeable->user,
            $likeable instanceof Post => $likeable->user,
            $likeable instanceof Review => $likeable->user,
            default => null,
        };

        if ($notifiable) {
            $notifiable->notify(new LikeNotification($liker, get_class($likeable), $likeable->id));
        }
    }

    public function sendCommentNotification(User $commenter, Comment $comment): void
    {
        $commentable = $comment->trip ?? $comment->post;
        
        if (!$commentable) {
            return;
        }

        // Don't notify if commenting on own content
        if ($commentable instanceof Trip && $commentable->user_id === $commenter->id) {
            return;
        }
        
        if ($commentable instanceof Post && $commentable->user_id === $commenter->id) {
            return;
        }

        $notifiable = match(true) {
            $commentable instanceof Trip => $commentable->user,
            $commentable instanceof Post => $commentable->user,
            default => null,
        };

        if ($notifiable) {
            $notifiable->notify(new CommentNotification($commenter, $comment));
        }
    }

    public function sendReplyNotification(User $replier, Reply $reply): void
    {
        $comment = $reply->comment;
        
        // Don't notify if replying to own comment
        if ($comment->user_id === $replier->id) {
            return;
        }

        $comment->user->notify(new ReplyNotification($replier, $reply));
    }

    public function sendFollowNotification(User $follower, User $following): void
    {
        // Don't notify if following self
        if ($follower->id === $following->id) {
            return;
        }

        $following->notify(new FollowNotification($follower));
    }

    public function sendReviewNotification(User $reviewer, Review $review): void
    {
        $reviewable = $review->reviewable;
        
        if (!$reviewable) {
            return;
        }

        // Send notification if reviewed a trip owned by another user
        if ($reviewable instanceof Trip && $reviewable->user_id && $reviewable->user_id !== $reviewer->id) {
            $reviewable->user->notify(new ReviewNotification($reviewer, $review));
        }
    }
}