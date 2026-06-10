<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageOptimizer
{
    const AVATAR_SIZE = 200;
    const POST_MAX_WIDTH = 1200;
    const QUALITY = 85;

    public static function optimizeAvatar(UploadedFile $file, string $disk = 'public'): string
    {
        $img = Image::read($file->getRealPath());
        $img->coverDown(self::AVATAR_SIZE, self::AVATAR_SIZE);
        $filename = 'avatars/' . md5(uniqid()) . '.webp';
        Storage::disk($disk)->put($filename, $img->toWebp(self::QUALITY));
        return $filename;
    }

    public static function optimizePostImage(UploadedFile $file, string $disk = 'public'): string
    {
        $img = Image::read($file->getRealPath());
        $img->scaleDown(width: min($img->width(), self::POST_MAX_WIDTH));
        $filename = 'posts/' . md5(uniqid()) . '.webp';
        Storage::disk($disk)->put($filename, $img->toWebp(self::QUALITY));
        return $filename;
    }

    public static function optimizeGeneric(UploadedFile $file, string $subdir, string $disk = 'public'): string
    {
        $img = Image::read($file->getRealPath());
        $img->scaleDown(width: min($img->width(), self::POST_MAX_WIDTH));
        $filename = trim($subdir, '/') . '/' . md5(uniqid()) . '.webp';
        Storage::disk($disk)->put($filename, $img->toWebp(self::QUALITY));
        return $filename;
    }

    public static function optimizeExisting(string $sourcePath, string $disk = 'public'): ?string
    {
        $fullPath = Storage::disk($disk)->path($sourcePath);
        if (!file_exists($fullPath)) return null;
        $img = Image::read($fullPath);
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $isAvatar = str_starts_with($sourcePath, 'avatars/');
        $maxW = $isAvatar ? self::AVATAR_SIZE : self::POST_MAX_WIDTH;
        if ($isAvatar) {
            $img->coverDown(self::AVATAR_SIZE, self::AVATAR_SIZE);
        } else {
            $img->scaleDown(width: min($img->width(), $maxW));
        }
        $webpPath = pathinfo($sourcePath, PATHINFO_DIRNAME) . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.webp';
        Storage::disk($disk)->put($webpPath, $img->toWebp(self::QUALITY));
        return $webpPath;
    }
}
