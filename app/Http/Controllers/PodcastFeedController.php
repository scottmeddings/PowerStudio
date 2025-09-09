<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PodcastFeedController extends Controller
{
    public function index(): Response
    {
        $key = 'podcast:feed:v1';
        $xml = Cache::remember($key, now()->addMinutes(10), function () {
            // Only published episodes with a publish date
            $episodes = Episode::with(['transcript', 'chapters', 'user'])
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->orderByDesc('published_at')
                ->limit(300) // Apple suggests keeping feed length reasonable
                ->get();

            // Build items data the view needs
            $items = $episodes->map(function (Episode $e) {
                $audioUrl = $e->audio_url;
                $length   = $this->guessLength($audioUrl); // optional HEAD
                $mime     = $this->guessType($audioUrl);   // audio/mpeg etc.

                // duration HH:MM:SS
                $duration = $this->fmtDuration((int) ($e->duration_seconds ?? 0));

                // per-episode cover (fallback to channel cover in view)
                $cover = $e->cover_image_url;

                // transcript URL & type (Podcasting 2.0)
                $transcriptUrl  = null;
                $transcriptType = null;
                if ($e->transcript) {
                    if ($e->transcript->storage_path) {
                        $transcriptUrl = route('episodes.transcript.download', $e);
                        $transcriptType = match($e->transcript->format) {
                            'vtt' => 'text/vtt',
                            'srt' => 'application/x-subrip',
                            default => 'text/plain',
                        };
                    }
                }

                // chapters JSON URL (Podcasting 2.0)
                $chaptersUrl = $e->chapters()->exists() ? route('episodes.chapters.json', $e) : null;

                return [
                    'title'        => $e->title ?? 'Episode '.$e->id,
                    'description'  => $e->description ?? '',
                    'audio_url'    => $audioUrl,
                    'enclosure'    => ['url' => $audioUrl, 'length' => $length, 'type' => $mime],
                    'guid'         => url("/episodes/{$e->id}"),
                    'pubDate'      => optional($e->published_at)->toRfc2822String(),
                    'duration'     => $duration,
                    'cover'        => $cover,
                    'transcript'   => $transcriptUrl ? ['url'=>$transcriptUrl, 'type'=>$transcriptType] : null,
                    'chapters_url' => $chaptersUrl,
                ];
            });

            // Render Blade to XML string
            return view('feed.podcast', [
                'items'  => $items,
                'config' => config('podcast'),
                'self'   => route('feed.podcast'),
                'lastBuildDate' => now()->toRfc2822String(),
            ])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    private function fmtDuration(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return ($h > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s));
    }

    private function guessType(?string $url): string
    {
        return str_ends_with(strtolower((string)$url), '.m4a') ? 'audio/x-m4a' : 'audio/mpeg';
    }

    // Try HEAD for Content-Length; fall back to 0 if unknown
    private function guessLength(?string $url): int
    {
        if (!$url) return 0;
        try {
            $res = Http::timeout(5)->head($url);
            if ($res->successful()) {
                $len = (int) $res->header('Content-Length', 0);
                return $len > 0 ? $len : 0;
            }
        } catch (\Throwable $e) { /* ignore */ }
        return 0;
    }
}
