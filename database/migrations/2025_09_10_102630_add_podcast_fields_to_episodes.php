<?php

// database/migrations/2025_09_10_000000_add_podcast_fields_to_episodes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('episodes', function (Blueprint $table) {
            
            $table->string('audio_path')->nullable();
            $table->unsignedBigInteger('audio_bytes')->nullable();
            $table->unsignedInteger('episode_number')->nullable();
            $table->string('episode_type')->default('full'); // full|trailer|bonus
            $table->boolean('explicit')->default(false);
            $table->string('image_url')->nullable();
            $table->uuid('uuid')->nullable()->unique();
        });
    }
    public function down(): void {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn([
                'audio_url','audio_path','audio_bytes','duration_seconds',
                'episode_number','episode_type','explicit','image_url',
                'published_at','uuid'
            ]);
        });
    }
};

