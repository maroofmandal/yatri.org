<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Services\ImageGen\TripImageGenerator;
use Illuminate\Console\Command;

class GenerateTripImages extends Command
{
    protected $signature = 'trips:generate-images
        {--force : Regenerate images even if trip already has one}
        {--limit=10 : Max trips to process}';

    protected $description = 'Backfill trip images using Nano Banana';

    public function handle(TripImageGenerator $generator): int
    {
        $query = Trip::where('status', 'ready');

        if (! $this->option('force')) {
            $query->whereNull('image');
        }

        $count = 0;
        $limit = (int) $this->option('limit');

        $query->chunk(5, function ($trips) use ($generator, &$count, $limit) {
            foreach ($trips as $trip) {
                if ($count >= $limit) {
                    return false;
                }
                $generator->force($this->option('force'))->generateForTrip($trip);
                $count++;
                $this->info("Generated images for trip #{$trip->id}: {$trip->title}");
            }
        });

        $this->info("Done. Generated images for {$count} trip(s).");

        return Command::SUCCESS;
    }
}
