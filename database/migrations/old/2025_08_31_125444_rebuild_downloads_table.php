<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop the old, incorrect table if it exists (dev data only)
        Schema::dropIfExists('downloads');

        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->string('ip', 45)->nullable();                 // IPv4/IPv6
            $table->char('country', 2)->nullable()->index();      // e.g. "US", "GB"
            $table->string('source', 32)->nullable()->index();    // e.g. apple/spotify/web/overcast/pocketcasts
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'created_at']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
