<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;

class ProfileController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function show(User $user)
    {
        $trips = $user->trips()
            ->where('is_public', true)
            ->where('status', 'ready')
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        $posts = $user->posts()
            ->where('is_public', true)
            ->with(['media', 'likes', 'comments'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        $reviews = $user->reviews()
            ->with('reviewable')
            ->withCount(['likes'])
            ->latest()
            ->get();

        $media = \App\Models\Media::where('mediable_type', \App\Models\Post::class)
            ->whereIn('mediable_id', $user->posts()->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'trips' => $user->trips()->where('status', 'ready')->count(),
            'followers' => $user->followers()->count(),
            'following' => $user->following()->count(),
            'countries' => $this->distinctCountries($user),
            'total_likes' => $user->total_likes_received,
            'total_days' => $user->total_days_traveled,
            'total_media' => $media->count(),
        ];

        return view('profile', compact('user', 'trips', 'posts', 'reviews', 'media', 'stats'));
    }

    public function follow(User $user)
    {
        if ($user->id !== auth()->id()) {
            auth()->user()->following()->syncWithoutDetaching([$user->id]);
            
            // Send notification
            $this->notificationService->sendFollowNotification(auth()->user(), $user);
        }

        return back();
    }

    public function unfollow(User $user)
    {
        auth()->user()->following()->detach($user->id);

        return back();
    }

    protected function distinctCountries(User $user): int
    {
        return $user->trips()
            ->where('status', 'ready')
            ->get()
            ->flatMap(fn ($t) => collect($t->destinations)->pluck('name'))
            ->unique()
            ->count();
    }
}