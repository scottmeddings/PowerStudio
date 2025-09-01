<?php

namespace App\Support;

class Achievements
{
    public static function evaluate(int $downloadsTotal, int $episodesTotal): array
    {
        $downloads = collect(config('achievements.downloads', []))->map(function ($a) use ($downloadsTotal) {
            $unlocked = $downloadsTotal >= $a['threshold'];
            $progress = (int) min(100, floor(($downloadsTotal / max(1, $a['threshold'])) * 100));
            return array_merge($a, [
                'type'     => 'downloads',
                'current'  => $downloadsTotal,
                'unlocked' => $unlocked,
                'progress' => $progress,
                'remaining'=> max(0, $a['threshold'] - $downloadsTotal),
            ]);
        });

        $episodes = collect(config('achievements.episodes', []))->map(function ($a) use ($episodesTotal) {
            $unlocked = $episodesTotal >= $a['threshold'];
            $progress = (int) min(100, floor(($episodesTotal / max(1, $a['threshold'])) * 100));
            return array_merge($a, [
                'type'     => 'episodes',
                'current'  => $episodesTotal,
                'unlocked' => $unlocked,
                'progress' => $progress,
                'remaining'=> max(0, $a['threshold'] - $episodesTotal),
            ]);
        });

        return $downloads->concat($episodes)->values()->all();
    }
}
