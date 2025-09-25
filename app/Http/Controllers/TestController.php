<?php
// app/Http/Controllers/TestController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Episode;
use App\Models\Download;

class TestController extends Controller
{
    public function totals()
    {
        // Core counts
        $totals = [
            'users'             => Schema::hasTable('users')     ? User::count() : 0,
            'episodes'          => Schema::hasTable('episodes')  ? Episode::count() : 0,
            'downloads'         => Schema::hasTable('downloads') ? Download::count() : 0,
            'downloads_last7'   => Schema::hasTable('downloads') ? Download::where('created_at', '>=', now()->subDays(7))->count() : 0,
            'downloads_last30'  => Schema::hasTable('downloads') ? Download::where('created_at', '>=', now()->subDays(30))->count() : 0,
        ];

        // Episodes by status (draft/published/archived)
        $episodesByStatus = collect();
        if (Schema::hasTable('episodes') && Schema::hasColumn('episodes', 'status')) {
            $episodesByStatus = Episode::select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')
                ->orderBy('status')
                ->get();
        }

        // Top episodes by downloads
        $topEpisodes = collect();
        if (Schema::hasTable('downloads') && Schema::hasTable('episodes')) {
            $topEpisodes = DB::table('downloads')
                ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
                ->select('episodes.id', 'episodes.title', DB::raw('COUNT(downloads.id) as downloads_count'))
                ->groupBy('episodes.id', 'episodes.title')
                ->orderByDesc('downloads_count')
                ->limit(10)
                ->get();
        }

        // Table row counts (MySQL-focused)
        [$tableCounts, $tableCountsError] = $this->getAllTableCounts();

        return view('pages.test_totals', compact(
            'totals',
            'episodesByStatus',
            'topEpisodes',
            'tableCounts',
            'tableCountsError'
        ));
    }

    /**
     * Row counts for all tables.
     */
    private function getAllTableCounts(): array
    {
        try {
            $driver = DB::getDriverName();
            $tables = [];

            if ($driver === 'mysql') {
                // Get all tables for current schema
                $tables = collect(DB::select("
                    SELECT table_name 
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE()
                "))->pluck('table_name')->all();
            } elseif ($driver === 'sqlite') {
                $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                    ->pluck('name')->all();
            } elseif ($driver === 'pgsql') {
                $tables = collect(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"))
                    ->pluck('tablename')->all();
            }

            $counts = [];
            foreach ($tables as $t) {
                if ($t === 'migrations') continue;
                try {
                    $counts[$t] = DB::table($t)->count();
                } catch (\Throwable $e) {
                    $counts[$t] = 'ERR';
                }
            }

            ksort($counts);
            return [$counts, null];

        } catch (\Throwable $e) {
            return [[], $e->getMessage()];
        }
    }
}
