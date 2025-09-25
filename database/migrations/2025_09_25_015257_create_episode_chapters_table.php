<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('episode_chapters', function (Blueprint $table) {
            $table->id();

            // Foreign key to episodes
            $table->foreignId('episode_id')
                ->constrained('episodes')
                ->cascadeOnDelete();

            // Fields in your fillable
            $table->unsignedInteger('sort')->default(0);
            $table->string('title');
            $table->unsignedBigInteger('starts_at_ms')->default(0);

            $table->timestamps();

            // Helpful index for faster lookups & ordering
            $table->index(['episode_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_chapters');
    }
};
