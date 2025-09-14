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
    
    public function scopePublishedWithAudio($q)
    {
        return $q->where('status', 'published')
                 ->where(function($q){
                     $q->whereNotNull('audio_url')->orWhereNotNull('audio_path');
                 });
    }

    // Always returns a usable URL (CDN if set, else storage/public URL, else raw path if already absolute)
    public function getPlayableUrlAttribute(): ?string
    {
        if ($this->audio_url) return $this->audio_url;
        if (!$this->audio_path) return null;
        if (Str::startsWith($this->audio_path, ['http://','https://','//'])) return $this->audio_path;
        return Storage::disk('public')->url($this->audio_path); // adjust disk if needed
    }

    public function getCoverUrlAttribute(): string
    {
        if (!empty($this->cover_path)) {
            // change disk if your covers live on a different disk
            return Storage::disk('public')->url($this->cover_path);
        }
        if (!empty($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }
        if (!empty($this->image_url)) {
            return Str::startsWith($this->image_url, ['http://','https://','//','data:'])
                ? $this->image_url
                : asset(ltrim($this->image_url, '/'));
        }
        return asset('images/podcast-cover.jpg');
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
