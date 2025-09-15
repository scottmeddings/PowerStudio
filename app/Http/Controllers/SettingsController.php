<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\SiteSetting; // alias of Setting, mapped to 'settings' table

class SettingsController extends Controller
{
    /* ---------- util: compute canonical feed URL (read-only) ---------- */
    private function computeFeedUrl(SiteSetting $s, Request $r): string
    {
        // If a site URL is configured, feed lives directly under it
        $site = trim((string) ($s->site_link ?? ''));
        if ($site !== '') {
            return rtrim($site, '/') . '/feed.xml';
        }

        // Otherwise: {base}/{slug}/feed.xml
        $base = rtrim((string) (config('app.url') ?: $r->getSchemeAndHttpHost()), '/');

        // prefer explicit subdomain/slug, else slug of title, else 'podcast'
        $slug = trim((string) ($s->podcast_subdomain ?? ''));
        if ($slug === '') {
            $slug = Str::slug((string) ($s->site_title ?? 'podcast'), '-');
            if ($slug === '') $slug = 'podcast';
        }

        return "{$base}/{$slug}/feed.xml";
    }

    /* ---------- General ---------- */
    public function general()
    {
        $s = SiteSetting::singleton();

        return view('settings.general', [
            'title' => $s->site_title ?? 'MyPodcast',
            'description'         => $s->site_desc         ?? '',
            'category'            => $s->site_category     ?? 'Technology',

            // show current site link + the editable subdomain piece
            'site_url'            => $s->site_link         ?? null,
            'subdomain'           => $s->podcast_subdomain ?? '',

            'language'            => $s->site_lang         ?? 'en-us',
            'country'             => $s->site_country      ?? 'Global',
            'timezone'            => $s->site_timezone     ?? config('app.timezone', 'UTC'),
            'podcast_type'        => $s->site_type         ?? 'episodic',
            'download_visibility' => $s->episode_download_visibility ?? 'hidden',
            'site_top_bar'        => ($s->site_topbar_show ?? true) ? 'show' : 'hide',
        ]);
    }

    public function updateGeneral(Request $r)
    {
        $data = $r->validate([
            'title'               => ['required','string','max:120'],
            'description'         => ['nullable','string','max:5000'],
            'category'            => ['required','string','max:60'],

            // users type only the left part of the domain
            'site_subdomain'      => ['nullable','regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i'],

            'language'            => ['nullable','string','max:20'],
            'country'             => ['nullable','string','max:40'],
            'timezone'            => ['nullable','string','max:64'],
            'podcast_type'        => ['nullable','in:episodic,serial'],
            'download_visibility' => ['nullable','in:hidden,public'],
            'site_top_bar'        => ['nullable','in:show,hide'],
        ]);

        $s = SiteSetting::singleton();

        $s->site_title     = $data['title'];
        $s->site_desc      = $data['description'] ?? null;
        $s->site_category  = $data['category'];

        // build locked domain https://{sub}.powerpod.com
        $sub = strtolower(trim($data['site_subdomain'] ?? ''));
        $s->podcast_subdomain = $sub ?: null;
        $s->site_link         = $sub ? "https://{$sub}.powerpod.com" : null;

        $s->site_lang      = $data['language']   ?? null;
        $s->site_country   = $data['country']    ?? null;
        $s->site_timezone  = $data['timezone']   ?? null;
        $s->site_type      = $data['podcast_type'] ?? 'episodic';

        $s->episode_download_visibility = $data['download_visibility'] ?? 'hidden';
        $s->site_topbar_show            = (($data['site_top_bar'] ?? 'show') === 'show');

        // recompute & persist read-only feed URL
        $s->feed_url = $this->computeFeedUrl($s, $r);

        $s->save();

        return back()->with('ok', 'General settings updated.');
    }

    /* ---------- Feed ---------- */
    public function feed()
    {
        $s = SiteSetting::singleton();

        return view('settings.feed', [
            // Read-only, computed
            'feed_url'               => $this->computeFeedUrl($s, request()),
            'explicit'               => (bool) ($s->feed_explicit ?? false),
            'apple_summary'          => $s->feed_apple_summary ?? '',

            // show the current site link for reference (but not editable here)
            'site_url'               => $s->site_link ?? '',

            'episode_link'           => $s->feed_episode_link ?? 'podpower', // podpower|external
            'episode_number_limit'   => (int) ($s->feed_episode_limit ?? 100),
            'episode_artwork_tag'    => $s->feed_episode_artwork_tag ?? 'itunes',

            'ownership_verification_email' => $s->feed_ownership_email ?? '',
            'apple_podcasts_verification'  => (bool) ($s->feed_apple_verification ?? false),
            'remove_from_apple_directory'  => (bool) ($s->feed_remove_from_directory ?? false),
            'set_podcast_new_feed_url'     => (bool) ($s->feed_set_new_feed_url ?? false),

            'redirect_to_new_feed'         => $s->feed_redirect_url ?? '',
        ]);
    }

    public function updateFeed(Request $r)
    {
        $data = $r->validate([
            // feed_url and site_url are read-only / derived
            'explicit'               => ['required','in:0,1'],
            'apple_summary'          => ['nullable','string','max:4000'],

            'episode_link'           => ['required','in:podpower,external'],
            'episode_number_limit'   => ['required','integer','min:1','max:1000'],
            'episode_artwork_tag'    => ['required','in:itunes,episode'],

            'ownership_verification_email' => ['nullable','email','max:255'],
            'apple_podcasts_verification'  => ['nullable','boolean'],
            'remove_from_apple_directory'  => ['nullable','boolean'],
            'set_podcast_new_feed_url'     => ['nullable','boolean'],
            'redirect_to_new_feed'         => ['nullable','url','max:2048'],
        ]);

        $s = SiteSetting::singleton();

        // Basics
        $s->feed_explicit             = (bool) $data['explicit'];
        $s->feed_apple_summary        = $data['apple_summary'] ?? '';

        // Advanced
        $s->feed_episode_link         = $data['episode_link']; // podpower|external
        $s->feed_episode_limit        = (int) $data['episode_number_limit'];
        $s->feed_episode_artwork_tag  = $data['episode_artwork_tag'];

        $s->feed_ownership_email       = $data['ownership_verification_email'] ?? null;
        $s->feed_apple_verification    = (bool) ($data['apple_podcasts_verification'] ?? false);
        $s->feed_remove_from_directory = (bool) ($data['remove_from_apple_directory'] ?? false);
        $s->feed_set_new_feed_url      = (bool) ($data['set_podcast_new_feed_url'] ?? false);

        $s->feed_redirect_url          = $data['redirect_to_new_feed'] ?? null;
        $s->feed_redirect_enabled      = !empty($s->feed_redirect_url);

        // recompute & persist read-only feed URL
        $s->feed_url = $this->computeFeedUrl($s, $r);

        $s->save();

        return back()->with('ok', 'Feed settings updated.');
    }

    /* ---------- Plugins (kept minimal; only if you later add a JSON/array column) ---------- */
    public function plugins()
    {
        $s = SiteSetting::singleton();

        $enabled = [];
        if (Schema::hasColumn($s->getTable(), 'plugins_enabled')) {
            $enabled = (array) ($s->plugins_enabled ?? []);
        }

        return view('settings.plugins', [
            'enabled_plugins' => $enabled,
        ]);
    }

    public function updatePlugins(Request $r)
    {
        $s = SiteSetting::singleton();

        if (!Schema::hasColumn($s->getTable(), 'plugins_enabled')) {
            return back()->with('ok', 'Plugins not supported yet.');
        }

        $plugins = array_values($r->input('plugins', []));
        $s->plugins_enabled = $plugins;
        $s->save();

        return back()->with('ok', 'Plugins updated.');
    }

    /* ---------- Import (only if you add these columns later) ---------- */
    public function import()
    {
        $s = SiteSetting::singleton();

        return view('settings.import', [
            'import_feed_url' => Schema::hasColumn($s->getTable(), 'import_feed_url') ? ($s->import_feed_url ?? '') : '',
            'do_301'          => Schema::hasColumn($s->getTable(), 'import_do_301') ? (bool) ($s->import_do_301 ?? false) : false,
        ]);
    }

    public function handleImport(Request $r)
    {
        $s = SiteSetting::singleton();

        if (!Schema::hasColumn($s->getTable(), 'import_feed_url')) {
            return back()->with('ok', 'Import settings not supported yet.');
        }

        $data = $r->validate([
            'import_feed_url' => ['required','url','max:2048'],
            'do_301'          => ['nullable','boolean'],
        ]);

        $s->import_feed_url = $data['import_feed_url'];
        if (Schema::hasColumn($s->getTable(), 'import_do_301')) {
            $s->import_do_301 = (bool) ($data['do_301'] ?? false);
        }

        $s->save();

        return back()->with('ok', 'Import settings saved.');
    }
}
