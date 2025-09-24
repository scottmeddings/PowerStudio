<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class EpisodeController extends Controller
{
    public function index(Request $request)
    {
        $episodes = Episode::query()
            ->where('user_id', auth()->id())
            ->withCount('downloads') // gives $ep->downloads_count
            ->select(['id','user_id','title','slug','status','published_at'])
            ->latest()
            ->paginate(10);

        // Optional: top episodes by early downloads (week/month)
        $topEpisodes = $this->topEpisodesEarlyDownloads(auth()->id(), 8);

        return view('pages.episodes', compact('episodes', 'topEpisodes'));
    }

    public function create()
    {
        return view('episodes.create');
    }

    /** Single source of truth for validation */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'title'            => ['required', 'string', 'max:160'],
            'description'      => ['nullable', 'string'],
            'audio'            => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav', 'max:2097152'], // 2GB (in KB)
            'audio_url'        => ['nullable', 'url'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status'           => ['required', 'in:draft,published'],
            'published_at'     => ['nullable', 'date'],
            'cover'            => ['nullable', 'image'],
            // NOTE: no 'plays' here — it's derived from downloads
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // Prefer uploaded file over URL
        $resolvedAudioUrl = $data['audio_url'] ?? null;
        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('audio', 'public');
            $resolvedAudioUrl = Storage::url($path); // /storage/audio/...
        }

        $slug = $this->uniqueSlug($data['title']);

        Episode::create([
            'user_id'          => auth()->id(),
            'title'            => $data['title'],
            'slug'             => $slug,
            'description'      => $data['description'] ?? null,
            'audio_url'        => $resolvedAudioUrl,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'status'           => $data['status'],
            'published_at'     => $data['published_at'] ?? null,
        ]);

        return redirect()->route('episodes')->with('success', 'Episode created.');
    }

    public function edit(Episode $episode)
    {
        $this->authorizeOwnership($episode);
        return view('episodes.edit', compact('episode'));
    }

    public function update(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $data = $this->validated($request);

        // Slug only changes if title changes
        if ($episode->title !== $data['title']) {
            $episode->slug = $this->uniqueSlug($data['title'], $episode->id);
        }

        // Auto manage published_at if status toggles and no date provided
        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        if (($data['status'] ?? 'draft') !== 'published') {
            $data['published_at'] = null;
        }

        // Resolve audio: uploaded file wins; else keep URL or previous value
        $resolvedAudioUrl = $data['audio_url'] ?? $episode->audio_url;

        if ($request->hasFile('audio')) {
            if ($episode->audio_url && str_starts_with($episode->audio_url, '/storage/')) {
                $old = str_replace('/storage/', '', $episode->audio_url);
                Storage::disk('public')->delete($old);
            }

            $newPath = $request->file('audio')->store('audio', 'public');
            $resolvedAudioUrl = Storage::url($newPath);
        }

        $episode->title            = $data['title'];
        $episode->description      = $data['description'] ?? null;
        $episode->audio_url        = $resolvedAudioUrl;
        $episode->duration_seconds = $data['duration_seconds'] ?? null;
        $episode->status           = $data['status'];
        $episode->published_at     = $data['published_at'] ?? null;
        $episode->save();

        return redirect()->route('episodes')->with('success', 'Episode updated.');
    }

    public function destroy(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if ($episode->audio_url && str_starts_with($episode->audio_url, '/storage/')) {
            $old = str_replace('/storage/', '', $episode->audio_url);
            Storage::disk('public')->delete($old);
        }

        $episode->delete();

        return redirect()->route('episodes')->with('success', 'Episode deleted.');
    }

    public function show(Episode $episode)
    {
        $this->authorizeOwnership($episode);
        $episode->load(['comments' => fn($q) => $q->approved()->latest()->with('user:id,name')]);
        return view('pages.episode_show', compact('episode'));
    }

    /** Publish / Unpublish */
    public function publish(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if (strtolower($episode->status ?? 'draft') !== 'published') {
            $episode->forceFill([
                'status'       => 'published',
                'published_at' => now(),
            ])->save();
        }

        dispatch(new \App\Jobs\ShareEpisodeToSocials($episode));

        return back()->with('success', 'Episode published.');
    }

    public function unpublish(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if (strtolower($episode->status ?? '') === 'published') {
            $episode->forceFill([
                'status'       => 'draft',
                'published_at' => null,
            ])->save();
        }

        return back()->with('success', 'Episode unpublished.');
    }

    /* ---------------------- Downloads tracking ---------------------- */

    /**
     * Route in web.php:
     * Route::get('/episodes/{episode}/download', [EpisodeController::class, 'download'])->name('episodes.download');
     */
    public function download(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if (! $episode->audio_url) {
            return back()->withErrors(['audio_url' => 'No audio URL available for this episode.']);
        }

        $ip      = $request->ip();
        $country = $request->header('CF-IPCountry') ?: $request->header('X-App-Country') ?: null;

        // de-dupe by IP in 12h window
        $exists = $episode->downloads()
            ->where('ip', $ip)
            ->where('created_at', '>=', now()->subHours(12))
            ->exists();

        if (! $exists) {
            $row = [
                'episode_id' => $episode->id,
                'ip'         => $ip,
                'country'    => $country,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('downloads', 'user_agent')) {
                $row['user_agent'] = (string) $request->userAgent();
            }
            Download::insert([$row]);
        }

        return redirect()->away($episode->audio_url);
    }

    /* ---------------------- Manual plays adjuster ---------------------- */

    private const MANUAL_UA = 'manual-adjust';

    public function setPlays(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $data = $request->validate([
            'plays' => ['required','integer','min:0'],
        ]);

        $target       = (int) $data['plays'];
        $currentTotal = (int) $episode->downloads()->count();

        // "Manual" rows marker: ip=0.0.0.0 (+ user_agent if present)
        $manualQuery = $episode->downloads()->where('ip', '0.0.0.0');
        if (Schema::hasColumn('downloads', 'user_agent')) {
            $manualQuery->where('user_agent', self::MANUAL_UA);
        }

        $currentManual = (int) (clone $manualQuery)->count();
        $delta = $target - $currentTotal;

        DB::transaction(function () use ($episode, $delta, $manualQuery) {
            if ($delta > 0) {
                // add $delta rows
                $ts    = now();
                $toAdd = $delta;
                $hasUA = Schema::hasColumn('downloads', 'user_agent');

                while ($toAdd > 0) {
                    $chunk = min(500, $toAdd);
                    $rows  = [];
                    for ($i = 0; $i < $chunk; $i++) {
                        $row = [
                            'episode_id' => $episode->id,
                            'ip'         => '0.0.0.0',
                            'country'    => null,
                            'created_at' => $ts,
                            'updated_at' => $ts,
                        ];
                        if ($hasUA) $row['user_agent'] = self::MANUAL_UA;
                        $rows[] = $row;
                    }
                    Download::insert($rows);
                    $toAdd -= $chunk;
                }
            } elseif ($delta < 0) {
                // remove only manual rows
                $toRemove = min(abs($delta), (int) (clone $manualQuery)->count());
                if ($toRemove > 0) {
                    $ids = (clone $manualQuery)->orderByDesc('id')->limit($toRemove)->pluck('id');
                    Download::whereIn('id', $ids)->delete();
                }
            }
        });

        $final = (int) $episode->downloads()->count();
        return back()->with('success', 'Plays updated to '.number_format($final).'.');
    }

    /* ---------------------- MySQL/SQLite-safe analytics ---------------------- */

    /**
     * Returns top N episodes with early download counts (first week & first month).
     * Uses MySQL DATE_ADD for MySQL and SQLite datetime() for SQLite.
     */
    protected function topEpisodesEarlyDownloads(int $userId, int $limit = 8)
{
    $driver = DB::connection()->getDriverName(); // 'mysql' for MySQL/MariaDB, 'sqlite' for SQLite

    $base  = "COALESCE(episodes.published_at, episodes.created_at)";
    $add7  = $driver === 'sqlite'
        ? "DATETIME($base, '+7 day')"
        : "DATE_ADD($base, INTERVAL 7 DAY)";
    $add30 = $driver === 'sqlite'
        ? "DATETIME($base, '+30 day')"
        : "DATE_ADD($base, INTERVAL 30 DAY)";

    // First week subquery
    $weekSub = DB::table('downloads')
        ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
        ->select('downloads.episode_id', DB::raw('COUNT(*) AS c'))
        ->whereRaw("downloads.created_at >= $base")
        ->whereRaw("downloads.created_at < $add7")
        ->groupBy('downloads.episode_id');

    // First month subquery
    $monthSub = DB::table('downloads')
        ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
        ->select('downloads.episode_id', DB::raw('COUNT(*) AS c'))
        ->whereRaw("downloads.created_at >= $base")
        ->whereRaw("downloads.created_at < $add30")
        ->groupBy('downloads.episode_id');

    // Final ranking
    return DB::table('episodes')
        ->select(
            'episodes.id',
            'episodes.title',
            DB::raw('COALESCE(w.c, 0) AS first_week'),
            DB::raw('COALESCE(m.c, 0) AS first_month')
        )
        ->leftJoinSub($weekSub, 'w', 'episodes.id', '=', 'w.episode_id')
        ->leftJoinSub($monthSub, 'm', 'episodes.id', '=', 'm.episode_id')
        ->where('episodes.user_id', $userId)
        ->orderByRaw('COALESCE(m.c, 0) DESC, COALESCE(w.c, 0) DESC')
        ->limit($limit)
        ->get();
}

    /* ---------------------- Helpers ---------------------- */

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'episode';
        $slug = $base; $i = 1;

        do {
            $exists = Episode::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
            if ($exists) $slug = $base.'-'.$i++;
        } while ($exists);

        return $slug;
    }

    private function authorizeOwnership(Episode $episode): void
    {
        abort_unless($episode->user_id === auth()->id(), 403);
    }
}
