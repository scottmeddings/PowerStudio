<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Models\Episode;
use App\Models\Setting;

class PodcastFeedController extends Controller
{
    public function index(Request $request): Response
    {
        $settings = Setting::singleton();

        $episodes = Episode::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit((int)($settings->episode_number_limit ?? 300))
            ->get();

        // ---------- Build a proper Carbon $lastUpdated ----------
        $coerceToCarbon = function ($v): ?Carbon {
            if ($v instanceof \DateTimeInterface) return Carbon::instance($v);
            if (is_string($v) || is_int($v) || is_float($v)) return Carbon::parse($v);
            return null;
        };

        $epMaxUpdated = $episodes->max('updated_at');          // Carbon|DateTime|string|null
        $setUpdated   = $settings->updated_at ?? null;          // Carbon|DateTime|string|null

        $lastUpdated = collect([
            $coerceToCarbon($epMaxUpdated),
            $coerceToCarbon($setUpdated),
        ])->filter()->max() ?? now();

        // ---------- ETag ----------
        $etag = '"'.sha1(implode('|', [
            $lastUpdated->toIso8601String(),
            (string) $episodes->count(),
            (string) ($settings->site_title ?? ''),
            (string) ($settings->site_link ?? ''),
        ])).'"';

        // Short-circuit 304
        if (trim((string)$request->header('If-None-Match')) === $etag) {
            return new Response('', 304, [
                'ETag'                   => $etag,
                'Cache-Control'          => 'public, max-age=300',
                'Vary'                   => 'Accept-Encoding',
                'X-Content-Type-Options' => 'nosniff',
                'Last-Modified'          => $lastUpdated->toRfc7231String(),
            ]);
        }

        // ---------- Data for the view ----------
        $site = [
            'title'         => $settings->site_title ?: '',
            'link'          => rtrim((string)($settings->site_link ?? config('app.url')), '/'),
            'lang'          => $settings->site_lang ?: 'en-us',
            'desc'          => $settings->site_desc ?: ($settings->site_itunes_summary ?: ''),
            'itunes_author' => $settings->site_itunes_author ?: '',
            // Atom "self" link
            'self_feed_url' => $settings->feed_url ?: url('/feed.xml'),
        ];

        $itunes = [
            // for <itunes:owner>
            'owner_name'   => $settings->site_owner_name ?: '',
            'owner_email'  => $settings->site_owner_email ?: '',

            // for <itunes:block> and <itunes:explicit>
            'block'        => ($settings->itunes_block ?? 'No') === 'Yes' ? 'Yes' : 'No',
            'explicit'     => ($settings->itunes_explicit ?? $settings->site_explicit ?? false) ? 'true' : 'false',

            // for <itunes:new-feed-url> and <itunes:image>
            'new_feed_url' => $settings->feed_url ?: '',
            'image_href'   => $settings->site_itunes_image ?: '',

            // optional
            'category'     => $settings->site_category ?: '',
            'type'         => $settings->site_type ?: '',
        ];

        // classic RSS <image>
        $image = [
            'url'    => $settings->site_image_url ?: $itunes['image_href'],
            'title'  => $site['title'],
            'link'   => $site['link'],
            'width'  => 144,
            'height' => 144,
        ];

        $xml = view('feed.podcast', compact('site', 'itunes', 'image', 'episodes'))->render();

        return new Response($xml, 200, [
            'content-type' => 'text/xml',
            'Cache-Control'           => 'public, max-age=300',
            'ETag'                    => $etag,
            'Last-Modified'           => $lastUpdated->toRfc7231String(),
            'Vary'                    => 'Accept-Encoding',
            'X-Content-Type-Options'  => 'nosniff',
        ]);
    }
}
