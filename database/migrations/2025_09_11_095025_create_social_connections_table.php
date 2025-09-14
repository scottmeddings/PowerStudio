<?php

// database/migrations/2025_09_11_000001_create_social_connections_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('social_connections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider');                  // facebook|linkedin|youtube|tumblr|wordpress
            $t->string('access_token', 2048)->nullable();
            $t->string('refresh_token', 2048)->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->string('page_id')->nullable();      // page/channel handle if needed
            $t->string('channel_id')->nullable();
            $t->json('settings')->nullable();       // misc provider data
            $t->boolean('is_connected')->default(false);
            $t->timestamps();
            $t->unique(['user_id','provider']);
        });
    }
    public function down(): void { Schema::dropIfExists('social_connections'); }
};
