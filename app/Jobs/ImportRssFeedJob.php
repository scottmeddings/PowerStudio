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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Models\SiteSetting;
use Throwable;

class ImportRssFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PROGRESS_KEY = 'rss_import:progress';

    public int $timeout = 300; // metadata-only now
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

        Log::info('[IMPORT] handle()', ['user_id'=>$this->userId, 'feed'=>$this->feedUrl, 'queue'=>$this->queue ?? 'default']);
        $this->progress(3, 'Fetching feed…');

        $resp = Http::retry(2, 150)->connectTimeout(5)->timeout(30)->get($this->feedUrl);
        if (!$resp->ok()) {
            throw new \RuntimeException('Feed fetch failed: HTTP '.$resp->status());
        }

        $xml = @simplexml_load_string($resp->body());
        if (!$xml) {
            throw new \RuntimeException('Invalid RSS/Atom XML.');
        }

        // Podcast title/description
        $feedTitle = (string)($xml->channel->title ?? $xml->title ?? '');
        $feedDesc  = (string)($xml->channel->description ?? $xml->subtitle ?? $xml->description ?? '');
        if ($feedTitle !== '' || $feedDesc !== '') {
            $this->updatePodcastSettings($this->userId, $feedTitle, $feedDesc);
        }

        // User cover (for default episode art)
        $userCoverPath = DB::table('users')->where('id', $this->userId)->value('cover_path');
        $userCoverUrl  = $userCoverPath ? $this->publicUrlForPath($userCoverPath) : null;

        // Collect feed items
        $items = [];
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $i) $items[] = $i;
        } elseif (isset($xml->entry)) {
            foreach ($xml->entry as $e) $items[] = $e;
        }

        if (count($items) === 0) {
            $this->progress(100, 'No items found.');
            return;
        }

        $this->progress(8, 'Parsing feed items… ('.count($items).')');

        // -------- 1) Parse to normalized array (metadata only) --------
        $parsed = [];
        $titles = [];
        $dates  = [];

        foreach ($items as $item) {
            $title = trim((string)($item->title ?? '')) ?: 'Untitled';
            $desc  = trim((string)($item->description ?? $item->summary ?? ''));

            $pub   = (string)($item->pubDate ?? $item->updated ?? $item->published ?? '');
            $pubAt = $this->parseDate($pub) ?? now()->format('Y-m-d H:i:s'); // deterministic key

            // Enclosure
            $enclosureUrl  = '';
            $enclosureSize = null;
            if (isset($item->enclosure)) {
                $a = $item->enclosure->attributes();
                $enclosureUrl = (string)($a['url'] ?? '');
                $len          = (string)($a['length'] ?? '');
                $enclosureSize = is_numeric($len) ? (int)$len : null;
            } else {
                foreach ($item->link ?? [] as $lnk) {
                    $a = $lnk->attributes();
                    if ((string)($a['rel'] ?? '') === 'enclosure') {
                        $enclosureUrl = (string)($a['href'] ?? '');
                        $len          = (string)($a['length'] ?? '');
                        $enclosureSize = is_numeric($len) ? (int)$len : null;
                        break;
                    }
                }
            }

            if ($title === '' && $enclosureUrl === '') {
                continue;
            }

            $itunes  = $item->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
            $pi      = $item->children('https://podcastindex.org/namespace/1.0');

            $durationSeconds = $this->parseItunesDuration((string)($itunes->duration ?? ''));
            $epNum           = $this->toIntOrNull((string)($itunes->episode ?? ''));
            $season          = $this->toIntOrNull((string)($itunes->season ?? ''));
            $epType          = (string)($itunes->episodeType ?? '') ?: 'full';
            $explicitStr     = strtolower(trim((string)($itunes->explicit ?? '')));
            $explicitFlag    = in_array($explicitStr, ['yes','true','explicit','1'], true) ? 1 : 0;

            $transcriptUrl = null;
            $chaptersUrl   = null;
            if ($pi) {
                if (isset($pi->transcript)) $transcriptUrl = (string)($pi->transcript->attributes()['url'] ?? null);
                if (isset($pi->chapters))   $chaptersUrl   = (string)($pi->chapters->attributes()['url'] ?? null);
            }

            $guid = (string)($item->guid ?? $item->id ?? '');
            $slug = Str::slug($title) ?: (string) Str::uuid();

            $parsed[] = [
                'title'          => $title,
                'desc'           => $desc,
                'pub_at'         => $pubAt,
                'slug'           => $slug,
                'guid_raw'       => $guid,
                'enclosure_url'  => $enclosureUrl ?: null,
                'enclosure_len'  => $enclosureSize,
                'duration_s'     => $durationSeconds,
                'ep_num'         => $epNum,
                'season'         => $season,
                'ep_type'        => $epType,
                'explicit'       => $explicitFlag,
                'chapters_url'   => $chaptersUrl,
                'transcript_url' => $transcriptUrl,
            ];

            $titles[$title] = true;
            $dates[$pubAt]  = true;
        }

        if (!$parsed) {
            $this->progress(100, 'No valid items to import.');
            return;
        }

        // -------- 2) Preload existing once, index by (title|published_at) --------
        $this->progress(12, 'Scanning existing episodes…');

        $existing = Episode::query()
            ->where('user_id', $this->userId)
            ->whereIn('title', array_keys($titles))
            ->whereIn('published_at', array_keys($dates))
            ->get([
                'id','title','published_at','slug','description',
                'duration_seconds','duration_sec','episode_number','episode_no',
                'season','episode_type','status','explicit',
                'downloads_count','comments_count',
                'ai_status','ai_progress','ai_message',
                'audio_url','audio_path','audio_bytes',
                'image_url','image_path','cover_path',
                'chapters','transcript','uuid','guid',
                'created_at','updated_at'
            ]);

        $index = [];
        foreach ($existing as $row) {
            $index[$row->title.'|'.$row->published_at] = $row;
        }

        // -------- 3) Build batch upserts (metadata only) --------
        $this->progress(18, 'Preparing batch upserts…');

        $now        = now();
        $batchSize  = 200;
        $totalItems = count($parsed);
        $processed  = 0;

        $updateCols = [
            'slug','description','audio_url','audio_bytes','audio_path',
            'duration_seconds','duration_sec','status','downloads_count','comments_count',
            'episode_number','episode_type','explicit','season','episode_no',
            'image_url','image_path','cover_path','chapters','transcript',
            'ai_status','ai_progress','ai_message','uuid','guid','updated_at'
        ];

        $toDispatch = []; // for asset download job

        foreach (array_chunk($parsed, $batchSize) as $chunk) {
            $rows = [];

            foreach ($chunk as $it) {
                $key = $it['title'].'|'.$it['pub_at'];
                $ex  = $index[$key] ?? null;

                $downloadsCount = $ex?->downloads_count ?? 0;
                $commentsCount  = $ex?->comments_count  ?? 0;

                $aiStatus   = $ex?->ai_status   ?? 'idle';
                $aiProgress = $ex?->ai_progress ?? 0;
                $aiMessage  = $ex?->ai_message  ?? null;

                $audioPath  = $ex?->audio_path  ?? null;
                $imagePath  = $ex?->image_path  ?? null;

                $chapters   = $ex?->chapters    ?? ($it['chapters_url']   ?: null);
                $transcript = $ex?->transcript  ?? ($it['transcript_url'] ?: null);

                $episodeNumber   = $it['ep_num']     ?? $ex?->episode_number ?? null;
                $episodeNo       = $it['ep_num']     ?? $ex?->episode_no     ?? null;
                $durationSecCol  = $it['duration_s'] ?? $ex?->duration_sec   ?? null;
                $durationSecsCol = $it['duration_s'] ?? $ex?->duration_seconds ?? null;

                $status   = $ex?->status   ?? 'published';
                $explicit = $ex?->explicit ?? $it['explicit'];
                $uuid     = $ex?->uuid     ?? (string) Str::uuid();

                $audioUrl = $ex?->audio_url ?? $it['enclosure_url'];

                $coverPathToUse = $ex?->cover_path ?: $userCoverPath;
                $imageUrlFinal  = $ex?->image_url  ?: $userCoverUrl;

                $rows[] = [
                    'user_id'          => $this->userId,
                    'title'            => $it['title'],
                    'published_at'     => $it['pub_at'],
                    'slug'             => $ex?->slug ?? $it['slug'],
                    'description'      => $it['desc'] !== '' ? $it['desc'] : ($ex?->description ?? null),

                    'audio_url'        => $audioUrl,
                    'audio_bytes'      => $ex?->audio_bytes ?? $it['enclosure_len'],
                    'audio_path'       => $audioPath,

                    'duration_seconds' => $durationSecsCol,
                    'duration_sec'     => $durationSecCol,

                    'status'           => $status,
                    'downloads_count'  => $downloadsCount,
                    'comments_count'   => $commentsCount,

                    'episode_number'   => $episodeNumber,
                    'episode_type'     => $it['ep_type'] ?: ($ex?->episode_type ?? 'full'),
                    'explicit'         => $explicit,
                    'season'           => $it['season'] ?? $ex?->season,
                    'episode_no'       => $episodeNo,

                    'image_url'        => $imageUrlFinal,
                    'image_path'       => $imagePath,
                    'cover_path'       => $coverPathToUse,

                    'chapters'         => $chapters,
                    'transcript'       => $transcript,

                    'ai_status'        => $aiStatus,
                    'ai_progress'      => $aiProgress,
                    'ai_message'       => $aiMessage,

                    'uuid'             => $uuid,
                    // temporary; canonical GUID rewrite happens in asset job
                    'guid'             => $it['guid_raw'] !== '' ? $it['guid_raw'] : ($ex?->guid ?? null),

                    'created_at'       => $ex?->created_at ?? $now,
                    'updated_at'       => $now,
                ];

                $toDispatch[] = [
                    'title'          => $it['title'],
                    'published_at'   => $it['pub_at'],
                    'slug'           => $it['slug'],
                    'enclosure_url'  => $it['enclosure_url'],
                    'chapters_url'   => $it['chapters_url'],
                    'transcript_url' => $it['transcript_url'],
                ];
            }

            // SQLite-friendly upsert (falls back to updateOrInsert if no UNIQUE index)
            $this->safeUpsertEpisodes($rows, ['user_id','title','published_at'], $updateCols);

            $processed += count($rows);
            $this->progress(18 + (int)floor(60 * ($processed / max(1, $totalItems))), "Imported metadata for {$processed}/{$totalItems}…");
        }

        // -------- 4) Map ids in one shot & queue asset jobs --------
        $this->progress(80, 'Queuing downloads…');

        $titlesSet = array_values(array_unique(array_column($toDispatch, 'title')));
        $datesSet  = array_values(array_unique(array_column($toDispatch, 'published_at')));

        if ($titlesSet && $datesSet) {
            $rows = Episode::query()
                ->where('user_id', $this->userId)
                ->whereIn('title', $titlesSet)
                ->whereIn('published_at', $datesSet)
                ->get(['id','title','published_at','slug']);

            $idIndex = [];
            foreach ($rows as $r) $idIndex[$r->title.'|'.$r->published_at] = ['id'=>$r->id,'slug'=>$r->slug];

            foreach ($toDispatch as $p) {
                $idx = $idIndex[$p['title'].'|'.$p['published_at']] ?? null;
                if (!$idx) {
                    Log::warning('[IMPORT] could not re-find episode after upsert', [
                        'user_id'=>$this->userId,'title'=>$p['title'],'published_at'=>$p['published_at']
                    ]);
                    continue;
                }

                DownloadEpisodeAssetsJob::dispatch(
                    episodeId:    $idx['id'],
                    userId:       $this->userId,
                    enclosureUrl: $p['enclosure_url'],
                    chaptersUrl:  $p['chapters_url'],
                    transcriptUrl:$p['transcript_url'],
                    slug:         $idx['slug'] ?: $p['slug']
                )->onQueue('io'); // use a separate worker pool if you like
            }
        }

        $this->progress(100, 'Import complete ✔ (assets queued)');
    }

    public function failed(Throwable $e): void
    {
        Log::error('ImportRssFeedJob failed', ['feed' => $this->feedUrl, 'err' => $e->getMessage()]);
        $this->progress(100, 'Failed: '.$e->getMessage());
    }

    /* ----------------- helpers ----------------- */

    private function progress(?int $percent, string $message): void
    {
        static $lastWrite = 0;
        $nowMs = (int)(microtime(true) * 1000);
        if ($percent !== 100 && $nowMs - $lastWrite < 200) return;
        $lastWrite = $nowMs;

        $current = Cache::get(self::PROGRESS_KEY, ['percent'=>0,'message'=>'…']);
        if ($percent === null) $percent = (int) ($current['percent'] ?? 0);
        Cache::put(self::PROGRESS_KEY, [
            'percent' => max(0, min(100, $percent)),
            'message' => $message,
            'meta'    => ['feed' => $this->feedUrl, 'user_id' => $this->userId],
        ], now()->addHour());
    }

    private function parseDate(?string $str): ?string
    {
        if (!$str) return null;
        try { return date('Y-m-d H:i:s', strtotime($str)); } catch (\Throwable) { return null; }
    }

    private function parseItunesDuration(?string $s): ?int
    {
        if (!$s) return null;
        $s = trim($s);
        if ($s === '') return null;
        if (ctype_digit($s)) return (int)$s;
        $parts = array_map('intval', explode(':', $s));
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

    private function publicUrlForPath(string $path): string
    {
        $disk = config('filesystems.default', 'public');
        $url  = \Storage::disk($disk)->url($path);
        return str_starts_with($url, 'http') ? $url : URL::to($url);
    }

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

    /**
     * Upsert rows chunk with fallback for SQLite (no UNIQUE index).
     */
    private function safeUpsertEpisodes(array $rows, array $uniqueBy, array $updateCols): void
    {
        try {
            Episode::upsert($rows, $uniqueBy, $updateCols);
            return;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $isConflict = str_contains($msg, 'ON CONFLICT') || str_contains($msg, 'constraint');
            if (!$isConflict) throw $e;

            foreach ($rows as $r) {
                $key  = array_intersect_key($r, array_flip($uniqueBy));
                $data = $r; unset($data['created_at']); // preserve created_at on updates
                DB::table('episodes')->updateOrInsert($key, $data);
            }
            Log::warning('[IMPORT] Using updateOrInsert fallback (no UNIQUE index for upsert).');
        }
    }
}
