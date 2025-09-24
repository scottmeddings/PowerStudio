<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->string('source', 50)->nullable();              // e.g. web, apple, spotify
            $table->string('country', 2)->nullable();              // ISO-3166 alpha-2
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();                                  // created_at used for time-series
            $table->index(['episode_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
