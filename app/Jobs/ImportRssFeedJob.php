<?php

namespace App\Jobs;

use App\Models\Episode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;   // ✅ add this
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Throwable;

class ImportRssFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Progress key shared with controller */
    private const PROGRESS_KEY = 'rss_import:progress';

    public function __construct(
        public string $feedUrl,
        public bool   $set301 = false,
        public ?int   $userId = null,
    ) {}

    public int $timeout = 600;
    public int $tries   = 1;

    public function handle(): void
    {
        // require a concrete owner id
        $ownerId = (int) ($this->userId ?? 0);
        if ($ownerId <= 0) {
            $this->progress('failed: no authenticated user id', 100);
            throw new \RuntimeException('No authenticated user id provided to ImportRssFeedJob');
        }

        Log::info('ImportRssFeedJob boot', [
            'url'   => $this->feedUrl,
            'store' => config('podpower.rss_progress_store', 'file'),
            'user'  => $ownerId,
        ]);
        $this->progress('Starting import…', 2);

        $resp = Http::timeout(60)->get($this->feedUrl);
        if (!$resp->ok()) throw new \RuntimeException("Feed request failed ({$resp->status()})");

        $this->progress('Fetched feed…', 5);

        $xml = @simplexml_load_string($resp->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml) throw new \RuntimeException('Invalid RSS XML');

        $channel = $xml->channel ?: $xml;
        $items   = $channel->item ?: $xml->entry;
        $total   = count($items) ?: 1;
        $count   = 0;

        foreach ($items as $item) {
            $count++;

            $guid    = (string)($item->guid ?? $item->id ?? '');
            $title   = trim((string)$item->title) ?: 'Untitled';
            $desc    = trim((string)$item->description ?: (string)$item->summary);
            $pubDate = (string)$item->pubDate ?: (string)$item->published ?: (string)$item->updated;

            // enclosure
            $encAttr  = $item->enclosure ? $item->enclosure->attributes() : null;
            $audioUrl = (string)($encAttr['url'] ?? '');
            if (!$audioUrl && isset($item->link)) {
                foreach ($item->link as $linkEl) {
                    $attrs = $linkEl->attributes();
                    if (isset($attrs['rel']) && (string)$attrs['rel'] === 'enclosure') {
                        $audioUrl = (string)($attrs['href'] ?? '');
                        if ($audioUrl) break;
                    }
                }
            }

            // image
            $itunes   = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
            $imageUrl = (string)($itunes->image['href'] ?? '');
            if (!$imageUrl && isset($item->image->url)) $imageUrl = (string)$item->image->url;

            // idempotency key
            $externalId = $guid ?: $audioUrl;
            if (!$externalId) {
                $this->progress("Skipped (no guid/audio): {$title}", $this->pct($count, $total));
                continue;
            }

            $ep = Episode::query()->where('guid', $externalId)->first() ?? new Episode();

            // owner — MUST be set for NOT NULL user_id
            $ep->user_id = $ep->user_id ?? $ownerId;

            // fields
            $ep->guid        = $externalId;
            $ep->title       = $title;
            $ep->description = $desc ?: $ep->description;
            $ep->status      = $ep->status ?? 'published';

            if (empty($ep->slug)) {
                $ep->slug = Str::slug(Str::limit($title, 60, '')) . '-' . Str::lower(Str::random(6));
            }

            // audio download
            if ($audioUrl) {
                [$audioPath, $audioPublic] = $this->downloadToPublic($audioUrl, "episodes/{$ep->slug}", 'audio');
                if ($audioPath) {
                    $ep->audio_path = $audioPath;
                    $ep->audio_url  = $audioPublic;
                } else {
                    $ep->audio_url ??= $audioUrl;
                }
            }

            // image download
            if ($imageUrl) {
                [$imgPath, $imgPublic] = $this->downloadToPublic($imageUrl, "episodes/{$ep->slug}", 'image');
                if ($imgPath) {
                    $ep->image_path = $imgPath;
                    $ep->image_url  = $imgPublic;
                }
            }

            if ($pubDate) {
                try { $ep->published_at = \Carbon\Carbon::parse($pubDate); } catch (\Throwable) {}
            }

            // guard before save
            if (!$ep->user_id) {
                Log::error('Episode missing user_id; refusing to save', [
                    'guid' => $externalId, 'title' => $title, 'jobUserId' => $ownerId,
                ]);
                throw new \RuntimeException('No user_id for episode row');
            }

            $ep->save();

            $this->progress("Imported: {$ep->title}", $this->pct($count, $total));
        }

        $this->progress("Done: imported {$count} item(s).", 100);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ImportRssFeedJob failed', ['url' => $this->feedUrl, 'error' => $e->getMessage()]);
        $this->progress('failed: '.$e->getMessage(), 100);
    }

    private function pct(int $i, int $n): int
    {
        return (int) floor($i / max(1, $n) * 100);
    }

    /** ✅ progress helpers (were missing) */
   

    private function progress(string $msg, int $pct): void
{
    $pct = max(0, min(100, $pct));
    $this->progressStore()->put(self::PROGRESS_KEY, [
        'message'    => $msg,
        'percent'    => $pct,
        'started_at' => now()->toIso8601String(),
    ], now()->addMinutes(30));
}

private function progressStore()
{
    return Cache::store(config('podpower.rss_progress_store', 'file'));
}

    private function downloadToPublic(string $url, string $baseDir, string $basename): array
    {
        try {
            $response = Http::timeout(120)->withOptions(['stream' => true])->get($url);
            if (!$response->ok()) return [null, null];

            $ext      = $this->guessExtension($url, $response->header('content-type'));
            $filename = "{$basename}.{$ext}";
            $path     = trim($baseDir, '/')."/{$filename}";

            $disk = Storage::disk('public');
            $dir  = dirname($path);
            if (!$disk->exists($dir)) $disk->makeDirectory($dir);

            $stream = $response->toPsrResponse()->getBody();
            $disk->put($path, $stream);

            return [$path, $disk->url($path)];
        } catch (Throwable $e) {
            Log::warning('Download failed', ['url' => $url, 'error' => $e->getMessage()]);
            return [null, null];
        }
    }

    private function guessExtension(string $url, ?string $contentType): string
    {
        $fromUrl = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if ($fromUrl) return $fromUrl;

        $map = [
            'audio/mpeg'  => 'mp3',
            'audio/mp3'   => 'mp3',
            'audio/x-m4a' => 'm4a',
            'audio/mp4'   => 'm4a',
            'audio/aac'   => 'aac',
            'image/jpeg'  => 'jpg',
            'image/jpg'   => 'jpg',
            'image/png'   => 'png',
            'image/webp'  => 'webp',
        ];
        return $map[strtolower((string)$contentType)] ?? 'bin';
    }
}
