<?php

namespace App\Models;

class SiteSetting extends Setting
{
    /** Back-compat for old helpers */
    public static function getValue(string $key, $default = null)
    {
        return parent::get($key, $default);
    }

    public static function putValue(string $key, $value): void
    {
        parent::set($key, $value);
    }
}
