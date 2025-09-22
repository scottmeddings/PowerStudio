<?php

// database/migrations/2025_09_22_000000_create_site_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index(); // optional scoping
            $table->string('key')->index();
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id','key']); // allow per-user settings; if you want global only, use unique('key')
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
