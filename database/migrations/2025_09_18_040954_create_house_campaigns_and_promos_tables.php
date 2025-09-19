<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('house_campaigns', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('podcast_id')->nullable()->index();
            $t->string('name');
            $t->enum('status', ['draft','active','paused','completed'])->default('draft');
            $t->date('start_at')->nullable();
            $t->date('end_at')->nullable();
            $t->unsignedTinyInteger('priority')->default(5); // 1=high
            $t->json('targets')->nullable();
            $t->timestamps();
        });

        Schema::create('house_promos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('campaign_id')->index();
            $t->string('label');
            $t->enum('slot', ['pre','mid','post'])->default('mid');
            $t->string('audio_url')->nullable(); // S3 path etc
            $t->string('cta_url')->nullable();
            $t->json('episodes')->nullable(); // episode ids to target
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('house_promos');
        Schema::dropIfExists('house_campaigns');
    }
};
