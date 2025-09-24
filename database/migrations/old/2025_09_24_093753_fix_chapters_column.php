<?php
// database/migrations/2025_09_24_000005_fix_chapters_column.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('episodes')) {
            return;
        }

        // 1) Add chapters_url column if missing
        if (! Schema::hasColumn('episodes', 'chapters_url')) {
            Schema::table('episodes', function (Blueprint $table) {
                $table->string('chapters_url')->nullable()->after('cover_path');
            });
        }

        // 2) Make sure chapters column is relaxed to TEXT (remove JSON constraint)
        // MySQL does not allow easy "drop check constraint" via Schema, so we use raw SQL.
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'mysql') {
            try {
                // Drop CHECK constraint if it exists
                DB::statement("ALTER TABLE episodes MODIFY chapters JSON NULL");
            } catch (\Throwable $e) {
                // If chapters was JSON with constraint, force fallback to TEXT
                DB::statement("ALTER TABLE episodes MODIFY chapters TEXT NULL");
            }
        } elseif ($connection === 'pgsql') {
            // PostgreSQL case: cast to text
            DB::statement("ALTER TABLE episodes ALTER COLUMN chapters TYPE TEXT USING chapters::text");
        } elseif ($connection === 'sqlite') {
            // SQLite has no enforced JSON type, so nothing to do
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('episodes')) {
            return;
        }

        Schema::table('episodes', function (Blueprint $table) {
            if (Schema::hasColumn('episodes', 'chapters_url')) {
                $table->dropColumn('chapters_url');
            }
        });

        // Optionally revert chapters to JSON if supported
        $connection = Schema::getConnection()->getDriverName();
        if ($connection === 'mysql') {
            try {
                DB::statement("ALTER TABLE episodes MODIFY chapters JSON NULL");
            } catch (\Throwable $e) {
                // ignore if fails
            }
        }
    }
};
