<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            // Add the column (nullable first to avoid issues on existing rows),
            // then add the FK. SQLite will emulate this under the hood.
            $table->foreignIdFor(\App\Models\User::class)
                  ->nullable()
                  ->after('id');

            // If you want to enforce FK (SQLite supports it when recreating table):
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            // Drop FK then column (SQLite may ignore dropForeign silently)
            // $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
