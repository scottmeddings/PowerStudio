<?php
// app/Models/SocialPost.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    protected $fillable = [
        'user_id','title','body','episode_url',
        'visibility','services','status','assets',
    ];

    protected $casts = [
    'services' => 'array',
    'assets'   => 'array',
    'meta'     => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
