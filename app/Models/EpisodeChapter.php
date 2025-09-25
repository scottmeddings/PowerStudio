<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeChapter extends Model
{
    protected $fillable = [
        'episode_id', 'sort', 'title', 'starts_at_ms',
    ];

    protected static function booted()
    {
        static::creating(function ($chapter) {
            if (empty($chapter->sort)) {
                $maxSort = static::where('episode_id', $chapter->episode_id)->max('sort');
                $chapter->sort = $maxSort + 1;
            }
        });
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}

