<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Add column only if it doesn't exist
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('user')->index()->after('email');
            });
        }

        // 2) Ensure values are not NULL (some DBs might allow nulls from old schema)
        DB::table('users')->whereNull('role')->update(['role' => 'user']);

        // 3) Ensure an index exists (optional safety; SQLite names are loose, so use try/catch)
        try {
            Schema::table('users', function (Blueprint $table) {
                // Will be ignored if an index with this name/col already exists
                $table->index('role', 'users_role_index');
            });
        } catch (\Throwable $e) {
            // ignore if index already exists
        }
    }

    public function down(): void
    {
        // Only drop if present (SQLite can’t drop indexes by column, so use name)
        if (Schema::hasColumn('users', 'role')) {
            // Best-effort remove index if it exists
            try { Schema::table('users', fn (Blueprint $t) => $t->dropIndex('users_role_index')); } catch (\Throwable $e) {}
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
        }
    }
};
