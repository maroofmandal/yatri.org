<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if columns already exist
        $hasLikeableType = Schema::hasColumn('likes', 'likeable_type');
        
        if (!$hasLikeableType) {
            Schema::table('likes', function (Blueprint $table) {
                $table->string('likeable_type')->nullable()->after('user_id');
                $table->unsignedBigInteger('likeable_id')->nullable()->after('likeable_type');
                $table->index(['likeable_type', 'likeable_id']);
            });
        }

        // Migrate existing trip_id data to polymorphic format
        DB::table('likes')
            ->whereNotNull('trip_id')
            ->update([
                'likeable_type' => 'App\\Models\\Trip',
                'likeable_id' => DB::raw('trip_id'),
            ]);

        // Drop the unique constraint on user_id, trip_id
        $hasUnique = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='likes' AND name='likes_user_id_trip_id_unique'");
        if (!empty($hasUnique)) {
            Schema::table('likes', function (Blueprint $table) {
                $table->dropIndex('likes_user_id_trip_id_unique');
            });
        }

        // Drop foreign key and column
        $hasForeignKey = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND tbl_name='likes' AND sql LIKE '%foreign key%trip_id%'");
        if (!empty($hasForeignKey)) {
            Schema::table('likes', function (Blueprint $table) {
                $table->dropForeign(['trip_id']);
            });
        }
        
        $hasTripId = Schema::hasColumn('likes', 'trip_id');
        if ($hasTripId) {
            Schema::table('likes', function (Blueprint $table) {
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

        // Migrate polymorphic data back to trip_id
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