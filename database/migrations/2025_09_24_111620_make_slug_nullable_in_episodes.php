<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            // ---- SLUG FIX ----
            if (Schema::hasColumn('episodes', 'slug')) {
                // Drop existing unique index if present
                $indexes = DB::select("SHOW INDEX FROM episodes WHERE Key_name = 'episodes_slug_unique'");
                if (!empty($indexes)) {
                    $table->dropUnique('episodes_slug_unique');
                }

                // Make slug nullable and re-add unique index
                $table->string('slug', 191)->nullable()->change();
                $table->unique('slug', 'episodes_slug_unique');
            }
        });

        // ---- UUID FIX ----
        if (Schema::hasColumn('episodes', 'uuid')) {
            // Backfill missing UUIDs
            DB::table('episodes')
                ->whereNull('uuid')
                ->update(['uuid' => DB::raw('(UUID())')]);

            // Force default UUID for future inserts
            DB::statement("ALTER TABLE episodes MODIFY uuid CHAR(36) NOT NULL DEFAULT (UUID())");
        }
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            // ---- SLUG ROLLBACK ----
            if (Schema::hasColumn('episodes', 'slug')) {
                $indexes = DB::select("SHOW INDEX FROM episodes WHERE Key_name = 'episodes_slug_unique'");
                if (!empty($indexes)) {
                    $table->dropUnique('episodes_slug_unique');
                }

                // Restore slug as NOT NULL + unique
                $table->string('slug', 191)->nullable(false)->change();
                $table->unique('slug', 'episodes_slug_unique');
            }

            // ---- UUID ROLLBACK ----
            if (Schema::hasColumn('episodes', 'uuid')) {
                // Remove default (UUID()) and make it nullable again
                DB::statement("ALTER TABLE episodes MODIFY uuid CHAR(36) NULL");
            }
        });
    }
};
