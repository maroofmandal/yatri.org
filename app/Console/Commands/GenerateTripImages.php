<?php

namespace App\Console\Commands;

use App\Jobs\GenerateTripImage;
use App\Models\Trip;
use Illuminate\Console\Command;

class GenerateTripImages extends Command
{
    protected $signature = 'trips:generate-images
        {--force : Regenerate images even if trip already has one}
        {--limit=10 : Max trips to process}';

    protected $description = 'Backfill trip images using Nano Banana';

    public function handle(): int
    {
        $query = Trip::where('status', 'ready');

        if (! $this->option('force')) {
            $query->whereNull('image');
        }

        $count = 0;
        $limit = (int) $this->option('limit');

        $query->chunk(5, function ($trips) use (&$count, $limit) {
            foreach ($trips as $trip) {
                if ($count >= $limit) {
                    return false;
                }
                GenerateTripImage::dispatch($trip);
                $count++;
                $this->info("Dispatched image gen for trip #{$trip->id}: {$trip->title}");
            }
        });

        $this->info("Done. Dispatched {$count} image generation job(s).");

        return Command::SUCCESS;
    }
}
