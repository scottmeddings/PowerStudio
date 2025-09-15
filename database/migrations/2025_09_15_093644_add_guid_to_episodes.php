<?php

// database/migrations/xxxx_xx_xx_add_guid_to_episodes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('episodes', function (Blueprint $table) {
            if (!Schema::hasColumn('episodes','guid')) {
                $table->string('guid', 512)->nullable()->unique();
            }
            if (!Schema::hasColumn('episodes','image_url')) {
                $table->string('image_url', 2048)->nullable();
            }
            if (!Schema::hasColumn('episodes','audio_url')) {
                $table->string('audio_url', 2048)->nullable();
            }
            if (!Schema::hasColumn('episodes','duration_sec')) {
                $table->integer('duration_sec')->nullable();
            }
            if (!Schema::hasColumn('episodes','season')) {
                $table->integer('season')->nullable();
            }
            if (!Schema::hasColumn('episodes','episode_no')) {
                $table->integer('episode_no')->nullable();
            }
            if (!Schema::hasColumn('episodes','explicit')) {
                $table->boolean('explicit')->default(false);
            }
            if (!Schema::hasColumn('episodes','status')) {
                $table->string('status', 32)->default('published');
            }
        });
    }
    public function down(): void { /* no-op for brevity */ }
};

