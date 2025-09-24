<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('audio_url'); // e.g. storage path
            // (Optional) If you prefer URLs instead of storage paths:
            // $table->string('cover_url')->nullable()->after('audio_url');
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('cover_path');
            // $table->dropColumn('cover_url');
        });
    }
};
