<?php

// app/Http/Controllers/PodcastFeedController.php
namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Models\Episode;
use App\Models\Setting;

class PodcastFeedController extends Controller
{
    // app/Http/Controllers/PodcastFeedController.php
public function index(): \Illuminate\Http\Response
{
    $settings = Setting::singleton();

    $episodes = Episode::query()
        ->where('status', 'published')
        ->whereNotNull('published_at')
        ->orderByDesc('published_at')
        ->orderByDesc('id')
        ->limit(300)
        ->get();

    // Strong-ish ETag based on settings + latest episode update
    $etag = '"'.sha1(
        implode('|', [
            optional($episodes->max('updated_at'))->toIso8601String(),
            (string) $episodes->count(),
            optional($settings->updated_at)->toIso8601String(),
        ])
    ).'"';

    // Short-circuit 304s
    if (trim((string)request()->header('If-None-Match')) === $etag) {
        return response('', 304)->withHeaders([
            'ETag'                   => $etag,
            'Cache-Control'          => 'public, max-age=300',
            'Vary'                   => 'Accept-Encoding',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // Render the XML via Blade (make sure the view starts with the XML declaration)
    $xml = view('feed.podcast', [
        'site' => [
            'title'           => $settings->site_title,
            'link'            => rtrim((string)($settings->site_link ?? config('app.url')), '/'),
            'lang'            => $settings->site_lang,
            'desc'            => $settings->site_desc,
            'itunes_author'   => $settings->site_itunes_author,
            'owner_name'      => $settings->site_owner_name,
            'owner_email'     => $settings->site_owner_email,
            'itunes_image'    => $settings->site_itunes_image,
            'itunes_category' => $settings->site_category,
            'itunes_type'     => $settings->site_type,
            'self_feed_url'   => url('/feed.xml'),
        ],
        'episodes' => $episodes,
    ])->render();

    return response($xml)->withHeaders([
       'content-type' => 'text/xml'
    ]);
}

}
