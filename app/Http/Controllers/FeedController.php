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
            ->paginate(10);

        $destinations = Destination::active()->orderByDesc('popularity')->limit(8)->get();

        $latestTrips = Trip::query()
            ->where('is_public', true)
            ->where('status', 'ready')
            ->with('user', 'posts.media', 'media')
            ->withCount(['likes', 'comments'])
            ->latest()
            ->limit(3)
            ->get();

        return view('feed', compact('posts', 'destinations', 'latestTrips'));
    }

    public function trips(Request $request)
    {
        $query = Trip::query()
            ->where('is_public', true)
            ->where('status', 'ready')
            ->with('user', 'posts.media', 'media')
            ->withCount(['likes', 'comments']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('origin', 'like', "%{$search}%")
                  ->orWhere('destinations', 'like', "%{$search}%");
            });
        }

        // Sort
        switch ($request->input('sort', 'latest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'budget_low':
                $query->orderBy('budget_total', 'asc');
                break;
            case 'budget_high':
                $query->orderBy('budget_total', 'desc');
                break;
            case 'most_liked':
                $query->orderByDesc('likes_count');
                break;
            default:
                $query->latest();
        }

        // Following filter
        if ($request->input('filter') === 'following' && $request->user()) {
            $query->whereIn('user_id', $request->user()->following()->pluck('users.id'));
        }

        // Destination filter
        if ($dest = $request->input('destination')) {
            $query->where(function ($q) use ($dest) {
                $q->where('destinations', 'like', "%{$dest}%")
                  ->orWhere('title', 'like', "%{$dest}%")
                  ->orWhere('origin', 'like', "%{$dest}%");
            });
        }

        $trips = $query->paginate(12)->withQueryString();
        $destinations = Destination::active()->orderByDesc('popularity')->limit(8)->get();

        return view('trips.index', compact('trips', 'destinations'));
    }
}
