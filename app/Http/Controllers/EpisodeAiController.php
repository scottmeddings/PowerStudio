<?php
// app/Http/Controllers/EpisodeAIController.php

namespace App\Http\Controllers;

use App\Jobs\EnhanceEpisodeJob;
use App\Models\Episode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class EpisodeAiController extends Controller
{
    /* ----- cache keys helpers (must match the Job) ----- */
    private function progressKey(Episode $e): string { return "ai:{$e->id}:progress"; }
    private function cancelKey(Episode $e): string   { return "ai:{$e->id}:cancel"; }
    private function pidKey(Episode $e): string      { return "ai:{$e->id}:pid"; }

    /**
     * Start the AI enhancement job.
     */
    public function enhance(Episode $episode): JsonResponse
    {
        $this->authorize('update', $episode);

        // clear any previous cancel flag and seed progress
        Cache::forget($this->cancelKey($episode));
        Cache::put($this->progressKey($episode), [
            'status'   => 'queued',
            'progress' => 1,
            'message'  => 'Queued…',
        ], now()->addMinutes(30));

        EnhanceEpisodeJob::dispatch($episode->id);

        // 202 = accepted/queued
        return response()->json(['ok' => true], 202);
    }

    /**
     * Polled by the UI to get current progress/state.
     */
    public function progress(Episode $episode)
    {
        $cache = Cache::get("ai:{$episode->id}:progress");

        // DB fallback (works even if worker/web use different CACHE_DRIVER or old config)
        if (!$cache || !is_array($cache)) {
            $cache = [
                'status'   => $episode->ai_status   ?? 'idle',
                'progress' => (int)($episode->ai_progress ?? 0),
                'message'  => $episode->ai_message  ?? '',
            ];
        }

        // Normalize payload fields
        $cache['status']   = (string) ($cache['status']   ?? 'idle');
        $cache['message']  = (string) ($cache['message']  ?? '');
        $cache['progress'] = (int)    ($cache['progress'] ?? 0);

        // Also expose a boolean so the front-end can stop polling
        $cache['terminal'] = in_array($cache['status'], ['done','failed','canceled'], true);

        return response()
            ->json($cache)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Cancel the running job quickly (kills ffmpeg if present).
     */
    public function cancel(Episode $episode)
    {
        $this->authorize('update', $episode);

        // Flag cancel so the job aborts at the next checkpoint
        Cache::put("ai:{$episode->id}:cancel", true, now()->addHour());

        // If ffmpeg is currently running, kill it by PID
        if ($pid = Cache::pull("ai:{$episode->id}:pid")) {
            if (stripos(PHP_OS, 'WIN') === 0) {
                @exec('taskkill /F /PID '.((int)$pid));
            } else {
                if (function_exists('posix_kill')) { @posix_kill((int)$pid, 9); }
                @exec('kill -9 '.((int)$pid));
            }
        }

        // Update visible progress state
        Cache::put("ai:{$episode->id}:progress", [
            'status'   => 'canceled',
            'progress' => 0,
            'message'  => 'Canceled by user.',
        ], now()->addMinutes(30));

        // (optional) if you mirror ai_* to DB
        $episode->update([
            'ai_status'   => 'canceled',
            'ai_progress' => 0,
            'ai_message'  => 'Canceled by user.',
        ]);

        return response()->json(['ok' => true]);
    }
}

