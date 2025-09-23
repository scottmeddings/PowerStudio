<?php
// database/migrations/2025_09_20_000003_harden_settings_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('settings')) return;

        $driver = Schema::getConnection()->getDriverName();

        // Helpers ------------------------------------------------------------
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
                     WHERE schemaname = ANY (current_schemas(false))
                       AND tablename = 'settings' AND indexname = ?", [$name]
                ),
                default  => false,
            };
        };

        $dropIndexIfExists = function (string $indexName) use ($driver, $hasIndex): void {
            if (! $hasIndex($indexName)) return;

            switch ($driver) {
                case 'sqlite':
                    // Valid in SQLite
                    DB::statement("DROP INDEX IF EXISTS {$indexName}");
                    break;

                case 'mysql':
                    // MySQL requires table-qualified drop; IF EXISTS not widely supported
                    DB::statement("ALTER TABLE settings DROP INDEX {$indexName}");
                    break;

                case 'pgsql':
                    // IF EXISTS supported; index may need schema-qualifying but current_schemas covers it
                    DB::statement("DROP INDEX IF EXISTS {$indexName}");
                    break;
            }
        };
        // -------------------------------------------------------------------

        // 1) Ensure user_id exists & is indexed (nullable for backfill safety)
        if (! Schema::hasColumn('settings', 'user_id')) {
            Schema::table('settings', function (Blueprint $t) {
                // Defaults to users.id; nullable to avoid FK failures on legacy rows
                $t->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $t->index('user_id');
            });
        }

        // 2) Drop legacy single-column uniques (idempotent & driver-safe)
        // Common legacy names created by Laravel:
        //   - settings_key_unique
        //   - settings_singleton_unique
        foreach (['settings_key_unique', 'settings_singleton_unique'] as $legacy) {
            try {
                Schema::table('settings', fn (Blueprint $t) => $t->dropUnique($legacy));
            } catch (\Throwable $e) {
                // Fallback to driver-specific raw drop
                $dropIndexIfExists($legacy);
            }
        }

        // 3) Add composite uniques if missing
        if (! $hasIndex('settings_user_key_unique')) {
            Schema::table('settings', function (Blueprint $t) {
                $t->unique(['user_id', 'key'], 'settings_user_key_unique');
            });
        }

        if (! $hasIndex('settings_user_singleton_unique')) {
            Schema::table('settings', function (Blueprint $t) {
                $t->unique(['user_id', 'singleton'], 'settings_user_singleton_unique');
            });
        }

        // 4) Normalize legacy singleton rows to avoid collisions on 'key'
        if (Schema::hasColumn('settings', 'singleton') && Schema::hasColumn('settings', 'key')) {
            DB::table('settings')->where('singleton', 1)->update(['key' => null]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) return;

        // Drop composite uniques if present (safe even if absent)
        foreach (['settings_user_key_unique', 'settings_user_singleton_unique'] as $idx) {
            try {
                Schema::table('settings', fn (Blueprint $t) => $t->dropUnique($idx));
            } catch (\Throwable $e) {}
        }

        // (Optional) Recreate legacy uniques if you truly need them back:
        // Schema::table('settings', fn (Blueprint $t) => $t->unique('key','settings_key_unique'));
        // Schema::table('settings', fn (Blueprint $t) => $t->unique('singleton','settings_singleton_unique'));
    }
};
