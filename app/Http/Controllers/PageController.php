<?php
// app/Http/Controllers/PageController.php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Download;
use App\Models\PodcastDirectory;        // make:model PodcastDirectory as per earlier step
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PageController extends Controller
{
    /**
     * Episodes index — only my episodes.
     */
    public function episodes(Request $request)
    {
        $episodes = Episode::query()
            ->where('user_id', $request->user()->id)
            ->withCount([
                'comments as approved_comments_count' => fn ($q) => $q->approved(),
            ])
            ->latest('created_at')
            ->paginate(10);

        return view('pages.episodes', compact('episodes'));
    }

    /**
     * Distribution page.
     * Sends: $rss, $directories, $connected, and stats for the header tiles.
     */
    public function distribution()
    {
        // Feed URL (swap for your real route if different)
        $rss = url('/feed/podcast.xml');

        // KPI tiles for the header include
        $yesterday = Download::whereDate('created_at', now()->subDay())->count();
        $last7     = Download::where('created_at', '>=', now()->subDays(7))->count();
        $last30    = Download::where('created_at', '>=', now()->subDays(30))->count();
        $allTime   = Download::count();

        // Directory definitions (icon is the CSS modifier your Blade uses; color is a fallback inline bg)
        $defaults = [
            'apple'        => ['name' => 'Apple Podcasts', 'icon' => 'pi-apple',   'color' => '#a970ff'],
            'spotify'      => ['name' => 'Spotify',        'icon' => 'pi-spotify', 'color' => '#1db954'],
            'ytmusic'      => ['name' => 'YouTube Music',  'icon' => 'pi-ytm',     'color' => '#ff0033'],
            'amazon'       => ['name' => 'Amazon Music',   'icon' => 'pi-amazon',  'color' => '#00a8e1'],
            'iheart'       => ['name' => 'iHeartRadio',    'icon' => 'pi-iheart',  'color' => '#c6002b'],
            'tunein'       => ['name' => 'TuneIn',         'icon' => 'pi-tunein',  'color' => '#14a0a0'],
            'pocketcasts'  => ['name' => 'Pocket Casts',   'icon' => 'pi-pocket',  'color' => '#f43f5e'],
            'overcast'     => ['name' => 'Overcast',       'icon' => 'pi-over',    'color' => '#ff7a00'],
            'castbox'      => ['name' => 'Castbox',        'icon' => 'pi-castbx',  'color' => '#f65e3b'],
            'deezer'       => ['name' => 'Deezer',         'icon' => 'pi-deezer',  'color' => '#121216'],
            'pandora'      => ['name' => 'Pandora',        'icon' => 'pi-pand',    'color' => '#224099'],
        ];

        // Pull saved config for this user (ok if table/model exists; otherwise empty)
        $saved = class_exists(PodcastDirectory::class)
            ? PodcastDirectory::where('user_id', Auth::id())->get()->keyBy('slug')
            : collect();
// inside PageController@distribution

        $rows = class_exists(\App\Models\PodcastDirectory::class)
            ? \App\Models\PodcastDirectory::where('user_id', \Auth::id())->get()->keyBy('slug')
            : collect();

        $directories = collect($defaults)->map(function ($meta, $slug) use ($rows) {
            $r = $rows->get($slug);
            return [
                'slug'         => $slug,
                'name'         => $meta['name'],
                'icon'         => $meta['icon'],
                'connected'    => (bool) optional($r)->is_connected, // <-- important
                'external_url' => optional($r)->external_url,
                'id'           => optional($r)->id,
            ];
        });
        // Build structures the Blade expects
        $directories = collect($defaults)->map(function (array $meta, string $slug) use ($saved) {
            $row = $saved->get($slug);
            return [
                'slug'         => $slug,
                'name'         => $meta['name'],
                'icon'         => $meta['icon'],                   // CSS class suffix used in your view
                'color'        => $meta['color'],                  // inline background fallback
                'connected'    => (bool) optional($row)->is_connected,
                'external_url' => optional($row)->external_url,
                'id'           => optional($row)->id,
            ];
        })->values(); // ensure numeric keys for @foreach

        // Quick map for dot/badge state
        $connected = $directories->mapWithKeys(fn ($d) => [$d['slug'] => $d['connected']])->all();

        return view('pages.distribution', compact(
            'rss',
            'directories',
            'connected',
            'yesterday',
            'last7',
            'last30',
            'allTime',
        ));
    }

    /**
     * Monetization page.
     */
    public function monetization()
    {
        return view('pages.monetization');
    }

    /**
     * Settings page.
     */
    public function settings()
    {
        $u   = Auth::user();
        $rss = url('/feed/podcast.xml');

        // Requires: php artisan storage:link
        $coverUrl = $u?->cover_path ? Storage::url($u->cover_path) : null;

        return view('pages.settings', compact('rss', 'u', 'coverUrl'));
    }

    /**
     * Statistics page.
     */
    public function statistics(Request $request)
    {
        $days = (int) $request->query('range', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        $from = now()->subDays($days - 1)->startOfDay();
        $to   = now();

        $raw = Download::selectRaw('date(created_at) as d, count(*) as c')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd')
            ->all();

        $series = [];
        $cursor = $from->copy();
        while ($cursor <= $to) {
            $k = $cursor->toDateString();
            $series[] = ['date' => $k, 'count' => $raw[$k] ?? 0];
            $cursor->addDay();
        }

        $totals = [
            'range'     => array_sum(array_column($series, 'count')),
            'yesterday' => Download::whereDate('created_at', now()->subDay()->toDateString())->count(),
            'last7'     => Download::where('created_at', '>=', now()->subDays(6)->startOfDay())->count(),
            'last30'    => Download::where('created_at', '>=', now()->subDays(29)->startOfDay())->count(),
            'all'       => Download::count(),
        ];

        $topEpisodes = DB::table('downloads')
            ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
            ->whereBetween('downloads.created_at', [$from, $to])
            ->select('episodes.id', 'episodes.title', DB::raw('COUNT(downloads.id) as downloads'))
            ->groupBy('episodes.id', 'episodes.title')
            ->orderByDesc('downloads')
            ->limit(10)
            ->get();

        return view('pages.statistics', [
            'series'      => $series,
            'totals'      => $totals,
            'topEpisodes' => $topEpisodes,
            'days'        => $days,
            'from'        => $from,
            'to'          => $to,
        ]);
    }
}
