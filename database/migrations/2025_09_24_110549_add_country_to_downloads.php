<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            if (!Schema::hasColumn('downloads', 'country')) {
                $table->string('country', 100)->nullable()->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            if (Schema::hasColumn('downloads', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
