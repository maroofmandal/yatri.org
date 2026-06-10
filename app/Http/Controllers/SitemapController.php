<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $posts = Post::where('is_public', true)->latest()->get();
        $trips = Trip::where('is_public', true)->where('status', 'ready')->latest()->get();
        $users = User::whereHas('posts', fn($q) => $q->where('is_public', true))
            ->orWhereHas('trips', fn($q) => $q->where('is_public', true)->where('status', 'ready'))
            ->get();

        return Response::view('sitemap', [
            'posts' => $posts,
            'trips' => $trips,
            'users' => $users,
        ])->header('Content-Type', 'application/xml');
    }
}
