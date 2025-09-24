<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('revenue_daily')) {
            Schema::create('revenue_daily', function (Blueprint $t) {
                $t->id();
                $t->date('day')->unique();
                $t->unsignedInteger('downloads')->default(0);
                $t->decimal('impressions', 12, 2)->default(0);
                $t->decimal('ecpm', 8, 2)->default(0);
                $t->decimal('revenue_usd', 12, 2)->default(0);
                $t->timestamps();
            });
        } else {
            // Safe add-missing-columns path if the table already exists
            Schema::table('revenue_daily', function (Blueprint $t) {
                if (!Schema::hasColumn('revenue_daily','day'))         $t->date('day')->nullable()->index();
                if (!Schema::hasColumn('revenue_daily','downloads'))   $t->unsignedInteger('downloads')->default(0);
                if (!Schema::hasColumn('revenue_daily','impressions')) $t->decimal('impressions', 12, 2)->default(0);
                if (!Schema::hasColumn('revenue_daily','ecpm'))        $t->decimal('ecpm', 8, 2)->default(0);
                if (!Schema::hasColumn('revenue_daily','revenue_usd')) $t->decimal('revenue_usd', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_daily');
    }
};


