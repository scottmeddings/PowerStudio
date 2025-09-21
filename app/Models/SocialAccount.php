<?php

// app/Models/SocialAccount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id','provider','external_id',
        'access_token','refresh_token','expires_at','meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'meta'       => 'array',
    ];

    // Encrypt tokens at rest
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v ? decrypt($v) : null,
            set: fn ($v) => $v ? encrypt($v) : null,
        );
    }

    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v ? decrypt($v) : null,
            set: fn ($v) => $v ? encrypt($v) : null,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
