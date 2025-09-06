<?php

// database/migrations/2025_09_04_000002_create_episode_transcripts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('episode_transcripts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $t->enum('format', ['vtt','srt','txt'])->default('vtt');
            $t->longText('body')->nullable();            // raw text (optional)
            $t->string('storage_path')->nullable();      // /public/transcripts/...
            $t->unsignedInteger('duration_ms')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('episode_transcripts'); }
};
