<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Trip;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $trips = Trip::query()
            ->where('is_public', true)
            ->where('status', 'ready')
            ->with('user')
            ->withCount(['likes', 'comments'])
            ->when($request->filter === 'following' && $request->user(), function ($q) use ($request) {
                $q->whereIn('user_id', $request->user()->following()->pluck('users.id'));
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $destinations = Destination::active()->orderByDesc('popularity')->limit(8)->get();

        return view('feed', compact('trips', 'destinations'));
    }
}
