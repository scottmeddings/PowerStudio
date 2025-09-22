<?php
// app/Models/SiteSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'settings';           // <-- IMPORTANT: use ONE table
    public $timestamps = false;

    protected $fillable = ['user_id', 'key', 'value'];

    // Value is stored as JSON
    protected $casts = ['value' => 'array'];

    public static function getValue(string $key, $default = null, ?int $userId = null)
    {
        $row = static::query()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->where('key', $key)
            ->first();

        return $row?->value ?? $default;
    }

    public static function setValue(string $key, $value, ?int $userId = null): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }
}
