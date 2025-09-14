<?php
// app/Http/Controllers/PlayerEmbedController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Episode;

class PlayerEmbedController extends Controller
{
    /** Core query: published episodes with audio */
    private function queryEpisodes(int $limit, string $order, ?string $onlySlug = null)
    {
        $q = Episode::query()
            ->whereNotNull('audio_path')
            ->where('status', 'published');

        if ($onlySlug) {
            $q->where('slug', $onlySlug);
        }

        $q = $order === 'oldest'
            ? $q->orderBy('published_at', 'asc')
            : $q->orderBy('published_at', 'desc');

        return $q->limit($limit)->get([
            'id','title','slug','description','published_at',
            'audio_path','audio_url','duration_sec',
            'image_url','image_path',      // ✅ include
            'cover_path',                  // ✅ include
        ]);
        
    }

    /** Iframe HTML endpoint (also builds Option A/B code boxes when ?showcode=1) */
    public function iframe(Request $req)
    {
        // Query params (with sane defaults)
        $limit    = (int) $req->integer('limit', 10);
        $order    = $req->string('order', 'newest')->lower()->value(); // newest|oldest
        $colorHex = ltrim((string) $req->get('color', '#7c3aed'), '#'); // brand color
        $font     = $req->string('font', 'system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif')->value();
        $theme    = $req->string('theme', 'light')->lower()->value(); // light|dark|auto
        $height   = $req->integer('height', 315);
        $share    = $req->boolean('share', true);
        $download = $req->boolean('download', true);
        $episode  = $req->string('episode', '')->value(); // optional slug to preselect
        $showcode = $req->boolean('showcode', false);

        $episodes = $this->queryEpisodes($limit, $order, null);

        // Build a consistent query for code blocks
        $query = http_build_query(array_filter([
            'limit'    => $limit ?: null,
            'order'    => $order ?: 'newest',
            'theme'    => $theme,
            'color'    => $colorHex,
            'share'    => $share ? 1 : 0,
            'download' => $download ? 1 : 0,
            'episode'  => $episode ?: null,
        ]));

        $iframeSrc = route('embed.player') . ($query ? ('?'.$query) : '');
        $scriptSrc = route('embed.player.script');

        // Prebuilt code snippets for the two options
        $embedA = <<<HTML
<iframe
  title="PowerTime"
  src="{$iframeSrc}"
  width="100%" height="{$height}"
  style="border:0;overflow:hidden"
  allow="autoplay"
  loading="lazy"></iframe>
HTML;

        $embedB = <<<HTML
<div data-powertime-player
     data-limit="{$limit}"
     data-order="{$order}"
     data-theme="{$theme}"
     data-color="#{$colorHex}"
     data-height="{$height}"
     data-share="{$share}"
     data-download="{$download}"
HTML;
        if ($episode) {
            $embedB .= "\n     data-episode=\"{$episode}\"";
        }
        $embedB .= "></div>\n<script async src=\"{$scriptSrc}\"></script>\n";

        return response()
            ->view('embed.player', [
                'episodes'      => $episodes,
                'brand'         => "#{$colorHex}",
                'font'          => $font,
                'theme'         => $theme,
                'height'        => $height,
                'share'         => $share,
                'download'      => $download,
                'preselectSlug' => $episode ?: null,
                'site'          => [
                    'title' => config('app.name', 'PowerTime'),
                    'link'  => rtrim(config('app.url'), '/'),
                ],
                'showcode'      => $showcode,
                'embedA'        => $embedA,
                'embedB'        => $embedB,
            ])
            // Security headers for embedding
            ->header('X-Frame-Options', 'ALLOWALL') // tighten to specific domains if desired
            ->header('Referrer-Policy', 'no-referrer-when-downgrade')
            ->header('Permissions-Policy', 'autoplay=(*), clipboard-write=()')
            ->header('Content-Security-Policy',
                "default-src 'self' *.jsdelivr.net *.cdnjs.com data: blob:; " .
                "img-src 'self' data: blob: https:; " .
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com data:; " .
                "media-src 'self' https: data: blob:; " .
                "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline' 'unsafe-eval'; "
            );
    }

    /** JS loader that injects an iframe automatically */
    public function script(Request $req)
    {
        $srcBase = route('embed.player');
        $js = <<<JS
(function(){
  var els = document.querySelectorAll('[data-powertime-player]');
  els.forEach(function(el){
    var params = new URLSearchParams();
    if (el.dataset.limit)    params.set('limit', el.dataset.limit);
    if (el.dataset.order)    params.set('order', el.dataset.order);
    if (el.dataset.color)    params.set('color', el.dataset.color.replace('#',''));
    if (el.dataset.font)     params.set('font', el.dataset.font);
    if (el.dataset.theme)    params.set('theme', el.dataset.theme);
    if (el.dataset.share)    params.set('share', el.dataset.share);
    if (el.dataset.download) params.set('download', el.dataset.download);
    if (el.dataset.episode)  params.set('episode', el.dataset.episode);

    var h = parseInt(el.dataset.height || '315', 10);
    var iframe = document.createElement('iframe');
    iframe.src = '{$srcBase}?'+params.toString();
    iframe.width = '100%';
    iframe.height = h;
    iframe.style.border = '0';
    iframe.allow = 'autoplay';
    iframe.loading = 'lazy';
    el.replaceWith(iframe);
  });
})();
JS;

        return response($js, 200)->header('Content-Type', 'application/javascript');
    }

    /** Optional: basic oEmbed (handy for platforms that support it) */
    public function oembed(Request $req)
    {
        $url = (string) $req->query('url', route('embed.player'));
        return response()->json([
            'version'       => '1.0',
            'type'          => 'rich',
            'provider_name' => config('app.name', 'PowerTime'),
            'provider_url'  => rtrim(config('app.url'), '/'),
            'title'         => 'PowerTime Podcast Player',
            'html'          => '<iframe src="'.e($url).'" width="100%" height="315" style="border:0" allow="autoplay"></iframe>',
            'width'         => 700,
            'height'        => 315,
        ]);
    }
}
