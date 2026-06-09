<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('total_days_traveled')->default(0);
            $table->decimal('total_kilometers', 10, 2)->default(0);
            $table->unsignedInteger('total_likes_received')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_days_traveled', 'total_kilometers', 'total_likes_received']);
        });
    }
};