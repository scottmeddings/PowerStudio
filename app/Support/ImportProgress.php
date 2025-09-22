<?php
// app/Support/ImportProgress.php
namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class ImportProgress
{
    public static function key(int $userId): string
    {
        return "rss_import:progress:{$userId}";
    }

    public static function put(int $userId, int $percent, string $message): void
    {
        Cache::put(self::key($userId), ['percent'=>$percent, 'message'=>$message], now()->addHour());
    }

    public static function get(int $userId): array
    {
        return Cache::get(self::key($userId), ['percent'=>0, 'message'=>'Waiting to start…']);
    }

    public static function clear(int $userId): void
    {
        Cache::forget(self::key($userId));
    }
}
