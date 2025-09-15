<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ImportRssFeedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ImportController extends Controller
{
    private const PROGRESS_KEY = 'rss_import:progress';
    private const LOCK_KEY     = 'rss_import:lock';

    public function show(Request $request)
    {
        $import_feed_url = old('import_feed_url')
            ?? cache('settings:import_feed_url')
            ?? 'https://podcast.powertime.au/feed.xml';

        $started = (bool) session('started', false);

        return view('settings.import', compact('import_feed_url','started'));
    }

    public function handle(Request $request)
{
    \Log::info('ImportController@handle HIT', $request->only('import_feed_url','do_301'));

    $data = $request->validate([
        'import_feed_url' => ['required','url','max:2048'],
        'do_301'          => ['nullable','boolean'],
    ]);

    cache(['settings:import_feed_url' => $data['import_feed_url']], now()->addDays(7));
    cache(['settings:do_301'          => (bool)($data['do_301'] ?? false)], now()->addDays(7));

    $store = \Cache::store(config('podpower.rss_progress_store','file'));

    // Prime progress so UI moves immediately
    $store->put('rss_import:progress', [
        'message'    => 'Queued…',
        'percent'    => 1,
        'started_at' => now()->toIso8601String(),
    ], now()->addMinutes(30));

    \Log::info('Dispatching ImportRssFeedJob', ['url' => $data['import_feed_url']]);

    dispatch(new \App\Jobs\ImportRssFeedJob(
        $data['import_feed_url'],
        (bool)($data['do_301'] ?? false),
        optional($request->user())->id
    ))->onQueue('default');

    return redirect()->route('settings.import.show')->with('started', true);
}


    public function status()
    {
        $store    = $this->progressStore();
        $progress = $store->get(self::PROGRESS_KEY);

        // Lightweight lock probe (optional)
        $tmpLock = $store->lock(self::LOCK_KEY, 1);
        $locked  = ! $tmpLock->get();
        optional($tmpLock)->release();

        $progress = [
            'message'    => Arr::get($progress, 'message', 'Waiting to start…'),
            'percent'    => (int) Arr::get($progress, 'percent', 0),
            'started_at' => Arr::get($progress, 'started_at', null),
        ];

        return response()->json(compact('progress','locked'));
    }

   private function progressStore()
{
    return \Cache::store(config('podpower.rss_progress_store', 'file'));
}
}
