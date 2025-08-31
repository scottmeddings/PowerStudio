<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Rename old table
        if (Schema::hasTable('episodes')) {
            Schema::rename('episodes', 'episodes_old');
        }

        // Create correct schema
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('audio_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->timestamps();
            $table->index(['user_id','created_at']);
        });

        // Try to copy what we can from old table if columns exist
        if (Schema::hasTable('episodes_old')) {
            // Adjust the SELECT list to whatever columns existed in your old table
            DB::statement("
                INSERT INTO episodes (id, created_at, updated_at)
                SELECT id, created_at, updated_at FROM episodes_old
            ");
            Schema::drop('episodes_old');
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
        if (Schema::hasTable('episodes_old')) {
            Schema::rename('episodes_old', 'episodes');
        }
    }
};
