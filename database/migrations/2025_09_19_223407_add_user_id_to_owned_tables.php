<?php

// database/migrations/2025_09_19_000000_add_user_id_to_owned_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // List every business table that should be per-user
    private array $tables = [
        'episodes','episode_comments','achievement_unlocks','comments','downloads',
        'episode_chapters','episode_transcripts','podcast_apps','podcast_directories',
        'settings','social_connections','revenue_daily','payouts',
        'dynamic_ad_settings','sponsorship_offers','house_campaigns','promos','collaborators',
        // add any others you have
    ];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            if (!Schema::hasTable($t)) continue;
            if (Schema::hasColumn($t, 'user_id')) continue;

            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable() // keep nullable so this migration succeeds on existing rows
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            if (!Schema::hasTable($t) || !Schema::hasColumn($t, 'user_id')) continue;
            Schema::table($t, function (Blueprint $table) {
                // drop FK + column safely
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
