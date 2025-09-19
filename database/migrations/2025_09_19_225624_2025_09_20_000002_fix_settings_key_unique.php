<?php

// database/migrations/2025_09_20_000003_harden_settings_indexes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        $hasIndex = function (string $name) use ($driver): bool {
            return match ($driver) {
                'sqlite' => (bool) DB::selectOne(
                    "SELECT name FROM sqlite_master WHERE type='index' AND name = ?", [$name]
                ),
                'mysql'  => (bool) DB::selectOne(
                    "SELECT INDEX_NAME name FROM information_schema.statistics
                     WHERE table_schema = DATABASE() AND table_name = 'settings' AND index_name = ?", [$name]
                ),
                'pgsql'  => (bool) DB::selectOne(
                    "SELECT indexname name FROM pg_indexes
                     WHERE schemaname = ANY (current_schemas(false)) AND tablename = 'settings' AND indexname = ?", [$name]
                ),
                default  => false,
            };
        };

        // 1) Ensure user_id exists & indexed
        if (!Schema::hasColumn('settings','user_id')) {
            Schema::table('settings', function (Blueprint $t) {
                $t->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $t->index('user_id');
            });
        }

        // 2) Drop any old global uniques (best-effort)
        try { Schema::table('settings', fn(Blueprint $t) => $t->dropUnique('settings_key_unique')); } catch (\Throwable $e) {
            DB::statement('DROP INDEX IF EXISTS settings_key_unique'); // sqlite
        }
        try { Schema::table('settings', fn(Blueprint $t) => $t->dropUnique('settings_singleton_unique')); } catch (\Throwable $e) {
            DB::statement('DROP INDEX IF EXISTS settings_singleton_unique'); // sqlite
        }

        // 3) Create the composite uniques if they DON'T already exist
        if (!$hasIndex('settings_user_key_unique')) {
            Schema::table('settings', function (Blueprint $t) {
                $t->unique(['user_id','key'], 'settings_user_key_unique');
            });
        }

        if (!$hasIndex('settings_user_singleton_unique')) {
            Schema::table('settings', function (Blueprint $t) {
                $t->unique(['user_id','singleton'], 'settings_user_singleton_unique');
            });
        }

        // 4) Make sure all singleton rows have NULL key (avoids UNIQUE(key) collisions on old schemas)
        DB::table('settings')->where('singleton', 1)->update(['key' => null]);
    }

    public function down(): void
    {
        // Safe rollback (skip if not present)
        try { Schema::table('settings', fn(Blueprint $t) => $t->dropUnique('settings_user_key_unique')); } catch (\Throwable $e) {}
        try { Schema::table('settings', fn(Blueprint $t) => $t->dropUnique('settings_user_singleton_unique')); } catch (\Throwable $e) {}
        // Recreate legacy single-column uniques if you need them back:
        // Schema::table('settings', fn(Blueprint $t) => $t->unique('key','settings_key_unique'));
        // Schema::table('settings', fn(Blueprint $t) => $t->unique('singleton','settings_singleton_unique'));
    }
};
