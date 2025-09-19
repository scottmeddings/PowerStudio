<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends TenantModel
{
    protected $table = 'settings';

    protected $fillable = [
        'user_id',

        // legacy
        'key', 'value', 'singleton',

        // core site settings
        'feed_url',
        'site_title', 'site_link', 'site_lang', 'site_desc',
        'site_itunes_author', 'site_itunes_summary', 'site_itunes_image',
        'site_owner_name', 'site_owner_email',
        'site_explicit', 'site_category', 'site_type',

        // website / display
        'podcast_subdomain', 'site_country', 'site_timezone',
        'episode_download_visibility', 'site_topbar_show',

        // feed options
        'feed_explicit', 'feed_apple_summary',

        // advanced feed
        'feed_episode_limit', 'feed_ownership_email',
        'feed_episode_link', 'feed_episode_artwork_tag',

        // directories / verification
        'feed_remove_from_directory', 'feed_apple_verification',

        // redirects
        'feed_redirect_enabled', 'feed_redirect_url', 'feed_set_new_feed_url',
    ];

    protected $casts = [
        'user_id'                     => 'int',
        'value'                       => 'array',   // legacy JSON
        'site_explicit'               => 'boolean',
        'site_topbar_show'            => 'boolean',
        'feed_explicit'               => 'boolean',
        'feed_remove_from_directory'  => 'boolean',
        'feed_apple_verification'     => 'boolean',
        'feed_redirect_enabled'       => 'boolean',
        'feed_set_new_feed_url'       => 'boolean',
        'feed_episode_limit'          => 'integer',
        'singleton'                   => 'integer',
    ];

    /* ------------------------ Helpers ------------------------ */

    /** Scope: singleton row */
    public function scopeSingleton($q)
    {
        return $q->where('singleton', 1);
    }

    /**
     * Return the per-user singleton row (creating it if missing).
     * Uses NULL key to avoid UNIQUE(key) collisions.
     */
    public static function singleton(?int $userId = null): self
    {
        $uid = $userId ?? Auth::id();

        // No auth context (console/queue). Don't create dupes; reuse any existing singleton row.
        if (!$uid) {
            return static::query()
                ->singleton()
                ->firstOrCreate(['singleton' => 1], ['key' => null, 'user_id' => null]);
        }

        // Per-user singleton; keep key NULL
        return static::query()->firstOrCreate(
            ['user_id' => $uid, 'singleton' => 1],
            ['key' => null]
        );
    }

    /** Cached hasColumn to avoid repeated schema checks */
    protected static function hasColumn(string $column): bool
    {
        return Cache::remember("settings:hascol:$column", 300, fn () =>
            Schema::hasColumn('settings', $column)
        );
    }

    /** Get a setting by name for the CURRENT user. */
    public static function get(string $name, $default = null)
    {
        $uid = Auth::id() ?? 0;
        $cacheKey = "settings:u:$uid:get:$name";

        return Cache::remember($cacheKey, 300, function () use ($name, $default, $uid) {
            if (static::hasColumn($name)) {
                $row = static::singleton($uid);
                $val = $row->getAttribute($name);
                return $val !== null ? $val : $default;
            }

            // Legacy per-user key/value; fall back to global (user_id NULL) if present
            $row = static::query()->where('key', $name)->where('user_id', $uid)->first()
                ?? static::query()->where('key', $name)->whereNull('user_id')->first();

            if (!$row) return $default;

            $val = $row->value ?? $row->getAttribute('value');
            return $val !== null ? $val : $default;
        });
    }

    /** Set a setting by name for the CURRENT user. */
    public static function set(string $name, $value): void
    {
        $uid = Auth::id();

        if (static::hasColumn($name)) {
            $row = static::singleton($uid);
            $row->setAttribute($name, $value);
            $row->save();
        } else {
            static::query()->updateOrCreate(
                ['user_id' => $uid, 'key' => $name],
                ['value' => $value, 'singleton' => null]
            );
        }

        Cache::forget("settings:u:" . ($uid ?? 0) . ":get:$name");
    }

    /** Bulk get with defaults for CURRENT user. */
    public static function getMany(array $keys, array $defaults = []): array
    {
        $out = [];
        foreach ($keys as $k) $out[$k] = static::get($k, $defaults[$k] ?? null);
        return $out;
    }

    /** Set many at once for CURRENT user. */
    public static function setMany(array $items): void
    {
        foreach ($items as $k => $v) static::set($k, $v);
    }

    /** Admin/utility: get all settings as key=>value for a specific user. */
    public static function kv(?int $userId = null): array
    {
        $uid = $userId ?? Auth::id();

        $map = [];

        // Columns from the per-user singleton row
        $row = static::singleton($uid);
        if ($row) {
            foreach ($row->getAttributes() as $attr => $val) {
                if (in_array($attr, ['id','user_id','key','value','singleton','created_at','updated_at'], true)) continue;
                $map[$attr] = $val;
            }
        }

        // Legacy rows for this user
        static::query()
            ->where('user_id', $uid)
            ->whereNull('singleton')
            ->get(['key','value'])
            ->each(function ($r) use (&$map) {
                $map[$r->key] = $r->value;
            });

        return $map;
    }

    /* Clear per-key cache when the model is saved directly on columns */
    protected static function booted(): void
    {
        parent::booted();

        static::saved(function (self $model) {
            $uid = $model->user_id ?? (Auth::id() ?? 0);
            foreach (array_keys($model->getChanges()) as $attr) {
                Cache::forget("settings:u:$uid:get:$attr");
            }
        });
    }
}
