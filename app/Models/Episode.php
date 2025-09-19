<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use App\Models\EpisodeChapter;
use App\Models\EpisodeTranscript;
use App\Models\Concerns\BelongsToOwner;


class Episode extends \App\Models\TenantModel
{
   

    use HasFactory, BelongsToOwner;

    // Let the trait set user_id on create; include it ONLY if you also import/migrate from external data.
    

 
  
    protected $fillable = ['title','...','user_id']; // keep user_id fillable if you import/migrate

   
  

    // app/Models/Episode.php


    protected $casts = [
        'published_at'     => 'datetime',
        'duration_seconds' => 'integer',
        'user_id' => 'int',
        // Do NOT cast 'chapters' to array when using the hasMany relation.
    ];

    /* Relationships */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
     public function downloads()
    {
        return $this->hasMany(\App\Models\Download::class);
    }

    // Optional convenience so you can use $episode->plays anywhere
    public function getPlaysAttribute()
    {
        // prefer eager count if present; otherwise query
        return $this->downloads_count ?? $this->downloads()->count();
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
