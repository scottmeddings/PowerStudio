<?php
// database/migrations/2025_09_24_000004_add_chapters_url_to_episodes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('episodes') && ! Schema::hasColumn('episodes', 'chapters_url')) {
            Schema::table('episodes', function (Blueprint $table) {
                $table->string('chapters_url')->nullable()->after('cover_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('episodes') && Schema::hasColumn('episodes', 'chapters_url')) {
            Schema::table('episodes', function (Blueprint $table) {
                $table->dropColumn('chapters_url');
            });
        }
    }
};
