<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class EpisodeController extends Controller
{
    public function index(Request $request)
    {
        $episodes = Episode::query()
            ->where('user_id', auth()->id())
            ->withCount('downloads')
            ->select(['id','user_id','title','slug','status','published_at'])
            ->latest()
            ->paginate(10);

        $topEpisodes = $this->topEpisodesEarlyDownloads(auth()->id(), 8);

        // ✅ Pick the first episode or null
        $episode = $episodes->first();

        return view('pages.episodes', compact('episodes', 'topEpisodes', 'episode'));
    }

    public function create()
    {
        return view('episodes.create');
    }

    /** Validation */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'title'            => ['required', 'string', 'max:160'],
            'description'      => ['nullable', 'string'],
            'audio'            => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav', 'max:2097152'], // 2GB
            'audio_url'        => ['nullable', 'url'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status'           => ['required', 'in:draft,published'],
            'published_at'     => ['nullable', 'date'],
            'cover'            => ['nullable', 'image'],
        ]);
    }

   public function store(Request $request)
{
    $data = $this->validated($request);

    $audioUrl  = $data['audio_url'] ?? null;
    $audioPath = null;

    if ($request->hasFile('audio')) {
        // Save file
        $path      = $request->file('audio')->store('audio', 'public');
        $audioPath = $path;

        // Always generate full URL for audio_url
        $audioUrl  = Storage::url($path); // e.g. /storage/audio/filename.mp3
    }

    $slug = $this->uniqueSlug($data['title']);

    Episode::create([
        'user_id'          => auth()->id(),
        'title'            => $data['title'],
        'slug'             => $slug,
        'description'      => $data['description'] ?? null,
        'audio_url'        => $audioUrl,
        'audio_path'       => $audioPath,
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

    if ($episode->title !== $data['title']) {
        $episode->slug = $this->uniqueSlug($data['title'], $episode->id);
    }

    if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
        $data['published_at'] = now();
    }
    if (($data['status'] ?? 'draft') !== 'published') {
        $data['published_at'] = null;
    }

    $audioUrl  = $data['audio_url'] ?? $episode->audio_url;
    $audioPath = $episode->audio_path;

    if ($request->hasFile('audio')) {
        // Delete old file if exists
        if ($audioPath && Storage::disk('public')->exists($audioPath)) {
            Storage::disk('public')->delete($audioPath);
        }

        // Save new file
        $newPath   = $request->file('audio')->store('audio', 'public');
        $audioPath = $newPath;

        // Always regenerate audio_url
        $audioUrl  = Storage::url($newPath);
    }

    $episode->fill([
        'title'            => $data['title'],
        'description'      => $data['description'] ?? null,
        'audio_url'        => $audioUrl,   // ✅ Always updated
        'audio_path'       => $audioPath,  // ✅ Save relative path too
        'duration_seconds' => $data['duration_seconds'] ?? null,
        'status'           => $data['status'],
        'published_at'     => $data['published_at'] ?? null,
    ])->save();

    return redirect()->route('episodes')->with('success', 'Episode updated.');
}


    public function destroy(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if ($episode->audio_path && Storage::disk('public')->exists($episode->audio_path)) {
            Storage::disk('public')->delete($episode->audio_path);
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

    /** Upload cover */
    public function uploadCover(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $request->validate([
            'cover' => ['required', 'image', 'max:2048'],
        ]);

        if ($episode->cover_path && str_starts_with($episode->cover_path, 'covers/')) {
            Storage::disk('public')->delete($episode->cover_path);
        }

        $path = $request->file('cover')->store('covers', 'public');

        $episode->cover_path = $path;
        $episode->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'cover_url'  => Storage::url($path),
                'cover_path' => $path,
            ]);
        }

        return back()->with('success', 'Cover uploaded successfully.');
    }

    /* ---------------------- Downloads tracking ---------------------- */
    public function download(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if (! $episode->audio_url) {
            return back()->withErrors(['audio_url' => 'No audio URL available for this episode.']);
        }

        $ip      = $request->ip();
        $country = $request->header('CF-IPCountry') ?: $request->header('X-App-Country') ?: null;

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

        $manualQuery = $episode->downloads()->where('ip', '0.0.0.0');
        if (Schema::hasColumn('downloads', 'user_agent')) {
            $manualQuery->where('user_agent', self::MANUAL_UA);
        }

        $delta = $target - $currentTotal;

        DB::transaction(function () use ($episode, $delta, $manualQuery) {
            if ($delta > 0) {
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

    /* ---------------------- Analytics ---------------------- */
    protected function topEpisodesEarlyDownloads(int $userId, int $limit = 8)
    {
        $driver = DB::connection()->getDriverName();
        $base  = "COALESCE(episodes.published_at, episodes.created_at)";
        $add7  = $driver === 'sqlite'
            ? "DATETIME($base, '+7 day')"
            : "DATE_ADD($base, INTERVAL 7 DAY)";
        $add30 = $driver === 'sqlite'
            ? "DATETIME($base, '+30 day')"
            : "DATE_ADD($base, INTERVAL 30 DAY)";

        $weekSub = DB::table('downloads')
            ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
            ->select('downloads.episode_id', DB::raw('COUNT(*) AS c'))
            ->whereRaw("downloads.created_at >= $base")
            ->whereRaw("downloads.created_at < $add7")
            ->groupBy('downloads.episode_id');

        $monthSub = DB::table('downloads')
            ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
            ->select('downloads.episode_id', DB::raw('COUNT(*) AS c'))
            ->whereRaw("downloads.created_at >= $base")
            ->whereRaw("downloads.created_at < $add30")
            ->groupBy('downloads.episode_id');

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
