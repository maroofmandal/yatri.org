<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reviewable_type'); // destination, hotel, flight
            $table->unsignedBigInteger('reviewable_id');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['reviewable_type', 'reviewable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};