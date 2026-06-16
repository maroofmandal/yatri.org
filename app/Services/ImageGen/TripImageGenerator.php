<?php

namespace App\Services\ImageGen;

use App\Models\Trip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TripImageGenerator
{
    protected ImageGenClient $client;

    protected bool $force = false;

    public function __construct(ImageGenClient $client)
    {
        $this->client = $client;
    }

    public function force(bool $val = true): static
    {
        $this->force = $val;
        return $this;
    }

    public function generateForTrip(Trip $trip): void
    {
        if (! $this->client->enabled()) {
            Log::info("Skipping image gen for trip {$trip->id}: no API keys configured.");
            return;
        }

        $trip = $trip->refresh();
        $plan = $trip->plan ?? [];
        $dests = collect($trip->destinations ?? []);

        if ($this->force || empty($trip->image)) {
            $this->generateHeroImage($trip, $plan, $dests);
        }

        $this->generateDestinationImages($trip, $plan, $dests);
    }

    protected function generateHeroImage(Trip $trip, array $plan, Collection $dests): void
    {
        $origin = $trip->origin;
        $styleLabel = match ($trip->style ?? 'mid') {
            'budget' => 'budget-friendly backpacking',
            'luxury' => 'luxury high-end',
            default  => 'mid-range travel',
        };
        $destNames = $dests->pluck('name')->take(3)->implode(', ');
        $destList = $destNames ?: ($dests->first()['name'] ?? 'a beautiful destination');

        $highlight = '';
        foreach (($plan['days'] ?? []) as $day) {
            foreach (($day['items'] ?? []) as $item) {
                if (! empty($item['description'])) {
                    $highlight = $item['description'];
                    break 2;
                }
            }
        }

        $ogPrompt = "Photorealistic travel photography of {$destList}. {$styleLabel} trip from {$origin}. "
            . "Group of diverse travelers joyfully exploring, admiring the view, natural candid expressions, "
            . "golden hour warm sunlight, professional Canon EOS R5 photography, cinematic composition, "
            . "vibrant colors, 8K ultra detailed, National Geographic style, travel magazine quality, "
            . "clear sky, depth of field.";
        if ($highlight) {
            $ogPrompt .= " Scene: {$highlight}.";
        }

        try {
            $imageData = $this->client->generateImage($ogPrompt, [
                'aspect_ratio' => '16:9',
                'image_size'   => '1K',
                'temperature'  => 0.35,
                'trip_id'      => $trip->id,
            ]);
            $subdir = 'trip-images/' . $trip->share_token;
            $path = $this->client->saveImage($subdir, $imageData);
            $trip->forceFill(['image' => $path])->save();
            Log::info("Generated hero image for trip {$trip->id} using model {$imageData['model']}");
        } catch (\Throwable $e) {
            try {
                $fallbackPrompt = "Award-winning travel destination photography of {$destList}. "
                    . "Stunning landscape, golden hour lighting, professional photography, "
                    . "cinematic composition, vibrant colors, 8K ultra detailed, clear sky, depth of field.";
                $imageData = $this->client->generateImage($fallbackPrompt, [
                    'aspect_ratio' => '16:9',
                    'image_size'   => '1K',
                    'temperature'  => 0.35,
                    'trip_id'      => $trip->id,
                ]);
                $subdir = 'trip-images/' . $trip->share_token;
                $path = $this->client->saveImage($subdir, $imageData);
                $trip->forceFill(['image' => $path])->save();
                Log::info("Generated fallback hero image for trip {$trip->id}");
            } catch (\Throwable $e2) {
                Log::error("Failed to generate hero image for trip {$trip->id}: " . $e2->getMessage());
            }
        }
    }

    protected function generateDestinationImages(Trip $trip, array $plan, Collection $dests): void
    {
        $existingDests = collect($plan['route'] ?? $dests);
        $subdir = 'trip-images/' . $trip->share_token . '/destinations';
        $destImages = $plan['destination_images'] ?? [];

        foreach ($existingDests as $dest) {
            $name = $dest['name'] ?? '';
            if (empty($name)) {
                continue;
            }
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
                $imageData = $this->client->generateImage($prompt, [
                    'aspect_ratio' => '16:9',
                    'image_size'   => '1K',
                    'temperature'  => 0.35,
                    'trip_id'      => $trip->id,
                ]);
                $path = $this->client->saveImage($subdir, $imageData);
                $destImages[$key] = $path;
                Log::info("Generated destination image for '{$name}' in trip {$trip->id}");
            } catch (\Throwable $e) {
                Log::warning("Failed to generate destination image for '{$name}' in trip {$trip->id}: " . $e->getMessage());
            }
        }

        if (! empty($destImages)) {
            $plan['destination_images'] = $destImages;
            $trip->update(['plan' => $plan]);
        }
    }
}
