<?php

// database/migrations/2025_09_08_000001_alter_episode_chapters_add_default.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite note: changing default may require a table rebuild; this works on MySQL/Postgres.
        Schema::table('episode_chapters', function (Blueprint $table) {
            $table->integer('starts_at_ms')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('episode_chapters', function (Blueprint $table) {
            $table->integer('starts_at_ms')->default(null)->change();
        });
    }
};
