<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'shares')) {
                $table->unsignedInteger('shares')->default(0)->after('views');
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'views')) {
                $table->unsignedInteger('views')->default(0)->after('status');
            }
            if (!Schema::hasColumn('posts', 'shares')) {
                $table->unsignedInteger('shares')->default(0)->after('views');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['views', 'shares']);
        });
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('shares');
        });
    }
};
