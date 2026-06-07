<?php

namespace App\Http\Controllers;

use App\Models\User;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        $trips = $user->trips()
            ->where('is_public', true)
            ->where('status', 'ready')
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        $stats = [
            'trips'     => $user->trips()->where('status', 'ready')->count(),
            'followers' => $user->followers()->count(),
            'following' => $user->following()->count(),
            'countries' => $this->distinctCountries($user),
        ];

        return view('profile', compact('user', 'trips', 'stats'));
    }

    public function follow(User $user)
    {
        if ($user->id !== auth()->id()) {
            auth()->user()->following()->syncWithoutDetaching([$user->id]);
        }

        return back();
    }

    public function unfollow(User $user)
    {
        auth()->user()->following()->detach($user->id);

        return back();
    }

    /** Rough distinct-destination count across a user's trips. */
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
