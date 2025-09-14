<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialConnection extends Model
{
    protected $fillable = [
        'user_id','provider','access_token','refresh_token','expires_at',
        'page_id','channel_id','settings','is_connected'
    ];

    protected $casts = [
        'settings'     => 'array',
        'is_connected' => 'bool',
        'expires_at'   => 'datetime',
    ];
}
