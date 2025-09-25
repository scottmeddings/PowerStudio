<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('episode_transcripts', function (Blueprint $table) {
            if (Schema::hasColumn('episode_transcripts', 'transcript')) {
                // Option 1: Drop it (preferred, since `body` is used)
                $table->dropColumn('transcript');

                // Option 2 (if you want to keep it instead of drop):
                // $table->text('transcript')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('episode_transcripts', function (Blueprint $table) {
            // Re-add column on rollback
            $table->longText('transcript')->nullable();
        });
    }
};
