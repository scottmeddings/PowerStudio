<?php

// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Download;
use App\Models\Episode;
use App\Support\Achievements;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $metrics = [
            'yesterday' => Download::whereDate('created_at', now()->subDay())->count(),
            'last7'     => Download::where('created_at', '>=', now()->subDays(7))->count(),
            'last30'    => Download::where('created_at', '>=', now()->subDays(30))->count(),
            'allTime'   => Download::count(),
        ];

        $episodesCount = Episode::count();

        $allAchievements = Achievements::evaluate($metrics['allTime'], $episodesCount);
        $achUnlocked = array_values(array_filter($allAchievements, fn ($a) => $a['unlocked']));
        $achLocked   = array_values(array_filter($allAchievements, fn ($a) => ! $a['unlocked']));

        return view('pages.dashboard', compact('metrics', 'achUnlocked', 'achLocked', 'allAchievements'));
    }
}
