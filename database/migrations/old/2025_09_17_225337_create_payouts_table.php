<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payouts')) {
            Schema::create('payouts', function (Blueprint $t) {
                $t->id();
                $t->string('provider')->default('stripe');
                $t->string('external_id')->nullable()->index();
                $t->date('payout_date')->nullable()->index();
                $t->decimal('amount_usd', 12, 2)->default(0);
                $t->enum('status', ['pending','processing','in_transit','paid','failed','canceled'])->default('pending');
                $t->json('meta')->nullable();
                $t->timestamps();
                $t->unique(['provider','external_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};

