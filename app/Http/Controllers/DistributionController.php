<?php

namespace App\Http\Controllers;

use App\Models\PodcastDirectory;
use App\Models\Episode;
use App\Models\Download;
use App\Models\SocialConnection;               // ← NEW
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;                    // ← for stub tokens

class DistributionController extends Controller
{
    /** Landing page: /distribution */
    public function index()
    {
        return view('distribution.index');
    }
    public function player(Request $req)
    {
        $limit = (int) $req->integer('limit', 10);
        $order = $req->string('order', 'newest')->lower()->value(); // newest|oldest

        $episodes = Episode::query()
            ->publishedWithAudio()
            ->when($order === 'oldest', fn($q) => $q->orderBy('published_at','asc'),
                                 fn($q) => $q->orderBy('published_at','desc'))
            ->limit($limit)
            ->get([
            'id','title','slug','description','published_at',
            'audio_path','audio_url','duration_sec',
            'image_url','image_path',      // ✅ include
            'cover_path',                  // ✅ include
        ]);
            

        // Pass through the same view vars your Blade defaults can use/override
        return view('distribution.player', [
            'episodes'      => $episodes,
            'theme'         => $req->get('theme', 'light'),
            'brand'         => '#'.ltrim((string)$req->get('color', '#7c3aed'), '#'),
            'height'        => (int)$req->integer('height', 315),
            'share'         => $req->boolean('share', true),
            'download'      => $req->boolean('download', true),
            'showcode'      => $req->boolean('showcode', true),
            'preselectSlug' => $req->get('episode'),
            'site'          => [
                'title' => config('app.name', 'PowerTime'),
                'link'  => rtrim(config('app.url'), '/'),
            ],
            // these help the embed-code builder compute URLs without errors
            'limitParam'    => $limit,
            'orderParam'    => $order,
        ]);
    }
    /** POST: connect/save a directory (Podcast Apps) */
    public function save(Request $request, string $slug)
    {
        $data = $request->validate([
            'external_url' => ['nullable', 'url'],
        ]);

        PodcastDirectory::updateOrCreate(
            ['user_id' => $request->user()->id, 'slug' => $slug],
            [
                'external_url' => $data['external_url'] ?? null,
                'is_connected' => !empty($data['external_url']),
            ]
        );

        return back()->with('success', ucfirst($slug) . ' settings saved.');
    }

    /** DELETE: disconnect a directory (Podcast Apps) */
    public function disconnect(Request $request, string $slug)
    {
        PodcastDirectory::updateOrCreate(
            ['user_id' => $request->user()->id, 'slug' => $slug],
            ['external_url' => null, 'is_connected' => false]
        );

        return back()->with('success', ucfirst($slug) . ' disconnected.');
    }

    /** GET: /distribution/apps (Podcast Apps) */
    public function apps()
    {
        $rss = url('/feed/podcast.xml');

        // Stats for the current user
        $episodeIds = Episode::where('user_id', Auth::id())->pluck('id');

        $base = Download::query()
            ->when($episodeIds->isNotEmpty(), fn ($q) => $q->whereIn('episode_id', $episodeIds));

        $yesterday = (clone $base)->whereDate('created_at', now()->subDay()->toDateString())->count();
        $last7   = (clone $base)->where('created_at', '>=', now()->subDays(6)->startOfDay())->count();
        $last30  = (clone $base)->where('created_at', '>=', now()->subDays(29)->startOfDay())->count();
        $allTime = (clone $base)->count();

        $stats = [
            ['label' => 'Yesterday Downloads',    'value' => $yesterday, 'color' => 'green'],
            ['label' => 'Last 7 Days Downloads',  'value' => $last7,     'color' => 'green'],
            ['label' => 'Last 30 Days Downloads', 'value' => $last30,    'color' => 'blue'],
            ['label' => 'All Time Downloads',     'value' => $allTime,   'color' => 'orange'],
        ];

        // Known directories
        $defaults = [
            'apple'       => ['name' => 'Apple Podcasts', 'icon' => 'pi-apple',   'color' => '#a970ff'],
            'spotify'     => ['name' => 'Spotify',        'icon' => 'pi-spotify', 'color' => '#1db954'],
            'ytmusic'     => ['name' => 'YouTube Music',  'icon' => 'pi-ytm',     'color' => '#ff0033'],
            'amazon'      => ['name' => 'Amazon Music',   'icon' => 'pi-amazon',  'color' => '#00a8e1'],
            'iheart'      => ['name' => 'iHeartRadio',    'icon' => 'pi-iheart',  'color' => '#c6002b'],
            'tunein'      => ['name' => 'TuneIn',         'icon' => 'pi-tunein',  'color' => '#14a0a0'],
            'pocketcasts' => ['name' => 'Pocket Casts',   'icon' => 'pi-pocket',  'color' => '#f43f5e'],
            'overcast'    => ['name' => 'Overcast',       'icon' => 'pi-over',    'color' => '#ff7a00'],
            'castbox'     => ['name' => 'Castbox',        'icon' => 'pi-castbx',  'color' => '#f65e3b'],
            'deezer'      => ['name' => 'Deezer',         'icon' => 'pi-deezer',  'color' => '#121216'],
            'pandora'     => ['name' => 'Pandora',        'icon' => 'pi-pand',    'color' => '#224099'],
        ];

        $saved = class_exists(PodcastDirectory::class)
            ? PodcastDirectory::where('user_id', Auth::id())->get()->keyBy('slug')
            : collect();

        $directories = collect($defaults)->map(function (array $meta, string $slug) use ($saved) {
            $row = $saved->get($slug);
            return [
                'slug'         => $slug,
                'name'         => $meta['name'],
                'icon'         => $meta['icon'],
                'color'        => $meta['color'],
                'connected'    => (bool) optional($row)->is_connected,
                'external_url' => optional($row)->external_url,
                'id'           => optional($row)->id,
            ];
        })->values();

        $connected = $directories->mapWithKeys(fn ($d) => [$d['slug'] => $d['connected']])->all();

        return view('distribution.apps', compact(
            'rss', 'directories', 'connected', 'yesterday', 'last7', 'last30', 'allTime', 'stats'
        ));
    }

    /** GET: /distribution/social (landing) */
    public function social()
    {
        $providers   = ['facebook','linkedin','youtube','tumblr','wordpress'];
        $connections = SocialConnection::where('user_id', Auth::id())->get()->keyBy('provider');

        return view('distribution.social', compact('providers','connections'));
    }

    /** Start OAuth/Connect (dev stub – replace with real redirect) */
    public function socialRedirect(Request $request, string $provider)
    {
        return redirect()->route('distribution.social.callback', [
            'provider' => $provider,
            'code'     => Str::random(16),
        ]);
    }

    /** OAuth callback: store tokens & mark connected */
    public function socialCallback(Request $request, string $provider)
    {
        SocialConnection::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => $provider],
            [
                'access_token' => 'fake-token-'.Str::random(24),
                'refresh_token'=> null,
                'expires_at'   => now()->addDays(30),
                'settings'     => ['note' => 'dev stub'],
                'is_connected' => true,
            ]
        );

        return redirect()->route('distribution.social')
            ->with('success', ucfirst($provider).' connected.');
    }

    /** Disconnect a social provider */
    public function socialDisconnect(Request $request, string $provider)
    {
        SocialConnection::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => $provider],
            ['access_token'=>null,'refresh_token'=>null,'expires_at'=>null,'settings'=>null,'is_connected'=>false]
        );

        return back()->with('success', ucfirst($provider).' disconnected.');
    }

    /** Send a quick test post using the latest published episode */
    public function socialTest(Request $request, string $provider)
    {
        $conn = SocialConnection::where('user_id', $request->user()->id)
            ->where('provider',$provider)->where('is_connected',true)->firstOrFail();

        $episode = Episode::where('user_id',$request->user()->id)
            ->whereNotNull('published_at')->latest('published_at')->first();

        if (!$episode) {
            return back()->with('error', 'No published episode to share.');
        }

        $this->pushToProvider($conn, $episode);

        return back()->with('success', 'Shared test post to '.ucfirst($provider).'.');
    }

    /** Central share helper – replace bodies with real API calls later */
    public function pushToProvider(SocialConnection $conn, Episode $episode): void
    {
        $link    = url('/episodes/'.$episode->id); // adjust to public URL if different
        $message = trim(($episode->title ?? 'New episode').' — '.($episode->summary ?? '').' '.$link);

        switch ($conn->provider) {
            case 'facebook':
                logger()->info('[SOCIAL] Facebook post', compact('message'));
                break;
            case 'linkedin':
                logger()->info('[SOCIAL] LinkedIn post', compact('message'));
                break;
            case 'youtube':
                logger()->info('[SOCIAL] YouTube post', compact('message'));
                break;
            case 'tumblr':
                logger()->info('[SOCIAL] Tumblr post', compact('message'));
                break;
            case 'wordpress':
                logger()->info('[SOCIAL] WordPress post', compact('message'));
                break;
        }
    }

    /** GET: /distribution/website */
    public function website()
    {
        return view('distribution.website');
    }

 
}
