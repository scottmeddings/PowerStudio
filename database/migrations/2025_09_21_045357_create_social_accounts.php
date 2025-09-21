<?php

// database/migrations/2025_09_21_000001_create_social_accounts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('social_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider', 32)->index();              // 'linkedin', 'x', etc.
            $t->string('external_id', 190)->nullable();       // LinkedIn member id (urn:li:person:xxx) or short id
            $t->text('access_token')->nullable();             // encrypted
            $t->text('refresh_token')->nullable();            // encrypted
            $t->timestamp('expires_at')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->unique(['user_id','provider']);
        });
    }
    public function down(): void { Schema::dropIfExists('social_accounts'); }
};
