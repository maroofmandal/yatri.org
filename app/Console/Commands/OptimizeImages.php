<?php

namespace App\Console\Commands;

use App\Helpers\ImageOptimizer;
use App\Models\Media;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'yatri:optimize-images';
    protected $description = 'Resize and convert existing images to WebP';

    public function handle()
    {
        $count = 0;

        // Optimize avatars
        $avatars = Storage::disk('public')->files('avatars');
        foreach ($avatars as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) continue;
            $this->info("Optimizing avatar: $file");
            $result = ImageOptimizer::optimizeExisting($file);
            if ($result) {
                Storage::disk('public')->delete($file);
                $url = Storage::disk('public')->url($result);
                User::where('avatar_url', Storage::disk('public')->url($file))
                    ->orWhere('avatar_url', Storage::disk('public')->url($file))
                    ->each(fn($u) => $u->update(['avatar_url' => $url]));
                $count++;
                $this->info("  -> $result");
            }
        }

        // Optimize post/media images
        foreach (['posts', 'media'] as $dir) {
            $files = Storage::disk('public')->files($dir);
            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) continue;
                $this->info("Optimizing: $file");
                $result = ImageOptimizer::optimizeExisting($file);
                if ($result) {
                    Storage::disk('public')->delete($file);
                    Media::where('path', $file)->each(fn($m) => $m->update(['path' => $result]));
                    $count++;
                    $this->info("  -> $result");
                }
            }
        }

        $this->info("Optimized $count images.");
    }
}
