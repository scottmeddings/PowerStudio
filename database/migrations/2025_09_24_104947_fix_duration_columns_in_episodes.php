<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            // Add duration_seconds if missing
            if (!Schema::hasColumn('episodes', 'duration_seconds')) {
                $table->integer('duration_seconds')->nullable()->after('audio_path');
            }

            // Add duration_sec if missing
            if (!Schema::hasColumn('episodes', 'duration_sec')) {
                $table->integer('duration_sec')->nullable()->after('duration_seconds');
            }

            // Add episode_no if missing (query is also selecting it)
            if (!Schema::hasColumn('episodes', 'episode_no')) {
                $table->integer('episode_no')->nullable()->after('episode_number');
            }

            // Add transcript if missing (query is selecting it too)
            if (!Schema::hasColumn('episodes', 'transcript')) {
                $table->longText('transcript')->nullable()->after('chapters');
            }
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (Schema::hasColumn('episodes', 'duration_seconds')) {
                $table->dropColumn('duration_seconds');
            }
            if (Schema::hasColumn('episodes', 'duration_sec')) {
                $table->dropColumn('duration_sec');
            }
            if (Schema::hasColumn('episodes', 'episode_no')) {
                $table->dropColumn('episode_no');
            }
            if (Schema::hasColumn('episodes', 'transcript')) {
                $table->dropColumn('transcript');
            }
        });
    }
};
