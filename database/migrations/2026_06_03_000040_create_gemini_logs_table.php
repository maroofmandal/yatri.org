<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gemini_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind')->default('plan');  // research|plan|chat
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->boolean('grounded')->default(false);
            $table->string('status')->default('ok'); // ok|error
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('kind');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gemini_logs');
    }
};
