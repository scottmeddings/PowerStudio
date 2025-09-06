<?php

// app/Http/Controllers/PodcastAppController.php
namespace App\Http\Controllers;

use App\Models\PodcastApp;
use Illuminate\Http\Request;

class PodcastAppController extends Controller
{
    // Upsert from modal
    public function upsert(Request $request, string $provider)
    {
        $user = $request->user();

        $data = $request->validate([
            'external_url' => ['nullable','url'],
            // Anything provider-specific can land in config[]
            'config'       => ['array'],
            'action'       => ['nullable','in:submit,save'], // submit sets status
        ]);

        $app = PodcastApp::firstOrNew([
            'user_id'  => $user->id,
            'provider' => $provider,
        ]);

        $app->external_url = $data['external_url'] ?? $app->external_url;
        $app->config       = $data['config'] ?? ($app->config ?? []);

        if (($data['action'] ?? 'save') === 'submit') {
            $app->status       = 'submitted';
            $app->submitted_at = now();
        } else {
            $app->status ??= 'draft';
        }

        $app->save();

        return back()->with('success', $app->displayName().' settings saved.');
    }

    // Quick "reset" like your “Delete the podcast URL…” link
    public function destroy(Request $request, string $provider)
    {
        $app = PodcastApp::where('user_id', $request->user()->id)
            ->where('provider', $provider)->firstOrFail();

        $app->forceFill([
            'status'       => 'draft',
            'external_url' => null,
            'config'       => null,
            'submitted_at' => null,
            'connected_at' => null,
        ])->save();

        return back()->with('success', $app->displayName().' configuration cleared.');
    }
}
