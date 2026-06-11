<?php

namespace App\Http\Controllers;

use App\Helpers\ImageOptimizer;
use App\Models\Media;
use App\Models\Post;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()
            ->where('is_public', true)
            ->with(['user', 'media', 'trip'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('posts.index', compact('posts'));
    }

    public function create()
    {
        $trips = auth()->user()->trips()
            ->where('status', 'ready')
            ->latest()
            ->get();

        return view('posts.create', compact('trips'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|min:6|max:255',
            'body' => 'required_without:media.0|string|max:2000',
            'type' => 'required|in:photo,video,text,review',
            'trip_id' => 'nullable|exists:trips,id',
            'location' => 'nullable|string|max:255',
            'location_lat' => 'nullable|numeric|between:-90,90',
            'location_lng' => 'nullable|numeric|between:-180,180',
            'media' => 'nullable|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,gif,webp,mp4,quicktime|max:10240',
        ]);

        $post = auth()->user()->posts()->create([
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'type' => $data['type'],
            'trip_id' => $data['trip_id'] ?? null,
            'location' => $data['location'] ?? null,
            'location_lat' => $data['location_lat'] ?? null,
            'location_lng' => $data['location_lng'] ?? null,
            'is_public' => true,
        ]);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $index => $file) {
                $type = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'photo';
                $path = $type === 'video'
                    ? $file->store('posts', 'public')
                    : ImageOptimizer::optimizePostImage($file);
                
                $post->media()->create([
                    'type' => $type,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'order' => $index,
                ]);
            }
        }

        return redirect()->route('profile', auth()->user())->with('ok', 'Post created!');
    }

    public function show(Post $post)
    {
        $post->load(['user', 'media', 'trip', 'comments.user', 'likes']);
        $post->loadCount(['likes', 'comments']);
        $post->increment('views');

        return view('posts.show', compact('post'));
    }

    public function viewer($postId)
    {
        $post = Post::with(['user', 'media', 'comments.user', 'likes'])->findOrFail($postId);
        $post->loadCount(['likes', 'comments']);
        $post->increment('views');

        $images = $post->media->where('type', 'photo')->values()->map(fn($m) => [
            'url' => $m->url,
            'id' => $m->id,
        ]);

        $comments = $post->comments->map(fn($c) => [
            'id' => $c->id,
            'body' => $c->body,
            'created_at' => $c->created_at->diffForHumans(),
            'user' => [
                'name' => $c->user->name,
                'avatar' => $c->user->avatar(),
            ],
        ]);

        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'author' => [
                'name' => $post->user->name,
                'avatar' => $post->user->avatar(),
                'url' => route('profile', $post->user),
            ],
            'images' => $images,
            'liked' => auth()->check() ? $post->isLikedBy(auth()->user()) : false,
            'likes_count' => $post->likes_count,
            'comments_count' => $post->comments_count,
            'comments' => $comments,
            'can_comment' => auth()->check(),
        ]);
    }

    public function destroy(Post $post)
    {
        if ($post->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        // Delete media files
        foreach ($post->media as $media) {
            Storage::disk('public')->delete($media->path);
            $media->delete();
        }

        $post->delete();

        return back()->with('ok', 'Post deleted!');
    }
}