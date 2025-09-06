<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('podcast_directories', function (Blueprint $table) {
            // add if missing
            if (! Schema::hasColumn('podcast_directories', 'user_id')) {
                $table->foreignId('user_id')
                      ->after('id')
                      ->constrained()         // references users.id
                      ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('podcast_directories', 'slug')) {
                $table->string('slug', 40)->after('user_id');
            }

            if (! Schema::hasColumn('podcast_directories', 'external_url')) {
                $table->string('external_url')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('podcast_directories', 'is_connected')) {
                $table->boolean('is_connected')->default(false)->after('external_url');
            }
        });

        // Add/ensure the uniqueness per user+slug
        Schema::table('podcast_directories', function (Blueprint $table) {
            // For SQLite, Laravel will create the index if not present
            $table->unique(['user_id', 'slug'], 'podcast_dirs_user_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('podcast_directories', function (Blueprint $table) {
            // Drop unique then column (SQLite-friendly)
            if (Schema::hasColumn('podcast_directories', 'user_id')) {
                $table->dropUnique('podcast_dirs_user_slug_unique');
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
