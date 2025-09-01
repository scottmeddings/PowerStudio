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
        // Core counts (guard anything that might not exist)
        $totals = [
            'users'             => class_exists(User::class)     ? User::count() : 0,
            'episodes'          => class_exists(Episode::class)  ? Episode::count() : 0,
            'downloads'         => class_exists(Download::class) ? Download::count() : 0,
            'downloads_last7'   => class_exists(Download::class) ? Download::where('created_at', '>=', now()->subDays(7))->count() : 0,
            'downloads_last30'  => class_exists(Download::class) ? Download::where('created_at', '>=', now()->subDays(30))->count() : 0,
        ];

        // Episodes by status (draft/published/archived), if Episodes table exists
        $episodesByStatus = collect();
        if (Schema::hasTable('episodes') && class_exists(Episode::class) && Schema::hasColumn('episodes', 'status')) {
            $episodesByStatus = Episode::select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')
                ->orderBy('status')
                ->get();
        }

        // Top episodes by downloads (requires downloads.episode_id)
        $topEpisodes = collect();
        if (
            Schema::hasTable('downloads') &&
            Schema::hasTable('episodes')  &&
            Schema::hasColumn('downloads', 'episode_id')
        ) {
            $topEpisodes = DB::table('downloads')
                ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
                ->select('episodes.id', 'episodes.title', DB::raw('COUNT(downloads.id) as downloads_count'))
                ->groupBy('episodes.id', 'episodes.title')
                ->orderByDesc('downloads_count')
                ->limit(10)
                ->get();
        }

        // DB-wide table counts (best-effort, driver-aware)
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
     * Best-effort table row counts across drivers without requiring doctrine/dbal.
     * Returns [array $counts, string|null $error]
     */
    private function getAllTableCounts(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $tables = [];

        try {
            if ($driver === 'sqlite') {
                $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                    ->pluck('name')
                    ->all();
            } elseif ($driver === 'mysql') {
                $tables = collect(DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()"))
                    ->pluck('table_name')
                    ->all();
            } elseif ($driver === 'pgsql') {
                $tables = collect(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"))
                    ->pluck('tablename')
                    ->all();
            } else {
                // Unknown driver – skip
                return [[], "Unsupported driver: {$driver}"];
            }

            // Count rows per table (skip migrations table to reduce noise)
            $counts = [];
            foreach ($tables as $t) {
                if ($t === 'migrations') continue;
                // Only count if accessible & valid
                try {
                    if (Schema::hasTable($t)) {
                        $counts[$t] = DB::table($t)->count();
                    }
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
