<?php
// database/migrations/2025_09_20_000003_harden_settings_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        // Helpers ------------------------------------------------------------
        $hasIndex = function (string $name) use ($driver): bool {
            return match ($driver) {
                'sqlite' => (bool) DB::selectOne(
                    "SELECT name FROM sqlite_master WHERE type='index' AND name = ?", [$name]
                ),
                'mysql'  => (bool) DB::selectOne(
                    "SELECT INDEX_NAME name FROM information_schema.statistics
                     WHERE table_schema = DATABASE() 
                       AND table_name = 'settings' 
                       AND index_name = ?", [$name]
                ),
                'pgsql'  => (bool) DB::selectOne(
                    "SELECT indexname name FROM pg_indexes
                     WHERE schemaname = ANY (current_schemas(false))
                       AND tablename = 'settings' 
                       AND indexname = ?", [$name]
                ),
                default  => false,
            };
        };

        $dropIndexIfExists = function (string $indexName) use ($driver, $hasIndex): void {
            if (! $hasIndex($indexName)) return;

            match ($driver) {
                'sqlite' => DB::statement("DROP INDEX IF EXISTS {$indexName}"),
                'mysql'  => DB::statement("ALTER TABLE settings DROP INDEX {$indexName}"),
                'pgsql'  => DB::statement("DROP INDEX IF EXISTS {$indexName}"),
                default  => null,
            };
        };
        // -------------------------------------------------------------------

        // 1) Ensure user_id exists & is indexed
        if (! Schema::hasColumn('settings', 'user_id')) {
            Schema::table('settings', function (Blueprint $t) {
                $t->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
                $t->index('user_id');
            });
        }

        // 2) Drop legacy single-column uniques
        foreach (['settings_key_unique', 'settings_singleton_unique'] as $legacy) {
            try {
                Schema::table('settings', fn (Blueprint $t) => $t->dropUnique($legacy));
            } catch (\Throwable $e) {
                $dropIndexIfExists($legacy);
            }
        }

        // 3) Add composite unique only on user_id + key
        if (! $hasIndex('settings_user_key_unique')) {
            Schema::table('settings', function (Blueprint $t) {
                $t->unique(['user_id', 'key'], 'settings_user_key_unique');
            });
        }

        // 4) Normalize legacy singleton rows (if both columns exist)
        if (Schema::hasColumn('settings', 'singleton') &&
            Schema::hasColumn('settings', 'key')) {
            DB::table('settings')->where('singleton', 1)->update(['key' => null]);
        }

        // 5) Drop the redundant user_id+singleton unique if it exists
        $dropIndexIfExists('settings_user_singleton_unique');
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        // Drop composite unique if present
        try {
            Schema::table('settings', fn (Blueprint $t) => $t->dropUnique('settings_user_key_unique'));
        } catch (\Throwable $e) {
            // ignore
        }

        // (Optional) Recreate legacy uniques if really needed:
        // Schema::table('settings', fn (Blueprint $t) => $t->unique('key','settings_key_unique'));
        // Schema::table('settings', fn (Blueprint $t) => $t->unique('singleton','settings_singleton_unique'));
    }
};
