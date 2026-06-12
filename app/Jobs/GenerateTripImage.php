<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Services\ImageGen\ImageGenClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateTripImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    protected Trip $trip;

    protected bool $force;

    public function __construct(Trip $trip, bool $force = false)
    {
        $this->trip = $trip->withoutRelations();
        $this->force = $force;
    }

    public function handle(ImageGenClient $client): void
    {
        if (! $client->enabled()) {
            Log::info("Skipping image gen for trip {$this->trip->id}: no API keys configured.");
            return;
        }

        $trip = $this->trip->refresh();
        $plan = $trip->plan ?? [];
        $dests = collect($trip->destinations ?? []);

        // Generate hero/preview image if missing or forced
        if ($this->force || empty($trip->image)) {
            $this->generateHeroImage($client, $trip, $plan, $dests);
        }

        // Generate per-destination images if missing or forced
        $this->generateDestinationImages($client, $trip, $plan, $dests);
    }

    protected function generateHeroImage(ImageGenClient $client, Trip $trip, array $plan, \Illuminate\Support\Collection $dests): void
    {
        $destNames = $dests->pluck('name')->take(3)->implode(', ');
        $origin = $trip->origin;
        $style = $trip->style ?? 'mid';
        $styleLabel = match ($style) {
            'budget' => 'budget-friendly backpacking',
            'luxury' => 'luxury high-end',
            default  => 'mid-range travel',
        };

        // Extract a highlight scene from the plan
        $highlight = '';
        $days = $plan['days'] ?? [];
        foreach ($days as $day) {
            foreach ($day['items'] ?? [] as $item) {
                if (! empty($item['description'])) {
                    $highlight = $item['description'];
                    break 2;
                }
            }
        }

        $destList = $destNames ?: ($dests->first()['name'] ?? 'a beautiful destination');
        $ogPrompt = "Photorealistic travel photography of {$destList}. {$styleLabel} trip from {$origin}. "
            . "Group of diverse travelers joyfully exploring, admiring the view, natural candid expressions, "
            . "golden hour warm sunlight, professional Canon EOS R5 photography, cinematic composition, "
            . "vibrant colors, 8K ultra detailed, National Geographic style, travel magazine quality, "
            . "clear sky, depth of field.";

        if ($highlight) {
            $ogPrompt .= " Scene: {$highlight}.";
        }

        try {
            $imageData = $client->generateImage($ogPrompt, [
                'aspect_ratio' => '16:9',
                'image_size'   => '1K',
                'temperature'  => 0.35,
            ]);

            $subdir = 'trip-images/' . $trip->share_token;
            $path = $client->saveImage($subdir, $imageData);

            $trip->forceFill(['image' => $path])->save();

            Log::info("Generated hero image for trip {$trip->id} using model {$imageData['model']}");
        } catch (\Throwable $e) {
            // Try with a simpler landscape-only prompt (no people, avoids safety filters)
            try {
                $fallbackPrompt = "Award-winning travel destination photography of {$destList}. "
                    . "Stunning landscape, golden hour lighting, professional photography, "
                    . "cinematic composition, vibrant colors, 8K ultra detailed, clear sky, depth of field.";
                $imageData = $client->generateImage($fallbackPrompt, [
                    'aspect_ratio' => '16:9',
                    'image_size'   => '1K',
                    'temperature'  => 0.35,
                ]);
                $subdir = 'trip-images/' . $trip->share_token;
                $path = $client->saveImage($subdir, $imageData);
                $trip->forceFill(['image' => $path])->save();
                Log::info("Generated fallback hero image for trip {$trip->id}");
            } catch (\Throwable $e2) {
                Log::error("Failed to generate hero image for trip {$trip->id}: " . $e2->getMessage());
            }
        }
    }

    protected function generateDestinationImages(ImageGenClient $client, Trip $trip, array $plan, \Illuminate\Support\Collection $dests): void
    {
        $existingDests = collect($plan['route'] ?? $dests);
        $subdir = 'trip-images/' . $trip->share_token . '/destinations';

        // Track destination images in the plan
        $destImages = $plan['destination_images'] ?? [];

        foreach ($existingDests as $dest) {
            $name = $dest['name'] ?? '';
            if (empty($name)) {
                continue;
            }

            // Skip if image already exists unless forced
            $key = mb_strtolower($name);
            if (! $this->force && ! empty($destImages[$key])) {
                continue;
            }

            $prompt = "Photorealistic travel photography of {$name}. "
                . "Stunning iconic landmark or cityscape, golden hour warm sunlight, "
                . "professional Canon EOS R5 photography, cinematic composition, "
                . "vibrant colors, 8K ultra detailed, National Geographic style, "
                . "travel magazine quality, clear sky, depth of field.";

            try {
                $imageData = $client->generateImage($prompt, [
                    'aspect_ratio' => '16:9',
                    'image_size'   => '1K',
                    'temperature'  => 0.35,
                ]);

                $path = $client->saveImage($subdir, $imageData);
                $destImages[$key] = $path;

                Log::info("Generated destination image for '{$name}' in trip {$trip->id}");
            } catch (\Throwable $e) {
                Log::warning("Failed to generate destination image for '{$name}' in trip {$trip->id}: " . $e->getMessage());
            }
        }

        // Save destination images mapping to the plan
        if (! empty($destImages)) {
            $plan['destination_images'] = $destImages;
            $trip->update(['plan' => $plan]);
        }
    }
}
