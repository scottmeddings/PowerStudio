<?php

namespace App\Http\Controllers;

use App\Models\PodcastDirectory;
use Illuminate\Http\Request;

class DistributionController extends Controller
{
    public function save(Request $request, string $slug)
    {
        $data = $request->validate([
            'external_url' => ['nullable','url'],
        ]);

        PodcastDirectory::updateOrCreate(
            ['user_id' => $request->user()->id, 'slug' => $slug],
            [
                'external_url' => $data['external_url'] ?? null,
                'is_connected' => !empty($data['external_url']),
            ]
        );

        return back()->with('success', ucfirst($slug).' settings saved.');
    }

    public function disconnect(Request $request, string $slug)
    {
        PodcastDirectory::updateOrCreate(
            ['user_id' => $request->user()->id, 'slug' => $slug],
            ['external_url' => null, 'is_connected' => false]
        );

        return back()->with('success', ucfirst($slug).' disconnected.');
    }
}

