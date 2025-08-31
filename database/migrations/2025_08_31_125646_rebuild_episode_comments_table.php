<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Dev-only reset to match the seeder shape
        Schema::dropIfExists('episode_comments');

        Schema::create('episode_comments', function (Blueprint $table) {
            $table->id();

            // Seeder expects this:
            $table->boolean('approved')->default(false)->index();

            $table->string('author_name', 191)->nullable();
            $table->string('author_email', 191)->nullable();

            $table->text('body');

            $table->foreignId('episode_id')
                  ->constrained('episodes')
                  ->cascadeOnDelete();

            // Comments may be from a user or anonymous (keep nullable = safe)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index(['episode_id', 'created_at']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_comments');
    }
};
