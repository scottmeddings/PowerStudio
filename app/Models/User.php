<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Passkeys (WebAuthn)
use Laragear\WebAuthn\WebAuthnAuthentication;
use Laragear\WebAuthn\Enums\UserVerification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, WebAuthnAuthentication;

    /**
     * Require real user verification (Windows Hello / Touch ID / etc).
     * You can relax this to UserVerification::Preferred if desired.
     */
    protected UserVerification $webauthnUserVerification = UserVerification::Required;
    public function isAdmin(): bool   { return $this->role === 'admin'; }
    public function isUser(): bool    { return $this->role === 'user'; }
    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // 'profile_photo_path',
        // 'cover_path',
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
     * Automatically include these accessors when casting to array/JSON.
     */
    protected $appends = [
        'profile_photo_url',
        'avatar_url',
        'cover_image_url',
        'display_name',
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
     * Relationships
     */
    public function collaborator(): HasOne
    {
        return $this->hasOne(\App\Models\Collaborator::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(\App\Models\Episode::class)->latest();
    }

    /**
     * Public cover image (podcast artwork) URL.
     * Priority: podcast_cover_url > cover_url > storage file > null.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (!empty($this->podcast_cover_url)) return $this->podcast_cover_url;
        if (!empty($this->cover_url))        return $this->cover_url;
        if (!empty($this->cover_path))       return Storage::url($this->cover_path);
        return null;
    }

    /**
     * Profile photo URL with Gravatar fallback.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if (!empty($this->profile_photo_path)) {
            return Storage::url($this->profile_photo_path);
        }

        if (!empty($this->profile_photo_url)) {
            return $this->profile_photo_url;
        }

        $hash = md5(strtolower(trim((string) $this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?s=240&d=mp";
    }

    /**
     * Alias for templates: $user->avatar_url.
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->profile_photo_url;
    }

    /**
     * Handy display name fallback: "Scott Meddings" → provided,
     * otherwise "scott" → "Scott".
      */
    public function getDisplayNameAttribute(): string
    {
        if (!empty($this->name)) {
            return $this->name;
        }

        return (string) Str::of((string) $this->email)->before('@')->headline();
    }
}
