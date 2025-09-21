<?php
// database/migrations/2025_09_21_000000_create_social_credentials_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('social_credentials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider');
            $t->json('credentials')->nullable();
            $t->timestamp('connected_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id','provider']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('social_credentials');
    }
};

