<?php

// app/Models/PodcastApp.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PodcastApp extends \App\Models\TenantModel
{
    protected $fillable = [
        'user_id','provider','status','external_url','config','submitted_at','connected_at'
    ];

    protected $casts = [
        'config'       => 'array',
        'submitted_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }

    // Small helpers
    public function displayName(): string {
        return [
            'apple' => 'Apple Podcasts',
            'spotify' => 'Spotify',
            'ytmusic' => 'YouTube Music',
            'amazon' => 'Amazon Music',
            'iheart' => 'iHeartRadio',
            'tunein' => 'TuneIn',
            'pocketcasts' => 'Pocket Casts',
            'overcast' => 'Overcast',
            'castbox' => 'Castbox',
            'deezer' => 'Deezer',
            'pandora' => 'Pandora',
        ][$this->provider] ?? ucfirst($this->provider);
    }
}
