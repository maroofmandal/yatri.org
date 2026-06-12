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

    public int $timeout = 180;

    public int $tries = 2;

    protected Trip $trip;

    public function __construct(Trip $trip)
    {
        $this->trip = $trip->withoutRelations();
    }

    public function handle(ImageGenClient $client): void
    {
        if (! $client->enabled()) {
            Log::info("Skipping image gen for trip {$this->trip->id}: no Nano Banana keys configured.");
            return;
        }

        $trip  = $this->trip;
        $plan  = $trip->plan ?? [];
        $dests = collect($trip->destinations ?? []);

        // Build a rich prompt from the trip plan
        $destNames  = $dests->pluck('name')->take(3)->implode(', ');
        $origin     = $trip->origin;
        $style      = $trip->style ?? 'mid';
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

        // Build the OG image prompt (16:9, 1200x630)
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
            $path   = $client->saveImage($subdir, $imageData);

            $trip->forceFill(['image' => $path])->save();

            Log::info("Generated OG image for trip {$trip->id} using model {$imageData['model']}");
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
                $path   = $client->saveImage($subdir, $imageData);
                $trip->forceFill(['image' => $path])->save();
                Log::info("Generated fallback image for trip {$trip->id}");
            } catch (\Throwable $e2) {
                Log::error("Failed to generate image for trip {$trip->id}: " . $e2->getMessage());
                $this->fail($e2);
            }
        }
    }
}
