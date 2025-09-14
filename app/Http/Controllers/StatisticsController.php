<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Download;

class StatisticsController extends Controller
{
    /** Legacy alias if a route still calls @statistics */
    public function statistics(Request $request)
    {
        return $this->index($request);
    }

    /** GET /statistics or /statistics/range/{range} */
    public function index(Request $request)
    {
        // Accept route param or query param
        $daysParam = $request->route('range') ?? $request->query('range', 30);
        $days = (int) $daysParam;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;

        $to   = Carbon::today();
        $from = (clone $to)->subDays($days - 1);

        // Per-day series
        $raw = Download::selectRaw('date(created_at) as d, count(*) as c')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd')
            ->all();

        $series = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $k = $cursor->toDateString();
            $series[] = ['date' => $k, 'count' => (int)($raw[$k] ?? 0)];
            $cursor->addDay();
        }

        $totals = [
            'range'     => array_sum(array_column($series, 'count')),
            'yesterday' => Download::whereDate('created_at', $to->copy()->subDay()->toDateString())->count(),
            'last7'     => Download::whereBetween('created_at', [$to->copy()->subDays(6)->startOfDay(), $to->endOfDay()])->count(),
            'all'       => Download::count(),
        ];

        $topEpisodes = DB::table('downloads')
            ->join('episodes', 'downloads.episode_id', '=', 'episodes.id')
            ->whereBetween('downloads.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select('episodes.id', 'episodes.title', DB::raw('COUNT(downloads.id) as downloads'))
            ->groupBy('episodes.id', 'episodes.title')
            ->orderByDesc('downloads')
            ->limit(10)
            ->get();

        $counts = array_map(fn ($r) => (int)$r['count'], $series);

        $sparks = [
            'range'     => $this->buildSparkPoints($counts),
            'yesterday' => $this->buildSparkPoints($this->windowYesterday($counts)),
            'last7'     => $this->buildSparkPoints(array_slice($counts, max(0, count($counts) - 7))),
            'all'       => $this->buildSparkPoints($counts),
        ];

        return view('pages.statistics', [
            'series'      => $series,
            'totals'      => $totals,
            'topEpisodes' => $topEpisodes,
            'days'        => $days,
            'from'        => $from,
            'to'          => $to,
            'sparks'      => $sparks,
        ]);
    }

    /** Convert numeric series to "x,y x,y ..." for a 100x28 sparkline. */
    private function buildSparkPoints(array $values, int $w = 100, int $h = 28): string
    {
        if (count($values) < 2) {
            $v = $values[0] ?? 0;
            $values = [$v, $v];
        }
        $n   = count($values);
        $max = max(1, max($values));
        $pts = [];
        for ($i = 0; $i < $n; $i++) {
            $x = ($i / max(1, $n - 1)) * $w;
            $y = $h - (($values[$i] / $max) * ($h - 4)) - 2;
            $pts[] = round($x, 1) . ',' . round($y, 1);
        }
        return implode(' ', $pts);
    }

    /** Return up to 8 points ending at yesterday (treat last bucket as today). */
    private function windowYesterday(array $counts): array
    {
        $n = count($counts);
        if ($n === 0) return [0, 0];
        $end = $n >= 2 ? $n - 2 : $n - 1;
        $start = max(0, $end - 7);
        return array_slice($counts, $start, $end - $start + 1);
    }
}
