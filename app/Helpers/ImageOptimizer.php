<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageOptimizer
{
    const AVATAR_SIZE = 200;
    const POST_MAX_WIDTH = 1200;
    const THUMB_MAX_WIDTH = 400;
    const THUMB_SM_MAX_WIDTH = 200;
    const QUALITY = 75;
    const THUMB_QUALITY = 60;

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
        Storage::disk($disk)->put($filename, self::encodeWebp($img, self::QUALITY));

        // Generate thumbnail (400px, Q60)
        $thumbRatio = min(self::THUMB_MAX_WIDTH / $w, 1);
        if ($thumbRatio < 1) {
            $thumb = imagescale($img, self::THUMB_MAX_WIDTH, (int)($h * $thumbRatio));
            Storage::disk($disk)->put('posts/thumbs/' . basename($filename), self::encodeWebp($thumb, self::THUMB_QUALITY));
            imagedestroy($thumb);
        }

        // Generate small thumbnail (200px, Q60) for carousel
        $smRatio = min(self::THUMB_SM_MAX_WIDTH / $w, 1);
        if ($smRatio < 1) {
            $thumbSm = imagescale($img, self::THUMB_SM_MAX_WIDTH, (int)($h * $smRatio));
            Storage::disk($disk)->put('posts/thumbs/sm/' . basename($filename), self::encodeWebp($thumbSm, self::THUMB_QUALITY));
            imagedestroy($thumbSm);
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
        Storage::disk($disk)->put($filename, self::encodeWebp($img, self::QUALITY));

        $thumbRatio = min(self::THUMB_MAX_WIDTH / $w, 1);
        if ($thumbRatio < 1) {
            $thumb = imagescale($img, self::THUMB_MAX_WIDTH, (int)($h * $thumbRatio));
            Storage::disk($disk)->put(trim($subdir, '/') . '/thumbs/' . basename($filename), self::encodeWebp($thumb, self::THUMB_QUALITY));
            imagedestroy($thumb);
        }

        $smRatio = min(self::THUMB_SM_MAX_WIDTH / $w, 1);
        if ($smRatio < 1) {
            $thumbSm = imagescale($img, self::THUMB_SM_MAX_WIDTH, (int)($h * $smRatio));
            Storage::disk($disk)->put(trim($subdir, '/') . '/thumbs/sm/' . basename($filename), self::encodeWebp($thumbSm, self::THUMB_QUALITY));
            imagedestroy($thumbSm);
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
        Storage::disk($disk)->put($webpPath, self::encodeWebp($img, self::QUALITY));
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
            // Still generate thumb_sm if original > 200px
            $smRatio = min(self::THUMB_SM_MAX_WIDTH / $w, 1);
            if ($smRatio >= 1) {
                return $sourcePath;
            }
            $thumbSm = imagescale($img, self::THUMB_SM_MAX_WIDTH, (int)($h * $smRatio));
            Storage::disk($disk)->put(dirname($sourcePath) . '/thumbs/sm/' . basename($sourcePath), self::encodeWebp($thumbSm, self::THUMB_QUALITY));
            imagedestroy($thumbSm);
            return $sourcePath;
        }
        $thumb = imagescale($img, self::THUMB_MAX_WIDTH, (int)($h * $ratio));
        Storage::disk($disk)->put(dirname($sourcePath) . '/thumbs/' . basename($sourcePath), self::encodeWebp($thumb, self::THUMB_QUALITY));
        imagedestroy($thumb);
        imagedestroy($img);

        // Generate thumb_sm from original
        $smRatio = min(self::THUMB_SM_MAX_WIDTH / $w, 1);
        if ($smRatio < 1) {
            $img2 = self::createFromFile($fullPath);
            if ($img2) {
                $thumbSm = imagescale($img2, self::THUMB_SM_MAX_WIDTH, (int)($h * $smRatio));
                Storage::disk($disk)->put(dirname($sourcePath) . '/thumbs/sm/' . basename($sourcePath), self::encodeWebp($thumbSm, self::THUMB_QUALITY));
                imagedestroy($thumbSm);
                imagedestroy($img2);
            }
        }

        return dirname($sourcePath) . '/thumbs/' . basename($sourcePath);
    }

    public static function generateThumbSm(string $sourcePath, string $disk = 'public'): ?string
    {
        $fullPath = Storage::disk($disk)->path($sourcePath);
        if (!file_exists($fullPath)) return null;
        $img = self::createFromFile($fullPath);
        if (!$img) return null;
        $w = imagesx($img);
        $h = imagesy($img);
        $ratio = min(self::THUMB_SM_MAX_WIDTH / $w, 1);
        if ($ratio >= 1) {
            imagedestroy($img);
            return $sourcePath;
        }
        $thumbSm = imagescale($img, self::THUMB_SM_MAX_WIDTH, (int)($h * $ratio));
        imagedestroy($img);
        $smPath = dirname($sourcePath) . '/thumbs/sm/' . basename($sourcePath);
        Storage::disk($disk)->put($smPath, self::encodeWebp($thumbSm, self::THUMB_QUALITY));
        imagedestroy($thumbSm);
        return $smPath;
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

    private static function encodeWebp(\GdImage $img, int $quality = null): string
    {
        ob_start();
        imagewebp($img, null, $quality ?? self::QUALITY);
        return ob_get_clean();
    }
}
