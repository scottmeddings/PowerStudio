<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
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
        // legacy JSON value
        'value' => 'array',

        // booleans
        'site_explicit'               => 'boolean',
        'site_topbar_show'            => 'boolean',
        'feed_explicit'               => 'boolean',
        'feed_remove_from_directory'  => 'boolean',
        'feed_apple_verification'     => 'boolean',
        'feed_redirect_enabled'       => 'boolean',
        'feed_set_new_feed_url'       => 'boolean',

        // ints
        'feed_episode_limit'          => 'integer',
        'singleton'                   => 'integer',
    ];

    /* ------------------------ Helpers ------------------------ */

    /** Scope: singleton row */
    public function scopeSingleton($q)
    {
        return $q->where('singleton', 1);
    }

    /** Return the singleton row, creating it if missing (with key = 'settings') */
    public static function singleton(): self
    {
        return static::query()->firstOrCreate(
            ['singleton' => 1],   // lookup
            ['key' => 'settings'] // only used on create
        );
    }

    /** Cached hasColumn to avoid repeated information_schema / pragma calls */
    protected static function hasColumn(string $column): bool
    {
        $cacheKey = "settings:hascol:$column";
        return Cache::remember($cacheKey, 300, function () use ($column) {
            return Schema::hasColumn('settings', $column);
        });
    }

    /**
     * Get a setting by name.
     * If a column with that name exists -> read from the singleton row column.
     * Otherwise, fall back to legacy key/value.
     */
    public static function get(string $name, $default = null)
    {
        $cacheKey = "settings:get:$name";

        return Cache::remember($cacheKey, 300, function () use ($name, $default) {
            if (static::hasColumn($name)) {
                $row = static::singleton();
                $val = $row->getAttribute($name);
                return $val !== null ? $val : $default;
            }

            // legacy key/value fallback
            $row = static::query()->where('key', $name)->first();
            if (!$row) return $default;

            $val = $row->value ?? $row->getAttribute('value');
            return $val !== null ? $val : $default;
        });
    }

    /**
     * Set a setting by name.
     * Writes to singleton column if the column exists; otherwise writes a legacy key/value row.
     */
    public static function set(string $name, $value): void
    {
        if (static::hasColumn($name)) {
            // write to singleton columns
            $row = static::singleton();
            $row->setAttribute($name, $value);
            $row->save();
        } else {
            // LEGACY key/value row: ensure singleton is NULL to avoid UNIQUE(singleton=1) collision
            static::query()->updateOrCreate(
                ['key' => $name],
                ['value' => $value, 'singleton' => null]
            );
        }

        Cache::forget("settings:get:$name");
    }

    /**
     * Bulk get with defaults (works for both column + legacy keys).
     *
     * @param array $keys      e.g. ['feed_url','feed_explicit'] or legacy keys
     * @param array $defaults  keyed by $keys (optional)
     * @return array           ['key' => value, ...]
     */
    public static function getMany(array $keys, array $defaults = []): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = static::get($k, $defaults[$k] ?? null);
        }
        return $out;
    }

    /** Convenience: set many at once (array of key => value). */
    public static function setMany(array $items): void
    {
        foreach ($items as $k => $v) {
            static::set($k, $v);
        }
    }

    /* Clear per-key cache when the model is saved directly on columns */
    protected static function booted(): void
    {
        static::saved(function (self $model) {
            foreach (array_keys($model->getChanges()) as $attr) {
                Cache::forget("settings:get:$attr");
            }
        });
    }
}
