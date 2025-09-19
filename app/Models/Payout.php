<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends \App\Models\TenantModel
{
    protected $fillable = [
        'provider',       // 'stripe' etc.
        'external_id',    // payout id from provider
        'payout_date',    // date
        'amount_usd',     // decimal
        'status',         // pending|processing|in_transit|paid|failed|canceled
        'meta',           // json payload
    ];

    protected $casts = [
        'payout_date' => 'date',
        'meta'        => 'array',
    ];
}
