<?php

// app/Http/Controllers/StripeWebhookController.php
namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\RevenueDaily;
use App\Models\StripeAccount;
use App\Models\StripeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret', env('STRIPE_WEBHOOK_SECRET'));

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            return response('Invalid', 400);
        }

        switch ($event->type) {
            case 'payout.paid':
            case 'payout.created':
            case 'payout.canceled':
            case 'payout.failed':
                $p = $event->data->object; // \Stripe\Payout
                Payout::updateOrCreate(
                    ['provider'=>'stripe','external_id'=>$p->id],
                    [
                        'payout_date' => Carbon::createFromTimestamp($p->arrival_date)->toDateString(),
                        'amount_usd'  => round($p->amount/100,2),
                        'status'      => $p->status,
                        'meta'        => $p,
                    ]
                );
                break;

            case 'balance.available':
                // optional: map balance txns -> daily revenue
                dispatch(new \App\Jobs\SyncStripeRevenueJob())->onQueue('default');
                break;
        }

        return response('ok', 200);
    }
}
