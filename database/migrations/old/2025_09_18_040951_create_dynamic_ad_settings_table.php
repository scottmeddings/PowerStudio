<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dynamic_ad_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('podcast_id')->nullable()->index(); // if multi-podcast
            $t->enum('status', ['disabled','selling','paused'])->default('disabled');
            $t->unsignedTinyInteger('default_fill')->default(70); // %
            $t->unsignedTinyInteger('pre_total')->default(1);
            $t->unsignedTinyInteger('mid_total')->default(2);
            $t->unsignedTinyInteger('post_total')->default(1);
            $t->json('targets')->nullable(); // e.g. geo, categories
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('dynamic_ad_settings'); }
};

