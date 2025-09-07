<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mass assignable attributes.
     * Add others here only if you update them via mass-assignment (e.g., $user->update([...])).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // 'profile_photo_path', // uncomment if you mass-assign it anywhere
        // 'cover_path',         // same for cover fields
        // 'cover_url',
        // 'podcast_cover_url',
    ];

    /**
     * Hidden attributes.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Public cover image (podcast artwork) URL.
     * Priority: explicit podcast_cover_url > cover_url > storage file > null.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->podcast_cover_url) return $this->podcast_cover_url;
        if ($this->cover_url)        return $this->cover_url;
        if ($this->cover_path)       return Storage::url($this->cover_path);
        return null;
    }

    /**
     * Profile photo URL (used anywhere you need the user's avatar).
     * Priority: stored file (profile_photo_path) > profile_photo_url (if you have one) > Gravatar fallback.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if (!empty($this->profile_photo_path)) {
            return Storage::url($this->profile_photo_path);
        }

        if (!empty($this->profile_photo_url)) {
            return $this->profile_photo_url;
        }

        // Gravatar fallback (silhouette)
        $hash = md5(strtolower(trim((string) $this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?s=240&d=mp";
    }

    /**
     * Alias for templates: $user->avatar_url
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->profile_photo_url; // uses the accessor above
    }

    /**
     * Handy display name fallback.
     */
    public function getDisplayNameAttribute(): string
    {
        if (!empty($this->name)) return $this->name;
        return (string) Str::of((string) $this->email)->before('@')->headline();
    }

    /**
     * Relationship: episodes owned by the user.
     */
    public function episodes()
    {
        return $this->hasMany(Episode::class)->latest();
    }
}
