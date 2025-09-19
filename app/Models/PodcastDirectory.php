<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PodcastDirectory extends \App\Models\TenantModel
{
    protected $fillable = [
        'user_id', 'slug', 'external_url', 'is_connected',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
    ];

    // Optional convenience alias so existing code that expects `connected` still works
    public function getConnectedAttribute(): bool
    {
        return (bool) $this->is_connected;
    }
}

