// database/migrations/2025_01_01_000000_create_comments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('approved'); // approved|pending|spam
            $table->timestamps();

            $table->index(['episode_id','created_at']);
            $table->index(['status','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
