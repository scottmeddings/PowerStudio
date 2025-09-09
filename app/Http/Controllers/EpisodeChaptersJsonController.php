<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\JsonResponse;

class EpisodeChaptersJsonController extends Controller
{
    public function show(Episode $episode): JsonResponse
    {
        // Public chapters payload (Podcasting 2.0 JSON)
        $chapters = $episode->chapters()
            ->orderBy('sort')
            ->get()
            ->map(function ($c) {
                return [
                    'startTime' => max(0, (float) (($c->starts_at_ms ?? 0) / 1000)),
                    'title'     => (string) $c->title,
                ];
            });

        return response()->json([
            'version'  => '1.2.0',
            'chapters' => $chapters,
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
