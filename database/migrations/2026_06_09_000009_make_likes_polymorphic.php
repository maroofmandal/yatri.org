<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('likes');
            Schema::create('likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('likeable_type');
                $table->unsignedBigInteger('likeable_id');
                $table->timestamps();
                $table->index(['likeable_type', 'likeable_id']);
                $table->unique(['user_id', 'likeable_type', 'likeable_id']);
            });
            return;
        }

        $hasLikeableType = Schema::hasColumn('likes', 'likeable_type');

        if (!$hasLikeableType) {
            Schema::table('likes', function (Blueprint $table) {
                $table->string('likeable_type')->nullable()->after('user_id');
                $table->unsignedBigInteger('likeable_id')->nullable()->after('likeable_type');
                $table->index(['likeable_type', 'likeable_id']);
            });
        }

        DB::table('likes')
            ->whereNotNull('trip_id')
            ->update([
                'likeable_type' => 'App\\Models\\Trip',
                'likeable_id' => DB::raw('trip_id'),
            ]);

        // Drop the unique constraint if it exists
        if (Schema::hasIndex('likes', ['user_id', 'trip_id'], true)) {
            Schema::table('likes', function (Blueprint $table) {
                $table->dropIndex('likes_user_id_trip_id_unique');
            });
        }

        $hasTripId = Schema::hasColumn('likes', 'trip_id');
        if ($hasTripId) {
            Schema::table('likes', function (Blueprint $table) {
                $table->dropForeign(['trip_id']);
                $table->dropColumn('trip_id');
            });
        }
    }

    public function down(): void
    {
        $hasTripId = Schema::hasColumn('likes', 'trip_id');
        if (!$hasTripId) {
            Schema::table('likes', function (Blueprint $table) {
                $table->foreignId('trip_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            });
        }

        DB::table('likes')
            ->where('likeable_type', 'App\\Models\\Trip')
            ->update([
                'trip_id' => DB::raw('likeable_id'),
            ]);

        $hasLikeableType = Schema::hasColumn('likes', 'likeable_type');
        if ($hasLikeableType) {
            Schema::table('likes', function (Blueprint $table) {
                $table->dropIndex(['likeable_type', 'likeable_id']);
                $table->dropColumn(['likeable_type', 'likeable_id']);
            });
        }
    }
};
