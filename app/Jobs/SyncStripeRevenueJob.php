<?php

// app/Jobs/SyncStripeRevenueJob.php
namespace App\Jobs;

use App\Models\Payout;
use App\Models\RevenueDaily;
use App\Models\StripeAccount;
use App\Models\StripeTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Stripe\StripeClient;

class SyncStripeRevenueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public ?int $userId = null) {}

    public function handle(): void
    {
        $secret = config('services.stripe.secret');
        if (!$secret) return;

        // If you support per-user Connect, select that account, else env:
        $acctId = config('services.stripe.connect_account');

        $client = new StripeClient($secret);
        $opts   = $acctId ? ['stripe_account'=>$acctId] : [];

        // 1) Balance transactions -> revenue rollup
        // We’ll pull last 6 months (tweak as you like)
        $since = now()->subMonths(6)->startOfDay()->timestamp;

        $params = ['limit'=>100, 'created'=>['gte'=>$since]];
        $autoPager = $client->balanceTransactions->all($params, $opts)->autoPagingIterator();

        $buckets = []; // day => amount_usd
        foreach ($autoPager as $txn) {
            // We’ll treat positive amounts as revenue; exclude fees/adjustments if you prefer.
            $amount = ($txn->amount ?? 0) / 100; // USD
            $day    = Carbon::createFromTimestamp($txn->available_on ?: $txn->created)->toDateString();

            StripeTransaction::updateOrCreate(
                ['txn_id'=>$txn->id],
                [
                    'available_on'=>Carbon::createFromTimestamp($txn->available_on ?: $txn->created),
                    'amount_usd'=>$amount,
                    'type'=>$txn->type,
                    'raw'=>$txn,
                ]
            );

            if ($amount > 0) {
                $buckets[$day] = ($buckets[$day] ?? 0) + $amount;
            }
        }

        foreach ($buckets as $day=>$amount) {
            $row = RevenueDaily::firstOrNew(['day'=>$day]);
            $row->revenue_usd = round(($row->revenue_usd ?? 0) + $amount, 2);
            // leave downloads/ecpm as-is if you set them via estimates or another import
            $row->save();
        }

        // 2) Payouts
        $payoutPager = $client->payouts->all(['limit'=>100, 'arrival_date'=>['gte'=>$since]], $opts)->autoPagingIterator();
        foreach ($payoutPager as $p) {
            Payout::updateOrCreate(
                ['provider'=>'stripe','external_id'=>$p->id],
                [
                    'payout_date' => Carbon::createFromTimestamp($p->arrival_date)->toDateString(),
                    'amount_usd'  => round($p->amount/100,2),
                    'status'      => $p->status,
                    'meta'        => $p,
                ]
            );
        }
    }
}
