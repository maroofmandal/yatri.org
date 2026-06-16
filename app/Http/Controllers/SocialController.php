<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Trip;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function like(Trip $trip)
    {
        return $this->toggleLike($trip);
    }

    public function likePost($postId)
    {
        return $this->toggleLike(Post::findOrFail($postId));
    }

    public function comment(Request $request, Trip $trip)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $comment = $trip->comments()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        $this->notificationService->sendCommentNotification(auth()->user(), $comment);

        return back()->withFragment('comments');
    }

    public function commentPost(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
            'trip_id' => $post->trip_id,
        ]);

        $this->notificationService->sendCommentNotification(auth()->user(), $comment);

        return response()->json(['comment' => $comment->load('user')]);
    }

    public function reply(Request $request, Comment $comment)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:500']]);

        $reply = $comment->childReplies()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        $this->notificationService->sendReplyNotification(auth()->user(), $reply);

        return response()->json(['reply' => $reply->load('user')]);
    }

    public function shareTrip($id)
    {
        return $this->trackShare(Trip::findOrFail($id));
    }

    public function sharePost($postId)
    {
        return $this->trackShare(Post::findOrFail($postId));
    }

    private function toggleLike($likeable): JsonResponse
    {
        $user = auth()->user();
        $existing = $likeable->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $like = $likeable->likes()->create(['user_id' => $user->id]);
            $this->notificationService->sendLikeNotification($user, $like);
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'count' => $likeable->likes()->count()]);
    }

    private function trackShare($shareable): JsonResponse
    {
        $shareable->increment('shares');

        return response()->json(['shares' => $shareable->fresh()->shares]);
    }
}
