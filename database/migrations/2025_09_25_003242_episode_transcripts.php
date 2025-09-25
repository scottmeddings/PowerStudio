<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('episode_transcripts')) {
            Schema::create('episode_transcripts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('episode_id')
                      ->constrained('episodes')
                      ->onDelete('cascade');

                $table->string('format', 50)->nullable();      // e.g. plain_text, srt, vtt
                $table->longText('body')->nullable();          // transcript text
                $table->unsignedBigInteger('duration_ms')->nullable(); // duration in ms
                $table->string('storage_path')->nullable();    // where file is stored

                $table->timestamps();
            });
        } else {
            Schema::table('episode_transcripts', function (Blueprint $table) {
                if (!Schema::hasColumn('episode_transcripts', 'episode_id')) {
                    $table->foreignId('episode_id')
                          ->constrained('episodes')
                          ->onDelete('cascade');
                }
                if (!Schema::hasColumn('episode_transcripts', 'format')) {
                    $table->string('format', 50)->nullable();
                }
                if (!Schema::hasColumn('episode_transcripts', 'body')) {
                    $table->longText('body')->nullable();
                }
                if (!Schema::hasColumn('episode_transcripts', 'duration_ms')) {
                    $table->unsignedBigInteger('duration_ms')->nullable();
                }
                if (!Schema::hasColumn('episode_transcripts', 'storage_path')) {
                    $table->string('storage_path')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_transcripts');
    }
};
