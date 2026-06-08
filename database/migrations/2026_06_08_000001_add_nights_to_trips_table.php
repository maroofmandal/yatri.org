<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'nights')) {
                $table->unsignedSmallInteger('nights')->default(0)->after('days');
            }
        });

        // Backfill nights = max(1, days - 1) for existing trips
        $trips = DB::table('trips')->where('nights', 0)->get();
        foreach ($trips as $trip) {
            DB::table('trips')->where('id', $trip->id)->update(['nights' => max(1, $trip->days - 1)]);
        }
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('nights');
        });
    }
};
