<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueDaily extends \App\Models\TenantModel
{
    // Our table name is not the Laravel plural default
    protected $table = 'revenue_daily';

    protected $fillable = [
        'day',           // date (Y-m-d)
        'downloads',     // int
        'impressions',   // decimal
        'ecpm',          // decimal
        'revenue_usd',   // decimal
    ];

    protected $casts = [
        'day' => 'date',
    ];
}
