<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('episode_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\User::class)->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();             // for guest comments
            $table->string('author_email')->nullable();
            $table->text('body');
            $table->boolean('approved')->default(true);
            $table->timestamps();

            $table->index(['episode_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_comments');
    }
};
