<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageOptimizer
{
    const AVATAR_SIZE = 200;
    const POST_MAX_WIDTH = 1200;
    const THUMB_MAX_WIDTH = 400;
    const QUALITY = 85;

    public static function optimizeAvatar(UploadedFile $file, string $disk = 'public'): string
    {
        $filename = 'avatars/' . md5(uniqid()) . '.webp';
        $tmp = $file->getRealPath();
        $img = self::createFromFile($tmp);
        if (!$img) {
            return $file->store('avatars', $disk);
        }
        $img = imagescale($img, self::AVATAR_SIZE, self::AVATAR_SIZE);
        Storage::disk($disk)->put($filename, self::encodeWebp($img));
        imagedestroy($img);
        return $filename;
    }

    public static function optimizePostImage(UploadedFile $file, string $disk = 'public'): string
    {
        $filename = 'posts/' . md5(uniqid()) . '.webp';
        $tmp = $file->getRealPath();
        $img = self::createFromFile($tmp);
        if (!$img) {
            return $file->store('posts', $disk);
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > self::POST_MAX_WIDTH) {
            $ratio = self::POST_MAX_WIDTH / $w;
            $h = (int)($h * $ratio);
            $img = imagescale($img, self::POST_MAX_WIDTH, $h);
        }
        Storage::disk($disk)->put($filename, self::encodeWebp($img));

        // Generate thumbnail
        $thumbRatio = min(self::THUMB_MAX_WIDTH / $w, 1);
        if ($thumbRatio < 1) {
            $thumb = imagescale($img, self::THUMB_MAX_WIDTH, (int)($h * $thumbRatio));
            Storage::disk($disk)->put('posts/thumbs/' . basename($filename), self::encodeWebp($thumb));
            imagedestroy($thumb);
        }

        imagedestroy($img);
        return $filename;
    }

    public static function optimizeGeneric(UploadedFile $file, string $subdir, string $disk = 'public'): string
    {
        $filename = trim($subdir, '/') . '/' . md5(uniqid()) . '.webp';
        $tmp = $file->getRealPath();
        $img = self::createFromFile($tmp);
        if (!$img) {
            return $file->store($subdir, $disk);
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > self::POST_MAX_WIDTH) {
            $ratio = self::POST_MAX_WIDTH / $w;
            $h = (int)($h * $ratio);
            $img = imagescale($img, self::POST_MAX_WIDTH, $h);
        }
        Storage::disk($disk)->put($filename, self::encodeWebp($img));

        $thumbRatio = min(self::THUMB_MAX_WIDTH / $w, 1);
        if ($thumbRatio < 1) {
            $thumb = imagescale($img, self::THUMB_MAX_WIDTH, (int)($h * $thumbRatio));
            Storage::disk($disk)->put(trim($subdir, '/') . '/thumbs/' . basename($filename), self::encodeWebp($thumb));
            imagedestroy($thumb);
        }

        imagedestroy($img);
        return $filename;
    }

    public static function optimizeExisting(string $sourcePath, string $disk = 'public'): ?string
    {
        $fullPath = Storage::disk($disk)->path($sourcePath);
        if (!file_exists($fullPath)) return null;
        $img = self::createFromFile($fullPath);
        if (!$img) return null;
        $isAvatar = str_starts_with($sourcePath, 'avatars/');
        if ($isAvatar) {
            $img = imagescale($img, self::AVATAR_SIZE, self::AVATAR_SIZE);
        } else {
            $w = imagesx($img);
            if ($w > self::POST_MAX_WIDTH) {
                $ratio = self::POST_MAX_WIDTH / $w;
                $img = imagescale($img, self::POST_MAX_WIDTH, (int)(imagesy($img) * $ratio));
            }
        }
        $webpPath = pathinfo($sourcePath, PATHINFO_DIRNAME) . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.webp';
        Storage::disk($disk)->put($webpPath, self::encodeWebp($img));
        imagedestroy($img);
        return $webpPath;
    }

    public static function generateThumb(string $sourcePath, string $disk = 'public'): ?string
    {
        $fullPath = Storage::disk($disk)->path($sourcePath);
        if (!file_exists($fullPath)) return null;
        $img = self::createFromFile($fullPath);
        if (!$img) return null;
        $w = imagesx($img);
        $h = imagesy($img);
        $ratio = min(self::THUMB_MAX_WIDTH / $w, 1);
        if ($ratio >= 1) {
            imagedestroy($img);
            return $sourcePath;
        }
        $thumb = imagescale($img, self::THUMB_MAX_WIDTH, (int)($h * $ratio));
        imagedestroy($img);
        $thumbPath = dirname($sourcePath) . '/thumbs/' . basename($sourcePath);
        Storage::disk($disk)->put($thumbPath, self::encodeWebp($thumb));
        imagedestroy($thumb);
        return $thumbPath;
    }

    private static function createFromFile(string $path): ?\GdImage
    {
        $info = @getimagesize($path);
        if (!$info) return null;
        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };
    }

    private static function encodeWebp(\GdImage $img): string
    {
        ob_start();
        imagewebp($img, null, self::QUALITY);
        return ob_get_clean();
    }
}
