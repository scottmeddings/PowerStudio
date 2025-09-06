<?php
// database/migrations/2025_09_06_000000_create_podcast_apps_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('podcast_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');                 // e.g. 'apple', 'spotify', 'ytmusic'...
            $table->string('status')->default('draft'); // draft|submitted|connected
            $table->string('external_url')->nullable(); // public show URL
            $table->json('config')->nullable();         // provider-specific settings
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id','provider']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('podcast_apps');
    }
};
