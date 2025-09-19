<?php

// database/migrations/2025_09_20_000000_add_user_id_to_settings.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('settings','user_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $table->index(['user_id']);
            });

            // OPTIONAL backfill: attach existing global rows to the first admin or your account
            $ownerId = DB::table('users')->where('role','admin')->value('id')
                      ?? DB::table('users')->orderBy('id')->value('id');
            if ($ownerId) {
                DB::table('settings')->whereNull('user_id')->update(['user_id' => $ownerId]);
            }

            // Now make it required and unique per user+key
            Schema::table('settings', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable(false)->change();
                $table->unique(['user_id','key']);
            });
        }
    }

    public function down(): void {
        if (Schema::hasColumn('settings','user_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropUnique(['user_id','key']);
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
