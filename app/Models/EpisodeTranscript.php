<?php

// app/Models/EpisodeTranscript.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeTranscript extends Model
{
    protected $table = 'episode_transcripts'; // ✅ ensure correct table name

    protected $fillable = [
        'episode_id',
        'body',
        'format',
        'duration_ms',
        'storage_path',
    ];

    public function episode()
    {
        return $this->belongsTo(Episode::class, 'episode_id', 'id');
    }
}

