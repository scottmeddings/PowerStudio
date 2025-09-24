<?php

// database/migrations/2025_09_07_000000_add_ai_fields_to_episodes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (!Schema::hasColumn('episodes', 'chapters')) {
                $table->json('chapters')->nullable();
            }
            if (!Schema::hasColumn('episodes', 'transcript')) {
                $table->longText('transcript')->nullable();
            }
        });
    }
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (Schema::hasColumn('episodes', 'chapters')) {
                $table->dropColumn('chapters');
            }
            if (Schema::hasColumn('episodes', 'transcript')) {
                $table->dropColumn('transcript');
            }
        });
    }
};

