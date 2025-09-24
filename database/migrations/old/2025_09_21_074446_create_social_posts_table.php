<?php

// database/migrations/2025_09_21_000002_create_social_posts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('social_posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title')->nullable();
            $t->text('body');
            $t->string('episode_url')->nullable();
            $t->string('visibility', 24)->default('public');
            $t->json('services');               // ['x','linkedin',...]
            $t->string('status', 24)->default('queued'); // queued|sent|failed
            $t->json('assets')->nullable();     // {images:[...], video:{...}}
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('social_posts'); }
};
