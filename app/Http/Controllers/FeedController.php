<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Post;
use App\Models\Trip;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()
            ->with('user', 'media', 'trip')
            ->withCount(['likes', 'comments'])
            ->where('is_public', true)
            ->latest()
            ->take(10)
            ->get();

        $trips = Trip::query()
            ->where('is_public', true)
            ->where('status', 'ready')
            ->with('user')
            ->withCount(['likes', 'comments'])
            ->when($request->filter === 'following' && $request->user(), function ($q) use ($request) {
                $q->whereIn('user_id', $request->user()->following()->pluck('users.id'));
            })
            ->latest()
            ->take(10)
            ->get();

        $destinations = Destination::active()->orderByDesc('popularity')->limit(8)->get();

        return view('feed', compact('posts', 'trips', 'destinations'));
    }

    public function trips(Request $request)
    {
        $trips = Trip::query()
            ->where('is_public', true)
            ->where('status', 'ready')
            ->with('user')
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('trips.index', compact('trips'));
    }
}
