<?php

// database/migrations/2025_09_20_000001_fix_settings_unique_indexes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Ensure user_id exists first (skip if you already added it)
        if (!Schema::hasColumn('settings','user_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $table->index('user_id');
            });
        }

        // --- Drop the global unique on `singleton`
        // MySQL/Postgres
        try {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropUnique('settings_singleton_unique'); // typical Laravel name
            });
        } catch (\Throwable $e) {
            // SQLite or different name: try raw drop (no-op if it doesn't exist)
            DB::statement('DROP INDEX IF EXISTS settings_singleton_unique');
        }

        // --- Add per-user uniques
        Schema::table('settings', function (Blueprint $table) {
            // one singleton row per user
            $table->unique(['user_id','singleton'], 'settings_user_singleton_unique');
            // one legacy key/value row per user per key
            $table->unique(['user_id','key'], 'settings_user_key_unique');
        });

        // Optional backfill: attach the existing (global) singleton row to the first admin/user
        if (Schema::hasColumn('settings','user_id')) {
            $ownerId = DB::table('users')->where('role','admin')->value('id')
                    ?? DB::table('users')->orderBy('id')->value('id');
            if ($ownerId) {
                DB::table('settings')
                    ->where('singleton', 1)
                    ->whereNull('user_id')
                    ->update(['user_id' => $ownerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_user_singleton_unique');
            $table->dropUnique('settings_user_key_unique');
            $table->unique('singleton', 'settings_singleton_unique');
        });
    }
};
