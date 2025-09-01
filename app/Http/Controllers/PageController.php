<?php
// app/Http/Controllers/PageController.php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Download;


class PageController extends Controller
{
    public function episodes()
    {
        // Prefer the current user's episodes; fall back to all if none
        $episodes = Episode::where('user_id', Auth::id())
            ->latest('created_at')
            ->paginate(10);

        if ($episodes->total() === 0) {
            $episodes = Episode::latest('created_at')->paginate(10);
        }

        return view('pages.episodes', compact('episodes'));
    }

    public function distribution()
    {
        // TODO: replace with your real feed URL or route
        $rss = url('/feed/podcast.xml');

        // Toggle these as you integrate real checks
        $connected = [
            'apple'       => false,
            'spotify'     => true,
            'ytmusic'     => false,
            'amazon'      => false,
            'iheart'      => false,
            'tunein'      => false,
            'pocketcasts' => false,
            'overcast'    => false,
            'castbox'     => false,
            'deezer'      => false,
            'pandora'     => false,
        ];

        return view('pages.distribution', compact('rss', 'connected'));
    }

   

   // app/Http/Controllers/PageController.php
    public function monetization()
    {
    $rev = [
        'mtd'    => 284.75,
        'last30' => 1120.40,
        'all'    => 7566.00,
        'ecpm'   => 18.25,
    ];

    $payouts = [
        ['date' => now()->subMonths(2)->endOfMonth()->toDateString(), 'amount' => 480.25, 'status' => 'paid'],
        ['date' => now()->subMonth()->endOfMonth()->toDateString(),   'amount' => 640.15, 'status' => 'paid'],
        ['date' => now()->endOfMonth()->toDateString(),                'amount' => 0.00,   'status' => 'processing'],
    ];

    return view('pages.monetization', compact('rev', 'payouts'));
    }


   // app/Http/Controllers/PageController.php
    public function settings()
    {
    $rss = url('/feed/podcast.xml');   // TODO: replace with your real feed route
    return view('pages.settings', compact('rss'));
    }


    public function statistics(Request $request)
    {
    // Accept ?range=7|30|90 (defaults to 30)
    $days = (int) $request->query('range', 30);
    $days = in_array($days, [7, 30, 90], true) ? $days : 30;

    $from = now()->subDays($days - 1)->startOfDay();
    $to   = now();

    // Daily downloads in the range
    $raw = Download::selectRaw('date(created_at) as d, count(*) as c')
        ->whereBetween('created_at', [$from, $to])
        ->groupBy('d')
        ->orderBy('d')
        ->pluck('c', 'd')
        ->all();

    // Fill missing days with 0
    $series = [];
    $cursor = $from->copy();
    while ($cursor <= $to) {
        $key = $cursor->toDateString();
        $series[] = ['date' => $key, 'count' => $raw[$key] ?? 0];
        $cursor->addDay();
    }

    // KPIs
    $totals = [
        'range'     => array_sum(array_column($series, 'count')),
        'yesterday' => Download::whereDate('created_at', now()->subDay()->toDateString())->count(),
        'last7'     => Download::where('created_at', '>=', now()->subDays(6)->startOfDay())->count(),
        'last30'    => Download::where('created_at', '>=', now()->subDays(29)->startOfDay())->count(),
        'all'       => Download::count(),
    ];

    // Top episodes in the range
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
