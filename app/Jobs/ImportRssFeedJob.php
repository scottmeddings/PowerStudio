<?php

namespace App\Jobs;

use App\Models\Episode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ImportRssFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PROGRESS_KEY = 'rss_import:progress';

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        public string $feedUrl,
        public bool   $set301 = false,
        public ?int   $userId = null
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        if (!$this->userId) {
            throw new \RuntimeException('episodes.user_id is required.');
        }

        $this->progress(3, 'Fetching feed…');

        $resp = Http::timeout(30)->get($this->feedUrl);
        if (!$resp->ok()) {
            throw new \RuntimeException('Feed fetch failed: HTTP '.$resp->status());
        }

        $xml = @simplexml_load_string($resp->body());
        if (!$xml) {
            throw new \RuntimeException('Invalid RSS/Atom XML.');
        }

        // Collect items (RSS <item> or Atom <entry>)
        $items = [];
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $i) $items[] = $i;
        } elseif (isset($xml->entry)) {
            foreach ($xml->entry as $e) $items[] = $e;
        }

        $total = max(1, count($items));
        $done  = 0;
        $this->progress(8, 'Parsing feed items… ('.$total.')');

        foreach ($items as $item) {
            $done++;

            // ----- Core fields -----
            $title = trim((string)($item->title ?? '')) ?: 'Untitled';
            $desc  = trim((string)($item->description ?? $item->summary ?? ''));

            $pub   = (string)($item->pubDate ?? $item->updated ?? $item->published ?? '');
            $pubAt = $this->parseDate($pub); // 'Y-m-d H:i:s' or null (allowed)

            // enclosure (audio) + bytes if advertised
            $enclosureUrl  = '';
            $enclosureSize = null;
            if (isset($item->enclosure)) {
                $attrs = $item->enclosure->attributes();
                $enclosureUrl = (string)($attrs['url'] ?? '');
                $len          = (string)($attrs['length'] ?? '');
                $enclosureSize = is_numeric($len) ? (int)$len : null;
            } else {
                foreach ($item->link ?? [] as $lnk) {
                    $attr = $lnk->attributes();
                    if ((string)($attr['rel'] ?? '') === 'enclosure') {
                        $enclosureUrl = (string)($attr['href'] ?? '');
                        $len          = (string)($attr['length'] ?? '');
                        $enclosureSize = is_numeric($len) ? (int)$len : null;
                        break;
                    }
                }
            }

            if ($title === '' && $enclosureUrl === '') {
                Log::warning('Skipping item with no title & no enclosure');
                $this->tick($done, $total, 'Skipping an empty item…');
                continue;
            }

            // ----- Namespaces -----
            $itunes  = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
            $media   = $item->children('http://search.yahoo.com/mrss/');
            $pi      = $item->children('https://podcastindex.org/namespace/1.0');

            // iTunes fields
            $durRaw          = (string)($itunes->duration ?? '');
            $durationSeconds = $this->parseItunesDuration($durRaw);
            $epNum           = $this->toIntOrNull((string)($itunes->episode ?? ''));
            $season          = $this->toIntOrNull((string)($itunes->season ?? ''));
            $epType          = (string)($itunes->episodeType ?? ''); // full|bonus|trailer
            $explicitStr     = strtolower(trim((string)($itunes->explicit ?? '')));
            $explicitFlag    = in_array($explicitStr, ['yes','true','explicit','1'], true) ? 1 : 0;

            // Images
            $imageUrl = $this->pickImageUrl($item, $itunes, $media);

            // PodcastIndex
            $transcriptUrl = null;
            $chaptersUrl   = null;
            if ($pi) {
                if (isset($pi->transcript)) {
                    $a = $pi->transcript->attributes();
                    $transcriptUrl = (string)($a['url'] ?? null);
                }
                if (isset($pi->chapters)) {
                    $a = $pi->chapters->attributes();
                    $chaptersUrl = (string)($a['url'] ?? null);
                }
            }

            // GUID/ID (for storage/reference only)
            $guid = (string)($item->guid ?? $item->id ?? '');

            // Derived
            $slug = Str::slug($title);

            // ----- Preserve existing row values where appropriate -----
   // Natural key
$key = [
    'user_id'      => $this->userId,
    'title'        => $title,
    'published_at' => $pubAt,
];

// Might be null on first import
$existing = \App\Models\Episode::query()->where($key)->first();

// Preserve-or-default values (all null-safe now)
$downloadsCount = $existing?->downloads_count ?? 0;
$commentsCount  = $existing?->comments_count  ?? 0;

$aiStatus   = $existing?->ai_status   ?? 'idle';
$aiProgress = $existing?->ai_progress ?? 0;
$aiMessage  = $existing?->ai_message  ?? null;

$audioPath  = $existing?->audio_path  ?? null;
$coverPath  = $existing?->cover_path  ?? null;
$imagePath  = $existing?->image_path  ?? null;

$chapters   = $existing?->chapters   ?? ($chaptersUrl   ?: null);
$transcript = $existing?->transcript ?? ($transcriptUrl ?: null);

$episodeNumber = $epNum ?? $existing?->episode_number ?? null;
$episodeNo     = $epNum ?? $existing?->episode_no     ?? null;

$durationSecCol  = $durationSeconds ?? $existing?->duration_sec     ?? null;
$durationSecsCol = $durationSeconds ?? $existing?->duration_seconds ?? null;

$status   = $existing?->status ?? 'published';
$explicit = $existing?->explicit ?? $explicitFlag;
$uuid     = $existing?->uuid ?? (string) \Illuminate\Support\Str::uuid();

$audioUrl      = $existing?->audio_url ?: ($enclosureUrl ?: null);
$imageUrlFinal = $imageUrl ?: ($existing?->image_url ?? null);

// Write ALL columns (user_id always present in key)
\Illuminate\Support\Facades\DB::table('episodes')->updateOrInsert(
    $key,
    [
        'slug'              => $existing?->slug ?? $slug,
        'description'       => $desc !== '' ? $desc : ($existing?->description ?? null),

        'audio_url'         => $audioUrl,
        'audio_bytes'       => $existing?->audio_bytes ?? $enclosureSize,
        'audio_path'        => $audioPath,

        'duration_seconds'  => $durationSecsCol,
        'duration_sec'      => $durationSecCol,

        'status'            => $status,
        'downloads_count'   => $downloadsCount,
        'comments_count'    => $commentsCount,

        'episode_number'    => $episodeNumber,
        'episode_type'      => $epType !== '' ? $epType : ($existing?->episode_type ?? 'full'),
        'explicit'          => $explicit,
        'season'            => $season ?? $existing?->season,
        'episode_no'        => $episodeNo,

        'image_url'         => $imageUrlFinal,
        'image_path'        => $imagePath,
        'cover_path'        => $coverPath,

        'chapters'          => $chapters,
        'transcript'        => $transcript,

        'ai_status'         => $aiStatus,
        'ai_progress'       => $aiProgress,
        'ai_message'        => $aiMessage,

        'uuid'              => $uuid,
        'guid'              => $guid !== '' ? $guid : ($existing?->guid ?? null),

        'created_at'        => $existing?->created_at ?? now(),
        'updated_at'        => now(),
    ]
);

// If enclosure appeared and DB had null audio_url, ensure it’s set
if ($enclosureUrl) {
    \App\Models\Episode::where($key)
        ->whereNull('audio_url')
        ->update(['audio_url' => $enclosureUrl, 'updated_at' => now()]);
}


            Log::info('[IMPORT] upserted episode (all fields)', [
                'user_id'      => $this->userId,
                'title'        => $title,
                'published_at' => $pubAt,
            ]);

            $this->tick($done, $total, 'Imported: '.$title);
        }

        $this->progress(100, 'Import complete ✔');
    }

    public function failed(Throwable $e): void
    {
        Log::error('ImportRssFeedJob failed', [
            'feed' => $this->feedUrl,
            'err'  => $e->getMessage(),
        ]);

        $this->progress(100, 'Failed: '.$e->getMessage());
    }

    /* ----------------- helpers ----------------- */

    private function progress(?int $percent, string $message): void
    {
        $current = Cache::get(self::PROGRESS_KEY, ['percent'=>0,'message'=>'…']);
        if ($percent === null) $percent = (int) ($current['percent'] ?? 0);
        Cache::put(self::PROGRESS_KEY, [
            'percent' => max(0, min(100, $percent)),
            'message' => $message,
            'meta'    => ['feed' => $this->feedUrl, 'user_id' => $this->userId],
        ], now()->addHour());
    }

    private function tick(int $done, int $total, string $message): void
    {
        $base  = 10;
        $range = 88;
        $pct   = $base + (int) floor($range * ($done / max(1,$total)));
        $this->progress($pct, $message);
    }

    private function parseDate(?string $str): ?string
    {
        if (!$str) return null;
        try {
            return date('Y-m-d H:i:s', strtotime($str));
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseItunesDuration(?string $s): ?int
    {
        if (!$s) return null;
        $s = trim($s);
        if ($s === '') return null;
        if (ctype_digit($s)) return (int)$s;              // seconds
        $parts = array_map('intval', explode(':', $s));   // HH:MM:SS or MM:SS
        if (count($parts) === 3) return $parts[0]*3600 + $parts[1]*60 + $parts[2];
        if (count($parts) === 2) return $parts[0]*60 + $parts[1];
        return null;
    }

    private function toIntOrNull(?string $s): ?int
    {
        if ($s === null) return null;
        $s = trim($s);
        return ($s !== '' && is_numeric($s)) ? (int)$s : null;
    }

    private function pickImageUrl($item, $itunes, $media = null): ?string
    {
        if ($itunes && isset($itunes->image)) {
            $a = $itunes->image->attributes();
            if (!empty($a['href'])) return (string)$a['href'];
        }
        if ($media && isset($media->thumbnail)) {
            $a = $media->thumbnail->attributes();
            if (!empty($a['url'])) return (string)$a['url'];
        }
        if ($media && isset($media->content)) {
            $a = $media->content->attributes();
            if (!empty($a['url'])) return (string)$a['url'];
        }
        return null;
    }
}
