<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('achievement_unlocks');

        Schema::create('achievement_unlocks', function (Blueprint $table) {
            $table->id();

            // what the seeder writes:
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 191)->index();
            $table->string('title', 191);
            $table->string('description', 512)->nullable();
            $table->timestamp('unlocked_at')->nullable(); // seeder provides a value

            // seeder only sets updated_at, but keep both nullable
            $table->timestamps();

            // avoid duplicate unlocks per user/achievement
            $table->unique(['user_id', 'code']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_unlocks');
    }
};
