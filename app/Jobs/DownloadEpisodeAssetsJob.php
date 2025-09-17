<?php

namespace App\Jobs;

use App\Models\Episode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class DownloadEpisodeAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries   = 1;

    public function __construct(
        public int $episodeId,
        public int $userId,
        public ?string $enclosureUrl,
        public ?string $chaptersUrl,
        public ?string $transcriptUrl,
        public string $slug
    ) {
        $this->onQueue('io'); // keep I/O off your default workers
    }

    public function handle(): void
    {
        $ep = Episode::find($this->episodeId);
        if (!$ep) {
            Log::warning('[ASSET] episode missing', ['episode_id' => $this->episodeId]);
            return;
        }

        // AUDIO
        if ($this->enclosureUrl && empty($ep->audio_path)) {
            try {
                $rel = $this->relPath($this->userId, $ep->title, $ep->published_at, 'audio.'.$this->guessExtFromUrl($this->enclosureUrl));
                $dl  = $this->downloadToStorage($this->enclosureUrl, $rel, 600);
                $ep->audio_path  = $dl['path'];
                $ep->audio_bytes = $dl['bytes'];
                $ep->audio_url   = $this->publicUrlFor($dl['path']);
            } catch (\Throwable $e) {
                Log::warning('[ASSET] audio download failed', ['id'=>$this->episodeId,'url'=>$this->enclosureUrl,'err'=>$e->getMessage()]);
            }
        } elseif ($ep->audio_path && (empty($ep->audio_url) || $this->isExternal($ep->audio_url))) {
            $ep->audio_url = $this->publicUrlFor($ep->audio_path);
        }

        // CHAPTERS
        if ($this->chaptersUrl) {
            try {
                $rel = $this->relPath($this->userId, $ep->title, $ep->published_at, 'chapters.json');
                $this->downloadToStorage($this->chaptersUrl, $rel, 180);
                $ep->chapters = $this->publicUrlFor($rel);
            } catch (\Throwable $e) {
                Log::warning('[ASSET] chapters download failed', ['id'=>$this->episodeId,'err'=>$e->getMessage()]);
            }
        }

        // TRANSCRIPT
        if ($this->transcriptUrl) {
            try {
                $ext = strtolower(pathinfo(parse_url($this->transcriptUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'vtt';
                $ext = in_array($ext, ['vtt','srt','txt','json']) ? $ext : 'vtt';
                $rel = $this->relPath($this->userId, $ep->title, $ep->published_at, "transcript.{$ext}");
                $this->downloadToStorage($this->transcriptUrl, $rel, 180);
                $ep->transcript = $this->publicUrlFor($rel);
            } catch (\Throwable $e) {
                Log::warning('[ASSET] transcript download failed', ['id'=>$this->episodeId,'err'=>$e->getMessage()]);
            }
        }

        // Canonical GUID (after we know id/slug)
        if (!$this->looksAbsolute($ep->guid)) {
            $rowSlug = $ep->slug ?: $this->slug;
            $episodeUrl = URL::to("/episodes/{$ep->id}-{$rowSlug}");
            $ep->guid = $episodeUrl . ($ep->guid ? ('#' . rawurlencode($ep->guid)) : '');
        }

        $ep->updated_at = now();
        $ep->save();
    }

    /* helpers */

    private function downloadToStorage(string $url, string $relPath, int $timeout): array
    {
        $tmp = tmpfile(); if ($tmp === false) throw new \RuntimeException('tmpfile failed');
        $tmpPath = stream_get_meta_data($tmp)['uri'];

        $req = Http::retry(2, 250)->timeout($timeout)->withHeaders([
            'User-Agent' => 'PodPowerImporter/1.0',
            'Accept'     => '*/*'
        ])->sink($tmpPath)->get($url);

        if (!$req->ok()) { fclose($tmp); throw new \RuntimeException('HTTP '.$req->status()); }
        $bytes = @filesize($tmpPath) ?: 0;

        $disk = config('filesystems.default', 'public');
        $dir  = dirname($relPath);
        if (!Storage::disk($disk)->exists($dir)) Storage::disk($disk)->makeDirectory($dir);
        rewind($tmp);
        $ok = Storage::disk($disk)->put($relPath, $tmp);
        fclose($tmp);
        if (!$ok) throw new \RuntimeException('write failed');

        return ['path'=>$relPath,'bytes'=>$bytes];
    }

    private function relPath(int $userId, string $title, ?string $publishedAt, string $filename): string
    {
        $ym = date('Y/m', $publishedAt ? strtotime($publishedAt) : time());
        $dateKey = $publishedAt ?: now()->format('Y-m-d H:i:s');
        $dateForName = str_replace([' ', ':'], ['_', '-'], $dateKey);
        $base = Str::slug($title) ?: 'episode';
        return "episodes/{$userId}/{$ym}/{$base}_{$dateForName}/{$filename}";
    }

    private function publicUrlFor(string $path): string
    {
        $disk = config('filesystems.default', 'public');
        $url  = Storage::disk($disk)->url($path);
        return str_starts_with($url, 'http') ? $url : URL::to($url);
    }

    private function looksAbsolute(?string $url): bool
    {
        return $url && preg_match('~^https?://~i', $url);
    }

    private function isExternal(?string $url): bool
    {
        if (!$url) return true;
        $host = parse_url($url, PHP_URL_HOST);
        $app  = parse_url(config('app.url', URL::to('/')), PHP_URL_HOST);
        return !($host && $app && strcasecmp($host, $app) === 0);
    }

    private function guessExtFromUrl(string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['mp3','m4a','aac','wav','ogg','opus','webm'], true) ? $ext : 'mp3';
    }
}
