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

    // ─────────────────────────────────────────────────────────────────────────────
    // Roles
    // ─────────────────────────────────────────────────────────────────────────────
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_CREATOR  = 'creator';
    public const ROLE_READONLY = 'readonly';
    public const ROLE_USER     = 'user'; // baseline

    public static function allowedRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_CREATOR,
            self::ROLE_READONLY,
            self::ROLE_USER,
        ];
    }

    // Convenience checks
    public function isAdmin(): bool    { return $this->role === self::ROLE_ADMIN; }
    public function isCreator(): bool  { return $this->role === self::ROLE_CREATOR; }
    public function isReadonly(): bool { return $this->role === self::ROLE_READONLY; }
    public function isUser(): bool     { return $this->role === self::ROLE_USER; }

    // Simple “capabilities” helpers you can expand later
    public function canManageUsers(): bool   { return $this->isAdmin(); }
    public function canEditContent(): bool   { return $this->isAdmin() || $this->isCreator(); }
    public function canViewContent(): bool   { return true; } // everyone may view by default

    // Query scopes
    public function scopeAdmins($q)   { return $q->where('role', self::ROLE_ADMIN); }
    public function scopeCreators($q) { return $q->where('role', self::ROLE_CREATOR); }
    public function scopeReadonly($q) { return $q->where('role', self::ROLE_READONLY); }
    public function scopeUsers($q)    { return $q->where('role', self::ROLE_USER); }

    // ─────────────────────────────────────────────────────────────────────────────
    // Mass assignment / defaults / casts
    // ─────────────────────────────────────────────────────────────────────────────
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',                // ← important for managing roles
        // 'profile_photo_path',
        // 'cover_path',
        // 'cover_url',
        // 'podcast_cover_url',
    ];

    protected $attributes = [
        'role' => self::ROLE_USER, // default baseline
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'profile_photo_url',
        'avatar_url',
        'cover_image_url',
        'display_name',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => 'string',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────────
    public function collaborator(): HasOne
    {
        return $this->hasOne(\App\Models\Collaborator::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(\App\Models\Episode::class)->latest();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────────────────────
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
