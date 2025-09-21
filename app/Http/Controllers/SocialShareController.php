<?php

// app/Http/Controllers/SocialShareController.php
namespace App\Http\Controllers;

use App\Http\Requests\SocialPostRequest;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Http\Request;
use App\Jobs\PublishToLinkedInJob;  

class SocialShareController extends Controller
{
    public const PROVIDERS = ['x','linkedin','facebook','instagram','threads','youtube','tiktok'];

// app/Http/Controllers/SocialShareController.php
    public function index(Request $r)
    {
        $user = $r->user();

        // Providers we render in the UI (same slugs as your Blade)
        $providers = ['x','linkedin','facebook','instagram','threads','youtube','tiktok'];

        // Grab connected providers from DB for this user
        $connected = $user->socialAccounts()
            ->pluck('provider')        // e.g. ['linkedin']
            ->unique()
            ->values()
            ->all();

        // Build a full map: ['x'=>false, 'linkedin'=>true, ...]
        $socialConnected = collect($providers)
            ->mapWithKeys(fn ($p) => [$p => in_array($p, $connected, true)])
            ->toArray(); // pass as plain array for Blade indexing

        $directories = collect([]); // or your real data

        return view('pages.distribution_social', compact('socialConnected','directories'));
    }

    public function oauthStart(Request $r, string $provider)
    {
        // Placeholder “connected” state (swap to real OAuth later).
        $r->validate([
            'username' => ['required','string','max:190'],
            'password' => ['required','string','max:190'], // not stored; just demo
        ]);

        SocialAccount::updateOrCreate(
            ['user_id'=>$r->user()->id, 'provider'=>$provider],
            ['external_id'=>substr(sha1($r->user()->id.$provider.now()),0,12), 'meta'=>['demo'=>true]]
        );

        return back()->with('ok', ucfirst($provider).' connected.');
    }

    public function disconnect(Request $r, string $provider)
    {
        $r->user()->socialAccounts()->where('provider',$provider)->delete();
        return back()->with('ok', ucfirst($provider).' disconnected.');
    }

        public function createPost(Request $req)
    {
        $user = $req->user();
        $services = collect($req->input('services', []))->map(fn($s)=>strtolower($s))->unique()->values()->all();

        $post = SocialPost::create([
            'user_id'     => $user->id,
            'title'       => (string) $req->input('title', ''),
            'body'        => (string) $req->input('body', ''),
            'episode_url' => (string) $req->input('episode_url', ''),
            'visibility'  => (string) $req->input('visibility', 'public'),
            'services'    => $services,
            'status'      => 'queued',
        ]);

        if (in_array('linkedin', $services, true) &&
            $user->socialAccounts()->where('provider','linkedin')->exists()) {

            // Correct namespace
            PublishToLinkedInJob::dispatch($post->id)->onQueue('default');
        }

        return redirect()->route('distribution.social')->with('ok','Post created and queued.');
    }

}
