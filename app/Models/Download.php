<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    // No $table override needed (defaults to 'downloads')

    protected $fillable = [
        'episode_id', 'source', 'country', 'ip', 'user_agent',
        'created_at', 'updated_at',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class, 'episode_id');
    }
}