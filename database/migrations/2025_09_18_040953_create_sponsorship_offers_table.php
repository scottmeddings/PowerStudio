<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sponsorship_offers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('podcast_id')->nullable()->index();
            $t->string('title');
            $t->decimal('cpm_usd', 8, 2)->default(0);
            $t->unsignedInteger('min_downloads')->default(0);
            $t->unsignedTinyInteger('pre_slots')->default(0);
            $t->unsignedTinyInteger('mid_slots')->default(1);
            $t->unsignedTinyInteger('post_slots')->default(0);
            $t->date('start_at')->nullable();
            $t->date('end_at')->nullable();
            $t->enum('status', ['draft','active','paused','archived'])->default('draft');
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sponsorship_offers'); }
};
