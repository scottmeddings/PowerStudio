<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ImportRssFeedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RssImportController extends Controller
{
    /** Must match the job & the status endpoint the Blade polls */
    private const PROGRESS_KEY = 'rss_import:progress';

    /** GET /settings/import */
    public function show(Request $request)
    {
        $import_feed_url = old('import_feed_url', config('app.feed_url', 'https://podcast.powertime.au/feed.xml'));
        $started = (bool) session('rss_import_started', false);

        return view('settings.import', compact('import_feed_url', 'started'));
    }

    /** POST /settings/import */
    public function handle(Request $request)
    {
        $data = $request->validate([
            'import_feed_url' => ['required', 'url', 'max:2048'],
            'do_301'          => ['nullable', 'boolean'],
        ]);

        // current signed-in user (route group should have 'auth' middleware)
        $ownerId = auth()->id();
        if (!$ownerId) {
            abort(403, 'Please sign in to import.');
        }

        // Tidy the URL (optional)
        $feed = $this->normalizeUrl($data['import_feed_url']);

        Log::info('[IMPORT] handle()', [
            'user_id' => $ownerId,
            'feed'    => $feed,
            'queue'   => 'default',
        ]);

        // Prime progress immediately for the poller
        Cache::put(self::PROGRESS_KEY, [
            'percent' => 1,
            'message' => 'Queued…',
            'meta'    => ['feed' => $feed, 'user_id' => $ownerId],
        ], now()->addHour());

        // Dispatch the job (queue name must match your worker)
        ImportRssFeedJob::dispatch(
            feedUrl: $feed,
            set301 : (bool) ($data['do_301'] ?? false),
            userId : $ownerId
        )->onQueue('default');

        // Let the Blade know to start/resume polling after redirect
        session()->flash('rss_import_started', true);

        return back()->with([
            'import_feed_url' => $feed,
            'started'         => true,
        ]);
    }

    /** GET /settings/import/status — polled by the Blade */
    public function status(Request $request)
    {
        $progress = Cache::get(self::PROGRESS_KEY, [
            'percent' => 0,
            'message' => 'Idle',
        ]);

        $progress['percent'] = max(0, min(100, (int) ($progress['percent'] ?? 0)));
        $progress['message'] = (string) ($progress['message'] ?? '…');

        return response()->json(['progress' => $progress]);
    }

    /* ==================== helpers ==================== */

    private function normalizeUrl(string $url): string
    {
        $u = trim($url);
        if (!preg_match('~^https?://~i', $u)) {
            $u = 'https://' . ltrim($u, '/');
        }
        return $u;
    }
}
