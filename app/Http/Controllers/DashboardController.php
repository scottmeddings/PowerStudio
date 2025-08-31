<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Download;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // minimal example – keep whatever metrics you already had
        $yesterdayDownloads = Download::whereDate('created_at', now()->subDay())->count();
        $last7  = Download::where('created_at', '>=', now()->subDays(7))->count();
        $last30 = Download::where('created_at', '>=', now()->subDays(30))->count();
        $all    = Download::count();

        return view('dashboard', compact('yesterdayDownloads','last7','last30','all'));
    }
}
