<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_ai_progress_to_episodes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('ai_status')->nullable();          // queued | downloading | downsampling | transcribing | summarizing | saving | done | failed
            $table->unsignedTinyInteger('ai_progress')->default(0); // 0..100
            $table->string('ai_message')->nullable();         // human label
        });
    }
    public function down(): void {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn(['ai_status','ai_progress','ai_message']);
        });
    }
};

