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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Models\SiteSetting;
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

        // Update podcast title/description in settings
        $feedTitle = (string)($xml->channel->title ?? $xml->title ?? '');
        $feedDesc  = (string)($xml->channel->description ?? $xml->subtitle ?? $xml->description ?? '');
        if ($feedTitle !== '' || $feedDesc !== '') {
            $this->updatePodcastSettings($this->userId, $feedTitle, $feedDesc);
        }

        // ⬇️ Fetch user's cover once; reuse for all episodes
        $userCoverPath = DB::table('users')->where('id', $this->userId)->value('cover_path');
        $userCoverUrl  = $userCoverPath ? $this->publicUrlFor($userCoverPath) : null;

        // Items
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
            $pubAt = $this->parseDate($pub);

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

            // PodcastIndex (transcript/chapters)
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

            $guid = (string)($item->guid ?? $item->id ?? '');
            $slug = Str::slug($title);

            // Natural key (keeps user_id on first insert)
            $key = [
                'user_id'      => $this->userId,
                'title'        => $title,
                'published_at' => $pubAt,
            ];

            $existing = Episode::query()->where($key)->first();

            $downloadsCount = $existing?->downloads_count ?? 0;
            $commentsCount  = $existing?->comments_count  ?? 0;

            $aiStatus   = $existing?->ai_status   ?? 'idle';
            $aiProgress = $existing?->ai_progress ?? 0;
            $aiMessage  = $existing?->ai_message  ?? null;

            $audioPath  = $existing?->audio_path  ?? null;

            // Keep any episode-specific image_path if already set.
            $imagePath  = $existing?->image_path  ?? null;

            // Seed chapters/transcript with feed links if DB empty (will be replaced when downloaded)
            $chapters   = $existing?->chapters   ?? ($chaptersUrl   ?: null);
            $transcript = $existing?->transcript ?? ($transcriptUrl ?: null);

            $episodeNumber   = $epNum ?? $existing?->episode_number ?? null;
            $episodeNo       = $epNum ?? $existing?->episode_no     ?? null;
            $durationSecCol  = $durationSeconds ?? $existing?->duration_sec     ?? null;
            $durationSecsCol = $durationSeconds ?? $existing?->duration_seconds ?? null;

            $status   = $existing?->status ?? 'published';
            $explicit = $existing?->explicit ?? $explicitFlag;
            $uuid     = $existing?->uuid ?? (string) Str::uuid();

            $audioUrl = $existing?->audio_url ?: ($enclosureUrl ?: null);

            // ⬇️ Copy user's cover into episode fields (no download)
            // - cover_path (DB raw path from users.cover_path)
            // - image_url  (public URL for that path)
            $coverPathToUse = $userCoverPath ?: ($existing?->cover_path ?? null);
            $imageUrlFinal  = $userCoverUrl  ?: ($existing?->image_url ?? null);

            // Upsert base fields
            DB::table('episodes')->updateOrInsert(
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

                    // ⬇️ set from user's cover
                    'image_url'         => $imageUrlFinal,
                    'image_path'        => $imagePath,
                    'cover_path'        => $coverPathToUse,

                    'chapters'          => $chapters,
                    'transcript'        => $transcript,

                    'ai_status'         => $aiStatus,
                    'ai_progress'       => $aiProgress,
                    'ai_message'        => $aiMessage,

                    'uuid'              => $uuid,
                    // temporary; we’ll rewrite after we know the row id/slug
                    'guid'              => $guid !== '' ? $guid : ($existing?->guid ?? null),

                    'created_at'        => $existing?->created_at ?? now(),
                    'updated_at'        => now(),
                ]
            );

            // If enclosure appeared and DB had null audio_url, seed it
            if ($enclosureUrl) {
                Episode::where($key)
                    ->whereNull('audio_url')
                    ->update(['audio_url' => $enclosureUrl, 'updated_at' => now()]);
            }

            // Reload (we need the id for URL building)
            $row = Episode::where($key)->first();

            // ---------- Download and relink: audio ----------
            if ($row && empty($row->audio_path) && !empty($audioUrl)) {
                try {
                    $dl = $this->downloadAudioToStorage(
                        url: $audioUrl,
                        userId: $this->userId,
                        title: $title,
                        publishedAt: $pubAt
                    );

                    DB::table('episodes')
                        ->where($key)
                        ->update([
                            'audio_path'  => $dl['path'],
                            'audio_bytes' => $dl['bytes'],
                            'audio_url'   => $dl['url'], // point to our site’s public URL
                            'updated_at'  => now(),
                        ]);
                } catch (\Throwable $e) {
                    Log::warning('[IMPORT] audio download failed', [
                        'user_id' => $this->userId,
                        'title'   => $title,
                        'url'     => $audioUrl,
                        'error'   => $e->getMessage(),
                    ]);
                }
            } elseif ($row && !empty($row->audio_path)) {
                // Ensure audio_url points to our local file URL
                $localUrl = $this->publicUrlFor($row->audio_path);
                if (empty($row->audio_url) || $this->isExternalUrl($row->audio_url)) {
                    Episode::where($key)->update(['audio_url' => $localUrl, 'updated_at' => now()]);
                }
            }

            // ---------- Download and relink: chapters (JSON) ----------
            if ($row && !empty($chaptersUrl)) {
                try {
                    $chapRel = $this->relStoragePath($this->userId, $title, $pubAt, 'chapters.json');
                    $chapUrl = $this->downloadToStorageReturnUrl($chaptersUrl, $chapRel);
                    Episode::where($key)->update(['chapters' => $chapUrl, 'updated_at' => now()]);
                } catch (\Throwable $e) {
                    Log::warning('[IMPORT] chapters download failed', ['url' => $chaptersUrl, 'err' => $e->getMessage()]);
                }
            }

            // ---------- Download and relink: transcript (VTT) ----------
            if ($row && !empty($transcriptUrl)) {
                try {
                    $ext = strtolower(pathinfo(parse_url($transcriptUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                    $ext = in_array($ext, ['vtt','srt','txt','json']) ? $ext : 'vtt';
                    $trRel = $this->relStoragePath($this->userId, $title, $pubAt, "transcript.{$ext}");
                    $trUrl = $this->downloadToStorageReturnUrl($transcriptUrl, $trRel);
                    Episode::where($key)->update(['transcript' => $trUrl, 'updated_at' => now()]);
                } catch (\Throwable $e) {
                    Log::warning('[IMPORT] transcript download failed', ['url' => $transcriptUrl, 'err' => $e->getMessage()]);
                }
            }

            // ---------- Canonical GUID: prefix with website URL ----------
            if ($row) {
                $guidToStore = $row->guid;
                if (!$this->looksAbsoluteUrl($guidToStore)) {
                    $episodeUrl = $this->episodePublicUrl($row->id, $slug);
                    $guidToStore = $episodeUrl . ($row->guid ? ('#' . rawurlencode($row->guid)) : '');
                }
                if ($guidToStore !== $row->guid) {
                    Episode::where($key)->update(['guid' => $guidToStore, 'updated_at' => now()]);
                }
            }

            Log::info('[IMPORT] upserted episode', [
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

    /**
     * Download audio to storage and return: ['path'=>string,'bytes'=>int,'mime'=>?string,'url'=>string]
     */
    private function downloadAudioToStorage(string $url, int $userId, string $title, ?string $publishedAt): array
    {
        $tmp = tmpfile();
        if ($tmp === false) throw new \RuntimeException('Failed to create temporary file.');
        $meta    = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'];

        $req = Http::timeout(600)
            ->withHeaders([
                'User-Agent' => 'PodPowerImporter/1.0',
                'Accept'     => 'audio/*, application/octet-stream, */*',
            ])
            ->sink($tmpPath)
            ->get($url);

        if (!$req->ok()) { fclose($tmp); throw new \RuntimeException('Download failed: HTTP '.$req->status()); }

        $bytes = @filesize($tmpPath) ?: 0;
        $mime  = $req->header('Content-Type');

        $ext  = $this->guessAudioExtension($mime, $url) ?? 'mp3';
        $relPath = $this->relStoragePath($userId, $title, $publishedAt, "audio.{$ext}");

        $disk = config('filesystems.default', 'public');
        $dir  = dirname($relPath);
        if (!Storage::disk($disk)->exists($dir)) Storage::disk($disk)->makeDirectory($dir);
        rewind($tmp);
        $ok = Storage::disk($disk)->put($relPath, $tmp); // stream write
        fclose($tmp);
        if (!$ok) throw new \RuntimeException('Failed to write audio to storage.');

        return ['path' => $relPath, 'bytes' => $bytes, 'mime' => $mime ?: null, 'url' => $this->publicUrlFor($relPath)];
    }

    /** Generic remote download -> storage, returns public URL. */
    private function downloadToStorageReturnUrl(string $sourceUrl, string $relPath): string
    {
        $tmp = tmpfile();
        if ($tmp === false) throw new \RuntimeException('tmpfile() failed');
        $meta = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'];

        $req = Http::timeout(180)->sink($tmpPath)->get($sourceUrl);
        if (!$req->ok()) { fclose($tmp); throw new \RuntimeException('Download failed: HTTP '.$req->status()); }

        $disk = config('filesystems.default', 'public');
        $dir  = dirname($relPath);
        if (!Storage::disk($disk)->exists($dir)) Storage::disk($disk)->makeDirectory($dir);
        rewind($tmp);
        $ok = Storage::disk($disk)->put($relPath, $tmp);
        fclose($tmp);
        if (!$ok) throw new \RuntimeException('Failed to write file to storage.');

        return $this->publicUrlFor($relPath);
    }

    /** Build a relative storage path like episodes/{user}/YYYY/MM/{slug_date}/{filename} */
    private function relStoragePath(int $userId, string $title, ?string $publishedAt, string $filename): string
    {
        $ym = date('Y/m', $publishedAt ? strtotime($publishedAt) : time());
        $dateKey = $publishedAt ?: now()->format('Y-m-d H:i:s');
        $dateForName = str_replace([' ', ':'], ['_', '-'], $dateKey);
        $baseName = Str::slug($title) ?: 'episode';
        $bucket = "{$baseName}_{$dateForName}";
        return "episodes/{$userId}/{$ym}/{$bucket}/{$filename}";
    }

    /** Map common audio MIME types to extensions; fall back to URL path. */
    private function guessAudioExtension(?string $mime, string $url): ?string
    {
        $map = [
            'audio/mpeg'  => 'mp3',
            'audio/mp3'   => 'mp3',
            'audio/mp4'   => 'm4a',
            'audio/x-m4a' => 'm4a',
            'audio/aac'   => 'aac',
            'audio/wav'   => 'wav',
            'audio/x-wav' => 'wav',
            'audio/ogg'   => 'ogg',
            'audio/opus'  => 'opus',
            'audio/webm'  => 'webm',
            'application/octet-stream' => null,
        ];
        if ($mime && isset($map[$mime])) return $map[$mime] ?: null;

        $path = parse_url($url, PHP_URL_PATH);
        $ext  = $path ? strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) : null;
        return in_array($ext, ['mp3','m4a','aac','wav','ogg','opus','webm'], true) ? $ext : null;
    }

    /** Build an absolute public URL for a storage path on the default disk. */
    private function publicUrlFor(string $path): string
    {
        $disk = config('filesystems.default', 'public');
        $url  = Storage::disk($disk)->url($path);    // S3 returns absolute
        return str_starts_with($url, 'http') ? $url : URL::to($url);
    }

    /** True if URL is absolute (has scheme). */
    private function looksAbsoluteUrl(?string $url): bool
    {
        if (!$url) return false;
        return (bool) preg_match('~^https?://~i', $url);
    }

    /** True if URL points off-site. */
    private function isExternalUrl(?string $url): bool
    {
        if (!$url) return true;
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return true;
        $app  = parse_url(config('app.url', URL::to('/')), PHP_URL_HOST);
        return !($app && strtolower($host) === strtolower($app));
    }

    /** Canonical episode page URL under our site. */
    private function episodePublicUrl(int $id, string $slug): string
    {
        return URL::to("/episodes/{$id}-{$slug}");
    }

    /** Persist podcast title/description (singleton-aware). */
    private function updatePodcastSettings(int $userId, ?string $title, ?string $desc): void
    {
        $title = trim((string) $title);
        $desc  = (string) $desc;

        $table = config('podpower.settings_table', 'settings');
        $isSingleton = Schema::hasColumn($table, 'singleton')
                    && class_exists(SiteSetting::class)
                    && method_exists(SiteSetting::class, 'singleton');

        if ($isSingleton) {
            $s = SiteSetting::singleton();
            if ($title !== '') { $s->site_title = $title; }
            if ($desc  !== '') { $s->site_desc  = $desc;  }
            $s->save();
            return;
        }

        $now     = now();
        $hasUser = Schema::hasColumn($table, 'user_id');
        $scope   = $hasUser ? ['user_id' => $userId] : [];

        if ($title !== '') {
            DB::table($table)->updateOrInsert(
                array_merge($scope, ['key' => 'podcast_title']),
                ['value' => $title, 'updated_at' => $now, 'created_at' => $now]
            );
        }
        if ($desc !== '') {
            DB::table($table)->updateOrInsert(
                array_merge($scope, ['key' => 'podcast_description']),
                ['value' => $desc, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
