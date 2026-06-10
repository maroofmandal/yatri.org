<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Trip;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function like(Trip $trip)
    {
        $user = auth()->user();
        $existing = $trip->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $like = $trip->likes()->create(['user_id' => $user->id]);
            $this->notificationService->sendLikeNotification($user, $like);
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'count' => $trip->likes()->count()]);
    }

    public function likePost($postId)
    {
        $post = Post::findOrFail($postId);
        $user = auth()->user();
        $existing = $post->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $like = $post->likes()->create(['user_id' => $user->id]);
            $this->notificationService->sendLikeNotification($user, $like);
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'count' => $post->likes()->count()]);
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
}