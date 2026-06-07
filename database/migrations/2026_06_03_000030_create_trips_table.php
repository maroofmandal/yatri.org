<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('share_token', 16)->unique();
            $table->string('title');

            // Origin
            $table->string('origin');
            $table->decimal('origin_lat', 10, 7)->nullable();
            $table->decimal('origin_lng', 10, 7)->nullable();

            // Stops: [{name, lat, lng, nights}]
            $table->json('destinations');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('days')->default(7);
            $table->unsignedSmallInteger('travelers')->default(1);

            // Budget
            $table->decimal('budget_total', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('style')->default('mid'); // budget|mid|luxury
            $table->json('interests')->nullable();

            // Generation output
            $table->string('status')->default('draft'); // draft|generating|ready|failed
            $table->json('plan')->nullable();
            $table->json('budget_breakdown')->nullable();
            $table->string('fit_status')->nullable(); // fit|over|under
            $table->json('grounding')->nullable();     // citations / sources
            $table->string('model_used')->nullable();
            $table->text('error')->nullable();

            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
