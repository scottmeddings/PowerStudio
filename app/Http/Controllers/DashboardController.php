<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Download;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Collect metrics
        $metrics = [
            'yesterday' => Download::whereDate('created_at', now()->subDay())->count(),
            'last7'     => Download::where('created_at', '>=', now()->subDays(7))->count(),
            'last30'    => Download::where('created_at', '>=', now()->subDays(30))->count(),
            'allTime'   => Download::count(),
        ];

        // Pass the array so your dashboard.blade.php can use $metrics['...']
        return view('pages.dashboard', compact('metrics'));

    }
}
