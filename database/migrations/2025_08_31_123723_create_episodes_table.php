<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('audio_url')->nullable();               // CDN or storage path
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 20)->default('draft');        // draft|published|archived
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
