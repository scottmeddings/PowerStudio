<?php

// app/Http/Controllers/Settings/ImportController.php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\ImportRssFeedJob;
use App\Support\ImportProgress;

class ImportController extends Controller
{
    public function show(Request $r)
    {
        // $started can come from session flash or be inferred from cache
        $started = (bool) $r->session()->get('rss_import_started', false);
        return view('settings.import', [
            'started' => $started,
            'import_feed_url' => old('import_feed_url'),
        ]);
    }

    public function handle(Request $r)
    {
        $data = $r->validate([
            'import_feed_url' => ['required','url'],
            'do_301'          => ['nullable','boolean'],
        ]);

        $userId = Auth::id();
        ImportProgress::put($userId, 1, 'Queued…');

        // Dispatch the job; ensure it knows the userId (for progress key & permissions)
        dispatch(new ImportRssFeedJob(
            feedUrl: $data['import_feed_url'],
            set301:  (bool) $r->boolean('do_301'),
            userId:  $userId
        ))->onQueue('default');

        // FLASH the session flag so your JS starts polling after redirect
        return redirect()
            ->route('settings.import')
            ->with('rss_import_started', true);
    }

    public function status(Request $r)
    {
        $userId = Auth::id();
        return response()->json(['progress' => ImportProgress::get($userId)]);
    }
}
