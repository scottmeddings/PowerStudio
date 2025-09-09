<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use App\Models\EpisodeChapter;
use App\Models\EpisodeTranscript;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'audio_url',
        'duration_seconds',
        'status',
        'published_at',
        'cover_path',
        'cover_url',
         'ai_status', 
         'ai_progress', 
         'ai_message',
    ];

    protected $casts = [
        'published_at'     => 'datetime',
        'duration_seconds' => 'integer',
        // Do NOT cast 'chapters' to array when using the hasMany relation.
    ];

    /* Relationships */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function chapters()
    {
        return $this->hasMany(EpisodeChapter::class)->orderBy('sort');
    }

    public function transcript()
    {
        return $this->hasOne(\App\Models\EpisodeTranscript::class);
    }
   

    // always load transcript with the model

    /* Accessors */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (!empty($this->cover_url)) {
            return $this->cover_url;
        }

        if (!empty($this->cover_path)) {
            return Storage::url($this->cover_path);
        }

        if ($this->relationLoaded('user') || $this->user()->exists()) {
            return $this->user->cover_image_url ?? null;
        }

        return null;
    }
}
