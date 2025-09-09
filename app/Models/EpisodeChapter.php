<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeChapter extends Model
{
    protected $fillable = [
        'episode_id', 'sort', 'title', 'starts_at_ms',
    ];

    // If someone sets starts_at (seconds), transparently store starts_at_ms.
    public function setStartsAtAttribute($value): void
    {
        $sec = is_numeric($value) ? (float) $value : 0;
        $this->attributes['starts_at_ms'] = (int) round(max(0, $sec) * 1000);
    }

    // Convenience accessor to read seconds from ms
    public function getStartsAtAttribute(): int
    {
        return (int) floor(($this->starts_at_ms ?? 0) / 1000);
    }
}
