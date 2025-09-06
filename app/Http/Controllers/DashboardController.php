<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;        // ← add
use App\Models\Download;
use App\Models\Episode;
use App\Models\Comment;
use App\Support\Achievements;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ---- Core metrics ---------------------------------------------------
        $metrics = [
            'yesterday' => Download::whereDate('created_at', now()->subDay())->count(),
            'last7'     => Download::where('created_at', '>=', now()->subDays(7))->count(),
            'last30'    => Download::where('created_at', '>=', now()->subDays(30))->count(),
            'allTime'   => Download::count(),
        ];

        // ---- Achievements ---------------------------------------------------
        $episodesCount   = Episode::count();
        $allAchievements = Achievements::evaluate($metrics['allTime'], $episodesCount);
        $achUnlocked     = array_values(array_filter($allAchievements, fn ($a) => $a['unlocked']));
        $achLocked       = array_values(array_filter($allAchievements, fn ($a) => ! $a['unlocked']));

        // ---- Recent comments (safe if table not present) --------------------
        $recentComments = collect();
        if (Schema::hasTable('comments')) {
            $recentComments = Comment::approved()
                ->latest()
                ->with(['user:id,name','episode:id,title'])
                ->limit(5)
                ->get();
        }
          // Mini series for tiles
        $series7      = $this->dailySeries(7);     // last 7 days (daily)
        $series30     = $this->dailySeries(30);    // last 30 days (daily)
        $series8days  = $this->dailySeries(8);     // 8 points for the "Yesterday" sparkline
        $seriesMonths = $this->monthlySeries(12);  // last 12 months (monthly)

        $tiles = [
            'yesterday' => [
                'label'  => 'Yesterday Downloads',
                'value'  => $metrics['yesterday'] ?? 0,
                'series' => $this->sample($series8days, 8),
                'color'  => '#22c55e',
            ],
            'last7' => [
                'label'  => 'Last 7 Days Downloads',
                'value'  => $metrics['last7'] ?? 0,
                'series' => $this->sample($series7, 8),
                'color'  => '#22c55e',
            ],
            'last30' => [
                'label'  => 'Last 30 Days Downloads',
                'value'  => $metrics['last30'] ?? 0,
                'series' => $this->sample($series30, 8),
                'color'  => '#06b6d4',
            ],
            'all' => [
                'label'  => 'All Time Downloads',
                'value'  => $metrics['allTime'] ?? 0,
                'series' => $this->sample($seriesMonths, 8), // monthly trend
                'color'  => '#f59e0b',
            ],
        ];

        // ---- Episode Performance  ------------------------------------------
        // Count downloads occurring in the FIRST 7/30 days after publish
        // (falls back to created_at if published_at is NULL).
        //
        // We build two subqueries (week/month) and LEFT JOIN them to episodes.
        // This is SQLite compatible via datetime(..., '+7 day') syntax.
        $firstWeekSub = DB::table('downloads')
            ->selectRaw('downloads.episode_id, COUNT(*) as c')
            ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
            ->whereRaw("downloads.created_at >= COALESCE(episodes.published_at, episodes.created_at)")
            ->whereRaw("downloads.created_at <  datetime(COALESCE(episodes.published_at, episodes.created_at), '+7 day')")
            ->groupBy('downloads.episode_id');

        $firstMonthSub = DB::table('downloads')
            ->selectRaw('downloads.episode_id, COUNT(*) as c')
            ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
            ->whereRaw("downloads.created_at >= COALESCE(episodes.published_at, episodes.created_at)")
            ->whereRaw("downloads.created_at <  datetime(COALESCE(episodes.published_at, episodes.created_at), '+30 day')")
            ->groupBy('downloads.episode_id');


            // ---- Downloads Trending (last 14 days) -------------------------------
        $days  = 14;
        $end   = now()->endOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();

        // Daily counts in the current window
        $raw = Download::selectRaw("date(created_at) as d, count(*) as c")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd')
            ->all();

        $series = [];
        $cursor = $start->copy();
        $currentTotal = 0;

        for ($i = 0; $i < $days; $i++) {
            $k = $cursor->toDateString();
            $v = $raw[$k] ?? 0;
            $series[] = ['date' => $k, 'count' => $v];
            $currentTotal += $v;
            $cursor->addDay();
        }    
        
        // Previous period totals (the 14 days immediately before the window)
        $prevStart  = $start->copy()->subDays($days);
        $prevEnd    = $start->copy()->subDay()->endOfDay();
        $prevTotal  = Download::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $delta      = $currentTotal - $prevTotal;
        $maxValue   = max(1, max(array_column($series, 'count')));

        $trending = [
            'series'        => $series,        // [{date, count}, ...]
            'total'         => $currentTotal,  // sum of series
            'prev_total'    => $prevTotal,     // previous 14-day total
            'delta'         => $delta,         // difference vs previous period
            'max'           => $maxValue,      // for chart scaling
            'days'          => $days,
            'start'         => $start,
            'end'           => $end,
        ];


        $episodesPerformance = Episode::query()
            ->selectRaw("
                episodes.id,
                episodes.title,
                COALESCE(w.c, 0)  as first_week,
                COALESCE(m.c, 0)  as first_month
            ")
            ->leftJoinSub($firstWeekSub,  'w', fn ($j) => $j->on('episodes.id', '=', 'w.episode_id'))
            ->leftJoinSub($firstMonthSub, 'm', fn ($j) => $j->on('episodes.id', '=', 'm.episode_id'))
            ->orderByDesc(DB::raw('COALESCE(m.c, 0)'))
            ->orderByDesc(DB::raw('COALESCE(w.c, 0)'))
            ->limit(8)
            ->get();

        return view('pages.dashboard', compact(
            'metrics',
            'achUnlocked',
            'achLocked',
            'allAchievements',
            'recentComments',
            'metrics',
            'achUnlocked',
            'achLocked',
            'allAchievements',
            'recentComments',
            'trending',
            'metrics', 
            'achUnlocked', 
            'achLocked', 
            'allAchievements',
            'recentComments', 
            'trending', 
            'tiles',
            'episodesPerformance'   // ← pass to blade
        ));
    }
        private function dailySeries(int $days): array
    {
        $end   = now()->endOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();

        $raw = Download::selectRaw('date(created_at) as d, count(*) as c')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('d')
            ->pluck('c', 'd')
            ->all();

        $series = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $days; $i++) {
            $k = $cursor->toDateString();
            $series[] = (int) ($raw[$k] ?? 0);
            $cursor->addDay();
        }
        return $series;
    }
    private function monthlySeries(int $months): array
    {
        $driver = DB::connection()->getDriverName();
        $expr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'mysql'  => "DATE_FORMAT(created_at, '%Y-%m')",
            default  => "to_char(created_at, 'YYYY-MM')", // pgsql
        };

        $end   = now()->startOfMonth();                 // current month
        $start = $end->copy()->subMonths($months - 1);  // N months back

        $raw = Download::selectRaw("$expr as ym, count(*) as c")
            ->whereBetween('created_at', [$start, $end->copy()->endOfMonth()])
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->all();

        $series = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $series[] = (int) ($raw[$key] ?? 0);
            $cursor->addMonth();
        }
        return $series;
    }
        private function sample(array $series, int $points = 8): array
    {
        $n = count($series);
        if ($n <= $points) return $series;

        $out = [];
        for ($i = 0; $i < $points; $i++) {
            $idx = (int) round($i * ($n - 1) / ($points - 1));
            $out[] = $series[$idx];
        }
        return $out;
    }
}
