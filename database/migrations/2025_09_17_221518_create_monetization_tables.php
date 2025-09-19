<?php
// database/migrations/2025_09_18_000000_create_monetization_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_inventories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('pre_total')->default(0);
            $t->unsignedInteger('pre_sold')->default(0);
            $t->unsignedInteger('mid_total')->default(0);
            $t->unsignedInteger('mid_sold')->default(0);
            $t->unsignedInteger('post_total')->default(0);
            $t->unsignedInteger('post_sold')->default(0);
            $t->enum('status', ['draft','selling','paused'])->default('draft');
            $t->timestamps();
        });

        // rollup of money & delivery by day (your graphs read from here)
        Schema::create('revenue_daily', function (Blueprint $t) {
            $t->id();
            $t->date('day')->index();
            $t->unsignedInteger('downloads')->default(0);
            $t->decimal('impressions', 12, 2)->default(0);
            $t->decimal('ecpm', 8, 2)->default(0);
            $t->decimal('revenue_usd', 12, 2)->default(0);
            $t->timestamps();
            $t->unique(['day']);
        });

        Schema::create('payouts', function (Blueprint $t) {
            $t->id();
            $t->string('provider')->default('stripe');
            $t->string('external_id')->nullable()->index(); // Stripe payout id
            $t->date('payout_date')->nullable()->index();
            $t->decimal('amount_usd', 12, 2)->default(0);
            $t->enum('status', ['pending','in_transit','paid','failed','canceled','processing'])->default('pending');
            $t->json('meta')->nullable(); // raw payload
            $t->timestamps();
            $t->unique(['provider','external_id']);
        });

        Schema::create('stripe_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->index(); // if you link per user
            $t->string('account_id')->index();             // acct_xxx
            $t->string('account_type')->default('standard'); // standard|express|custom
            $t->string('status')->default('connected');    // connected|disconnected
            $t->json('capabilities')->nullable();
            $t->timestamps();
            $t->unique(['user_id','account_id']);
        });

        // optional: cache of Stripe balance transactions -> revenue mapping
        Schema::create('stripe_transactions', function (Blueprint $t) {
            $t->id();
            $t->string('txn_id')->unique();   // txn_xxx
            $t->date('available_on')->index();
            $t->decimal('amount_usd', 12, 2);
            $t->string('type')->nullable();   // charge, adjustment, transfer, etc.
            $t->json('raw')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_transactions');
        Schema::dropIfExists('stripe_accounts');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('revenue_daily');
        Schema::dropIfExists('ad_inventories');
    }
};
