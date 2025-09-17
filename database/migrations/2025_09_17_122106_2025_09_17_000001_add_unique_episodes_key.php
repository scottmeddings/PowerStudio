<?php

// database/migrations/2025_09_17_000001_add_unique_episodes_key.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('episodes', function (Blueprint $table) {
            $table->unique(['user_id','title','published_at'], 'episodes_user_title_pub_unique');
        });
    }
    public function down(): void {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropUnique('episodes_user_title_pub_unique');
        });
    }
};
