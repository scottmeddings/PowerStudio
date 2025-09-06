<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'slug', 'description', 'audio_url',
        'duration_seconds', 'status', 'published_at','cover_path',
    ];

    protected $casts = [
        'published_at' => 'datetime',   // <-- add this
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Current episode cover URL, with fallback to the user's podcast cover
    public function getCoverImageUrlAttribute(): ?string
    {
        if (!empty($this->cover_url ?? null)) {
            return $this->cover_url;
        }
        if (!empty($this->cover_path ?? null)) {
            return Storage::url($this->cover_path);
        }
        // Fallback to user's cover accessors you already exposed (e.g. cover_image_url)
        if ($this->relationLoaded('user') || $this->user) {
            return $this->user->cover_image_url ?? null; // from your earlier User accessor
        }
        return null;
    }
    // app/Models/Episode.php  (add)
    public function chapters(){ return $this->hasMany(\App\Models\EpisodeChapter::class)->orderBy('sort'); }
    public function transcript(){ return $this->hasOne(\App\Models\EpisodeTranscript::class); }

    
}
