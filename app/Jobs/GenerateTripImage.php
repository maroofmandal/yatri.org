<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Services\ImageGen\TripImageGenerator;
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

    public function handle(TripImageGenerator $generator): void
    {
        $generator->force($this->force)->generateForTrip($this->trip);
    }

    public function failed(\Throwable $e = null): void
    {
        Log::error("GenerateTripImage job failed for trip {$this->trip->id}: " . ($e?->getMessage() ?? 'unknown'));
    }
}
