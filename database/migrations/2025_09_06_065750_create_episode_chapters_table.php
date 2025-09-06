<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('episode_chapters', function (Blueprint $t) {
            $t->id();
            $t->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('starts_at_ms');         // 0 … duration in ms
            $t->string('title', 160);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('episode_chapters'); }
};
