<?php

namespace App\Http\Controllers;

use App\Helpers\ImageOptimizer;
use App\Models\Media;
use App\Models\Post;
use App\Models\Review;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,mp4,quicktime|max:10240',
            'mediable_type' => 'required|in:post,trip,review',
            'mediable_id' => 'required|integer',
        ]);

        $mediableType = match($data['mediable_type']) {
            'post' => Post::class,
            'trip' => Trip::class,
            'review' => Review::class,
        };

        $mediable = $mediableType::find($data['mediable_id']);
        
        if (!$mediable) {
            return response()->json(['error' => 'Invalid target.'], 422);
        }

        // Check ownership
        $userId = $mediable->user_id ?? auth()->id();
        if ($userId !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $file = $request->file('file');
        $type = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'photo';
        $path = $type === 'video'
            ? $file->store('media', 'public')
            : ImageOptimizer::optimizeGeneric($file, 'media');

        $media = Media::create([
            'mediable_type' => $mediableType,
            'mediable_id' => $data['mediable_id'],
            'type' => $type,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'order' => $mediable->media()->count(),
        ]);

        return response()->json(['media' => $media]);
    }

    public function destroy(Media $media)
    {
        // Check ownership
        $mediable = $media->mediable;
        $userId = $mediable->user_id ?? auth()->id();
        
        if ($userId !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $legacy = preg_replace('/\.webp$/', '.jpg', $media->path);
        if ($legacy !== $media->path) Storage::disk('public')->delete($legacy);
        $legacyPng = preg_replace('/\.webp$/', '.png', $media->path);
        if ($legacyPng !== $media->path) Storage::disk('public')->delete($legacyPng);
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return response()->json(['ok' => true]);
    }
}