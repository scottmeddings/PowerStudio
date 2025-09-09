<?php

// database/migrations/2025_09_09_000000_create_episode_transcripts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('episode_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->string('format', 16)->default('txt');   // txt|vtt|srt
            $table->longText('body')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->string('storage_path')->nullable();
            $table->timestamps();

            $table->unique('episode_id'); // hasOne
        });
    }
    public function down(): void {
        Schema::dropIfExists('episode_transcripts');
    }
};
