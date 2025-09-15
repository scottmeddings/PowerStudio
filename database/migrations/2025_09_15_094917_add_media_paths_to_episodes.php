<?php

// database/migrations/2025_09_15_000000_add_media_paths_to_episodes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('episodes', function (Blueprint $table) {
            if (!Schema::hasColumn('episodes','guid'))       $table->string('guid', 512)->nullable()->unique();
            if (!Schema::hasColumn('episodes','audio_url'))  $table->string('audio_url', 2048)->nullable();
            if (!Schema::hasColumn('episodes','audio_path')) $table->string('audio_path', 1024)->nullable(); // storage/public relative
            if (!Schema::hasColumn('episodes','image_url'))  $table->string('image_url', 2048)->nullable();
            if (!Schema::hasColumn('episodes','image_path')) $table->string('image_path', 1024)->nullable();
            if (!Schema::hasColumn('episodes','published_at')) $table->dateTime('published_at')->nullable();
            if (!Schema::hasColumn('episodes','status'))     $table->string('status', 32)->default('published');
            if (!Schema::hasColumn('episodes','slug'))       $table->string('slug', 255)->nullable()->index();
        });
    }
    public function down(): void { /* keep columns */ }
};

