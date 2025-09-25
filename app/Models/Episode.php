<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use App\Models\EpisodeChapter;
use App\Models\EpisodeTranscript;
use App\Models\Concerns\BelongsToOwner;

class Episode extends TenantModel
{
    use HasFactory, BelongsToOwner;

    protected $table = 'episodes';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'slug',
        'audio_url',
        'audio_path',
        'audio_bytes',
        'duration_seconds',
        'status',
        'published_at',
        'cover_path',
        'image_url',
        'image_path',
        'episode_number',
        'season',
        'episode_type',
        'explicit',
        'uuid',
        'unique_key',
    ];

    protected $casts = [
        'published_at'     => 'datetime',
        'duration_seconds' => 'integer',
        'user_id'          => 'integer',
    ];

    /* ---------------- Relationships ---------------- */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function downloads()
    {
        return $this->hasMany(Download::class);
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
        return $this->hasOne(EpisodeTranscript::class, 'episode_id', 'id');
    }


    /* ---------------- Scopes ---------------- */

    public function scopePublishedWithAudio(Builder $q): Builder
    {
        return $q->whereNotNull('published_at')
                 ->where('published_at', '<=', now())
                 ->whereNotNull('playable_url');
    }

    public function newEloquentBuilder($query): EpisodeBuilder
    {
        return new EpisodeBuilder($query);
    }

    /* ---------------- Accessors ---------------- */

    public function getPlaysAttribute()
    {
        return $this->downloads_count ?? $this->downloads()->count();
    }

    public function getPlayableUrlAttribute(): ?string
    {
        if ($this->audio_url) {
            return $this->audio_url;
        }
        if (!$this->audio_path) {
            return null;
        }

        if (Str::startsWith($this->audio_path, ['http://','https://','//'])) {
            return $this->audio_path;
        }

        return Storage::disk('public')->url($this->audio_path);
    }

    public function getCoverUrlAttribute(): string
    {
        if (!empty($this->cover_path)) {
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
