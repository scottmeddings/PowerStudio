<?php

namespace App\Jobs;

use App\Models\Episode;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;            // ✅ correct import
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;          // ✅ for saving processed audio
use Throwable;

class EnhanceEpisodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Kill the worker if it runs longer than this (seconds) */
    public $timeout = 120;
    /** Number of times the job may be attempted. */
    public $tries = 1;

    public function __construct(public int $episodeId) {}

    /* ----------------- Cache key helpers (must match controller) ----------------- */
    private function progressKey(): string { return "ai:{$this->episodeId}:progress"; }
    private function cancelKey(): string   { return "ai:{$this->episodeId}:cancel"; }
    private function pidKey(): string      { return "ai:{$this->episodeId}:pid";   }

    public function handle(): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $episode = Episode::findOrFail($this->episodeId);
        $local = $downsampled = null;

        Log::info('EnhanceEpisode start', [
            'episode_id' => $this->episodeId,
            'attempt'    => method_exists($this, 'attempts') ? $this->attempts() : null,
        ]);

        try {
            $this->tick(5,  'downloading',  'Downloading audio…');
            $this->abortIfCanceled();

            $local = $this->resolveLocalAudioFromEpisode($episode);

            $this->tick(15, 'downsampling', 'Optimizing audio…');
            $this->abortIfCanceled();
            $downsampled = $this->downsample($local); // stores ffmpeg PID in cache during run

            // ✅ Persist processed audio and update episode URL/path (before transcription)
            $this->persistProcessedAudio($episode, $downsampled, $local);

            $this->tick(60, 'transcribing', 'Transcribing with Whisper…');
            $this->abortIfCanceled();
            $transcription = $this->transcribeWithOpenAI($downsampled);

            $text = (string)($transcription['text'] ?? '');
            $durationMs = isset($transcription['duration'])
                ? (int) round(((float)$transcription['duration']) * 1000)
                : null;

            $this->tick(80, 'summarizing', 'Generating title, description & chapters…');
            $this->abortIfCanceled();
            [$title, $description, $chapters] = $this->summarizeFromTranscript($text);

            // Save transcript
            $this->tick(92, 'saving', 'Saving transcript…');
            $episode->transcript()->updateOrCreate([], [
                'format'       => 'txt',
                'body'         => $text,
                'duration_ms'  => $durationMs,
                'storage_path' => null,
            ]);

            // Save episode fields
            $this->tick(96, 'saving', 'Saving episode details…');
            if (!empty($title))       { $episode->title       = $title; }
            if (!empty($description)) { $episode->description = $description; }
            $episode->save();

            // Save chapters (replace existing)
            $this->tick(98, 'saving', 'Saving chapter markers…');
            if (is_array($chapters)) {
                $episode->chapters()->delete();

                $useMs = Schema::hasColumn('episode_chapters', 'starts_at_ms');
                $useS  = Schema::hasColumn('episode_chapters', 'starts_at');

                $sort = 1;
                foreach ($chapters as $c) {
                    $startSec = (int) max(0, (int) ($c['start'] ?? $c['startTime'] ?? 0));
                    $titleIn  = trim((string)($c['title'] ?? ''));
                    $row = [
                        'episode_id' => $episode->id,
                        'sort'       => $sort++,
                        'title'      => ($titleIn !== '') ? $titleIn : 'Chapter',
                    ];

                    if ($useMs) {
                        $row['starts_at_ms'] = $startSec * 1000;
                    } elseif ($useS) {
                        $row['starts_at'] = $startSec;
                    } else {
                        // fallback if schema unexpected
                        $row['starts_at_ms'] = 0;
                    }

                    \App\Models\EpisodeChapter::create($row);
                }
            }

            $this->tick(100, 'done', 'Finished!');
        } catch (Throwable $e) {
            if ($this->isCanceled()) {
                $this->tick(0, 'canceled', 'Canceled by user.');
                return;
            }
            Log::error('EnhanceEpisodeJob failed', [
                'episode_id' => $this->episodeId,
                'error'      => $e->getMessage(),
            ]);
            $this->tick(0, 'failed', 'AI enhancement failed: '.$e->getMessage());
            throw $e;
        } finally {
            Cache::forget($this->pidKey());

            // ✅ Only delete temp files (avoid deleting real /public files)
            $isTmp = static function (?string $p): bool {
                if (!$p) return false;
                $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $rp  = realpath($p) ?: $p;
                return str_starts_with($rp, $tmp);
            };

            if ($downsampled && is_file($downsampled) && $isTmp($downsampled)) @unlink($downsampled);
            if ($local && is_file($local) && $isTmp($local)) @unlink($local);
        }
    }

    /* ---------------------- Progress / Cancel helpers ---------------------- */
    private function resolveLocalAudioFromEpisode(Episode $episode): string
    {
        // 1) Prefer path on public disk
        if (!empty($episode->audio_path)) {
            $p = Storage::disk('public')->path($episode->audio_path);
            if (is_file($p)) {
                \Log::info('resolveLocalAudioFromEpisode: using disk path', ['path' => $p]);
                return $p;
            }
            \Log::warning('resolveLocalAudioFromEpisode: audio_path missing on disk', ['path' => $p]);
        }

        // 2) Fallback to URL mapping (no HTTP)
        if (!empty($episode->audio_url)) {
            return $this->resolveLocalAudio($episode->audio_url);
        }

        throw new \RuntimeException('Episode has no audio_path or audio_url.');
    }

    private function tick(int $pct, string $status, string $msg): void
    {
        $pct = max(0, min(100, $pct));
        Cache::put($this->progressKey(), [
            'status'   => $status,
            'progress' => $pct,
            'message'  => $msg,
        ], now()->addMinutes(30));

        // Mirror to DB if columns exist
        try {
            Episode::whereKey($this->episodeId)->update([
                'ai_status'   => $status,
                'ai_progress' => $pct,
                'ai_message'  => $msg,
            ]);
        } catch (\Throwable $ignored) {}
    }

    private function isCanceled(): bool
    {
        return (bool) Cache::get($this->cancelKey(), false);
    }

    private function abortIfCanceled(): void
    {
        if ($this->isCanceled()) {
            throw new \RuntimeException('Canceled by user');
        }
    }

    /* ---------------------- IO helpers ---------------------- */
    private function resolveLocalAudio(string $input): string
    {
        if (!$input) {
            throw new \RuntimeException('No audio URL/path provided.');
        }

        // 1) Already a real filesystem path?
        if (is_file($input)) {
            return $input;
        }

        // 2) /storage/... (public symlink) → real path(s)
        if (str_starts_with($input, '/storage/')) {
            $publicPath = public_path(ltrim($input, '/')); // public/storage/...
            if (is_file($publicPath)) return $publicPath;

            $relative = ltrim(Str::after($input, '/storage/'), '/');
            if (Storage::disk('public')->exists($relative)) {
                return Storage::disk('public')->path($relative);
            }

            \Log::warning('resolveLocalAudio: /storage path not found', ['input' => $input]);
            throw new \RuntimeException('Audio path not found: '.$input);
        }

        // 3) Relative path on the public disk?
        if (!preg_match('#^https?://#i', $input) && !str_starts_with($input, DIRECTORY_SEPARATOR)) {
            if (Storage::disk('public')->exists($input)) {
                return Storage::disk('public')->path($input);
            }
        }

        // 4) Full URL → try mapping if it’s a /storage URL on this host
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            $path = parse_url($input, PHP_URL_PATH) ?: '';

            // Map http(s)://<host>/storage/... to disk; DO NOT fetch over HTTP
            if (str_starts_with($path, '/storage/')) {
                $publicPath = public_path(ltrim($path, '/'));
                if (is_file($publicPath)) {
                    \Log::info('resolveLocalAudio: mapped URL to public file', ['url' => $input, 'path' => $publicPath]);
                    return $publicPath;
                }
                $relative = ltrim(Str::after($path, '/storage/'), '/');
                if (Storage::disk('public')->exists($relative)) {
                    $real = Storage::disk('public')->path($relative);
                    \Log::info('resolveLocalAudio: mapped URL to storage disk file', ['url' => $input, 'path' => $real]);
                    return $real;
                }

                \Log::warning('resolveLocalAudio: /storage URL did not map to file', ['url' => $input]);
                throw new \RuntimeException('Audio path not found: '.$input);
            }

            // For any other remote URL, actually download to temp (non-localhost)
            $host = parse_url($input, PHP_URL_HOST);
            $localish = static fn($h) => in_array($h, ['localhost','127.0.0.1','::1'], true);
            if ($localish($host)) {
                // Don’t try to HTTP fetch from localhost in a worker
                throw new \RuntimeException('Localhost URL not reachable from worker: '.$input);
            }

            $tmp = sys_get_temp_dir() . '/ep_' . Str::uuid() . '.mp3';
            $lastTick = 0.0;

            try {
                Http::withOptions([
                        'sink'     => $tmp,
                        'progress' => function ($dlTotal, $dlNow) use (&$lastTick) {
                            $now = microtime(true);
                            if (($now - $lastTick) < 0.75) return;
                            $lastTick = $now;

                            $pct = 5;
                            if ($dlTotal > 0) {
                                $t   = max(0, min(1, $dlNow / $dlTotal));
                                $pct = (int) round(5 + 10 * $t);
                            }

                            $fmt = function ($b) {
                                $u = ['B','KB','MB','GB']; $i = 0;
                                while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
                                return ($b >= 100 ? number_format($b, 0)
                                                  : ($b >= 10 ? number_format($b, 1)
                                                              : number_format($b, 2))) . ' ' . $u[$i];
                            };

                            $msg = 'Downloading audio… ' . $fmt((float) $dlNow);
                            if ($dlTotal > 0) { $msg .= ' / ' . $fmt((float) $dlTotal); }

                            $this->tick($pct, 'downloading', $msg);
                            if ($this->isCanceled()) throw new \RuntimeException('Canceled by user');
                        },
                        'allow_redirects' => true,
                    ])
                    ->connectTimeout(60)
                    ->timeout(900)
                    ->retry(2, 5000)
                    ->get($input)
                    ->throw();
            } catch (\Throwable $e) {
                @is_file($tmp) && @unlink($tmp);
                throw new \RuntimeException('Failed to download audio (network or URL issue).');
            }

            if (!is_file($tmp)) throw new \RuntimeException('Unable to download audio file.');
            return $tmp;
        }

        // 5) Unknown input
        throw new \RuntimeException('Audio path not found: '.$input);
    }

    /**
     * Downsample to mono 16kHz ~64kbps using ffmpeg if available. Falls back to original on failure.
     * Stores the ffmpeg PID in cache so the controller can kill it immediately on cancel.
     */
    private function downsample(string $src): string
    {
        $enabled = filter_var(env('AI_DOWNSAMPLE', true), FILTER_VALIDATE_BOOL);
        if (!$enabled) {
            Log::info('Downsample skipped via AI_DOWNSAMPLE=0');
            return $src;
        }

        $ffmpeg = $this->findFfmpeg();
        if (!$ffmpeg) {
            Log::warning('ffmpeg not found; skipping downsample');
            return $src;
        }

        $dst = sys_get_temp_dir() . '/ep_ds_' . Str::uuid() . '.mp3';
        $timeoutSec = (int) env('FFMPEG_TIMEOUT', 600);

        $duration = $this->probeDurationSec($src);
        if ($duration === null) {
            Log::info('ffprobe not found or no duration; using heartbeat progress for downsample');
        }

        $args = [
            $ffmpeg,
            '-nostdin', '-hide_banner',
            '-loglevel', 'error',
            '-y',
            '-i', $src,
            '-ac', '1',
            '-ar', '16000',
            '-b:a', '64k',
            '-progress', 'pipe:2',
            $dst,
        ];

        $proc = new Process($args);
        $proc->setTimeout(null);
        $proc->start();

        // ✅ store PID for cancel
        try {
            if (method_exists($proc, 'getPid')) {
                Cache::put($this->pidKey(), $proc->getPid(), now()->addMinutes(30));
            }
        } catch (\Throwable $ignored) {}

        $start = microtime(true);
        $lastBeat = 0.0;
        $stderrBuf = '';

        while ($proc->isRunning()) {
            if ($this->isCanceled()) {
                $proc->stop(0);
                throw new \RuntimeException('Canceled by user');
            }

            if ((microtime(true) - $start) > $timeoutSec) {
                Log::warning('ffmpeg timed out; killing process', ['timeout' => $timeoutSec]);
                $proc->stop(0);
                break;
            }

            $chunk = $proc->getIncrementalErrorOutput();
            if ($chunk !== '') {
                $stderrBuf .= $chunk;
                if ($duration && preg_match_all('/out_time_ms=(\d+)/', $chunk, $m) && !empty($m[1])) {
                    $ms  = (int) end($m[1]);
                    $t   = max(0.0, min(1.0, ($ms / 1_000_000.0) / $duration));
                    $pct = 15 + (int) round(40 * $t); // 15 → 55
                    $this->tick($pct, 'downsampling', 'Optimizing audio…');
                }
            } else {
                $now = microtime(true);
                if ($now - $lastBeat > 1.0) {
                    $lastBeat = $now;
                    static $p = 15; $p = min(55, $p + 1);
                    $this->tick($p, 'downsampling', 'Optimizing audio…');
                }
            }

            usleep(150_000);
        }

        $exit = $proc->getExitCode();
        if ($exit === 0 && is_file($dst)) return $dst;

        Log::warning('ffmpeg downsample failed; using source file', [
            'exit_code'   => $exit,
            'stderr_tail' => substr($stderrBuf, -2000),
        ]);

        return $src;
    }

    /**
     * Persist a processed audio file to the public disk and update episode fields.
     * If $processed === $original, we assume no new file was produced and skip.
     */
    private function persistProcessedAudio(Episode $episode, string $processed, string $original): void
{
    $rpProcessed = realpath($processed) ?: $processed;
    $rpOriginal  = realpath($original)  ?: $original;

    // If downsample failed or just returned the original file, skip persisting.
    if (!is_file($rpProcessed) || $rpProcessed === $rpOriginal) {
        \Log::info('persistProcessedAudio: no new file to persist', [
            'episode_id' => $episode->id,
            'processed'  => $rpProcessed,
            'original'   => $rpOriginal,
        ]);
        return;
    }

    // Store under public disk
    $rel = 'episodes/'.$episode->id.'/enhanced_'.Str::uuid().'.mp3';

    $in = @fopen($rpProcessed, 'rb');
    if ($in === false) {
        throw new \RuntimeException('Unable to open processed audio for saving.');
    }

    try {
        Storage::disk('public')->put($rel, $in, [
            'visibility' => 'public',
            'mimetype'   => 'audio/mpeg',
        ]);
    } finally {
        @fclose($in);
    }

    // Build a URL that respects the configured disk
    $url = Storage::disk('public')->url($rel); // usually /storage/...

    // 🔒 Update the DB atomically to avoid stale model overwrites later
    $affected = Episode::whereKey($episode->id)->update([
        'audio_path' => $rel,
        'audio_url'  => $url,
        'updated_at' => now(), // in case timestamps are relied on elsewhere
    ]);

    // Keep in-memory model in sync for any subsequent saves in this job
    $episode->forceFill([
        'audio_path' => $rel,
        'audio_url'  => $url,
    ])->syncOriginal(); // marks attributes as clean so later save() won't revert them

    // Or, if you prefer to re-query from DB:
    // $episode->refresh();

    \Log::info('Processed audio persisted', [
        'episode_id' => $episode->id,
        'audio_path' => $rel,
        'audio_url'  => $url,
        'affected'   => $affected,
    ]);

    if ($affected !== 1) {
        \Log::warning('persistProcessedAudio: DB update did not affect 1 row', [
            'episode_id' => $episode->id,
            'affected'   => $affected,
        ]);
    }
}


    /**
     * Call OpenAI Whisper with generous timeouts to avoid cURL 28 on large files.
     */
    private function transcribeWithOpenAI(string $localPath): array
    {
        $key = $this->requireOpenAIKey();

        $fh = fopen($localPath, 'rb');
        if (!$fh) throw new \RuntimeException('Unable to open audio file for transcription.');

        try {
            $resp = Http::withToken($key)
                ->asMultipart()
                ->connectTimeout(60)
                ->timeout(1800)
                ->retry(2, 8000)
                ->attach('file', $fh, basename($localPath))
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model'           => 'whisper-1',
                    'response_format' => 'verbose_json',
                    'temperature'     => 0.2,
                ]);

            $resp->throw();
            return $resp->json();
        } finally {
            fclose($fh); // ✅ avoid handle leak on Windows
        }
    }

    /**
     * Ask the LLM to create title/description/chapters (JSON) from transcript text.
     */
    private function summarizeFromTranscript(string $text): array
    {
        if (trim($text) === '') return [null, null, []];

        $prompt = <<<PROMPT
You are a podcast editor. Based on the transcript, produce:
1) A compelling episode title (<= 80 chars)
2) A short episode description (2–3 sentences, <= 500 chars)
3) A JSON array of chapter markers, each: { "start": seconds(int), "title": "..." }

Return strict JSON like:
{
  "title": "...",
  "description": "...",
  "chapters": [ { "start": 0, "title": "Intro" }, ... ]
}
PROMPT;

        $key = $this->requireOpenAIKey();

        $resp = Http::withToken($key)
            ->connectTimeout(30)
            ->timeout(180)
            ->retry(2, 5000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o-mini',
                'temperature'     => 0.7,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => 'You are an expert podcast editor.'],
                    ['role' => 'user',   'content' => $prompt . "\n\nTRANSCRIPT:\n" . $text],
                ],
            ])
            ->throw()
            ->json();

        $content = $resp['choices'][0]['message']['content'] ?? '{}';
        $data    = json_decode($content, true);
        if (!is_array($data)) {
            Log::warning('LLM summarization returned non-JSON; using fallbacks', [
                'preview' => substr((string)$content, 0, 200)
            ]);
            $data = [];
        }

        return [
            $data['title']       ?? null,
            $data['description'] ?? null,
            is_array($data['chapters'] ?? null) ? $data['chapters'] : [],
        ];
    }

    /* ---------------------- ffmpeg / ffprobe helpers ---------------------- */

    private function probeDurationSec(string $src): ?float
    {
        $ffprobe = $this->findFfprobe();
        if (!$ffprobe) return null;

        $p = new Process([
            $ffprobe,
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=nk=1:nw=1',
            $src
        ]);
        $p->setTimeout(10);
        $p->run();
        if ($p->getExitCode() !== 0) return null;

        $val = trim($p->getOutput() . $p->getErrorOutput());
        $sec = (float) $val;
        return $sec > 0 ? $sec : null;
    }

    private function findFfprobe(): ?string
    {
        $env = env('FFPROBE_PATH');
        if ($env && is_file($env)) return $env;

        $candidates = stripos(PHP_OS_FAMILY, 'Windows') !== false
            ? ['ffprobe.exe', 'ffprobe.cmd', 'ffprobe.bat']
            : ['ffprobe'];

        $paths = array_filter(explode(PATH_SEPARATOR, (string) getenv('PATH')));
        foreach ($paths as $dir) {
            foreach ($candidates as $bin) {
                $full = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $bin;
                if (is_file($full)) return $full;
            }
        }
        return null;
    }

    private function findFfmpeg(): ?string
    {
        $env = env('FFMPEG_PATH');
        if ($env && is_file($env)) return $env;

        $candidates = stripos(PHP_OS_FAMILY, 'Windows') !== false
            ? ['ffmpeg.exe', 'ffmpeg.cmd', 'ffmpeg.bat']
            : ['ffmpeg'];

        $paths = array_filter(explode(PATH_SEPARATOR, (string) getenv('PATH')));
        foreach ($paths as $dir) {
            foreach ($candidates as $bin) {
                $full = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $bin;
                if (is_file($full)) return $full;
            }
        }
        return null;
    }

    /* ---------------------- misc helpers ---------------------- */

    private function requireOpenAIKey(): string
    {
        $key = (string) config('services.openai.key');
        if (!$key) {
            throw new \RuntimeException('OpenAI API key missing (services.openai.key).');
        }
        return $key;
    }

    private function isWindows(): bool
    {
        return stripos(PHP_OS_FAMILY ?? PHP_OS, 'Windows') !== false;
    }

    private function joinCommand(array $parts): string
    {
        return implode(' ', array_map(function ($p) {
            if ($p === '' || preg_match('/\s|["\'$`&|<>^]/', $p)) {
                if ($this->isWindows()) {
                    return '"'.str_replace('"', '\"', $p).'"';
                }
                return "'" . str_replace("'", "'\"'\"'", $p) . "'";
            }
            return $p;
        }, $parts));
    }
}
