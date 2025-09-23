<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\SiteSetting;

class WebsiteController extends Controller
{
    private const SETTINGS_KEY = 'website';
    private const CACHE_KEY    = 'website:settings';

    /** User-scoped keys (no DB migration required) */
    private function userKey(?int $userId): string {
        return sprintf('%s:u:%s', self::SETTINGS_KEY, $userId ?? 'guest');
    }
    private function userCacheKey(?int $userId): string {
        return sprintf('%s:u:%s', self::CACHE_KEY, $userId ?? 'guest');
    }

    // ------- PAGES -------
    public function edit(Request $request)
    {
        $userId   = $request->user()?->id;
        $setKey   = $this->userKey($userId);
        $settings = $this->settingsWithDefaults(SiteSetting::getValue($setKey, []) ?? []);

        $templates = [
            ['slug'=>'zen','name'=>'Zen 1.0','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Large banner image, simple episode list, fast loads.'],
            ['slug'=>'frontrow','name'=>'Frontrow','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Profile card left, episodes right, great for bios.'],
            ['slug'=>'focuspod','name'=>'Focuspod','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Hero image with grid of episodes and tags.'],
        ];

        // Resolve banner URL (optional: the Blade can also handle this)
        $bannerUrl = null;
        if (!empty($settings['banner']) && !Str::startsWith($settings['banner'], ['http://','https://'])) {
            $bannerUrl = Storage::disk('public')->url($settings['banner']);
        } elseif (!empty($settings['banner'])) {
            $bannerUrl = $settings['banner'];
        }

        return view('distribution.website', compact('settings','templates','bannerUrl'));
    }
    public function userSite(Request $request, int $user, ?string $template = null, ?string $siteSlug = null)
    {
        // Load saved settings for that user
        $settings = SiteSetting::getValue("website:u:$user", []) ?? [];

        // Optional slug guard (if you’re including one in the URL)
        if (!empty($siteSlug) && isset($settings['site_slug']) && $siteSlug !== $settings['site_slug']) {
            // 404 if the slug doesn’t match, adjust to taste
            abort(404);
        }

        // Decide which template to render
        $tpl = $template ?: ($settings['template'] ?? 'zen');
        $tpl = $this->validateTemplate($tpl); // keep your existing validator

        // Build your view payload – reuse your existing helper if you have one
        $payload = $this->payload($this->settingsWithDefaults($settings), $tpl, /*preview*/ (bool) $template);

        return view("site.templates.$tpl", $payload);
    }

    public function show(Request $request)
    {
        $userId   = $request->user()?->id;
        $setKey   = $this->userKey($userId);
        $cacheKey = $this->userCacheKey($userId);

        $settings = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($setKey) {
            return $this->settingsWithDefaults(SiteSetting::getValue($setKey, []) ?? []);
        });

        $tpl     = $this->validateTemplate($settings['template']);
        $payload = $this->payload($settings, $tpl, false);
        return view("site.templates.$tpl", $payload);
    }

    public function preview(Request $request, string $template)
    {
        $userId   = $request->user()?->id;
        $setKey   = $this->userKey($userId);
        $settings = $this->settingsWithDefaults(SiteSetting::getValue($setKey, []) ?? []);
        $settings['template'] = $this->validateTemplate($template);
        $payload = $this->payload($settings, $settings['template'], true);
        return view("site.templates.".$settings['template'], $payload);
    }

    // ------- FORM SAVE (modal Save Settings) -------
    public function update(Request $request)
    {
        $user     = $request->user();
        $userId   = $user?->id;
        $setKey   = $this->userKey($userId);
        $cacheKey = $this->userCacheKey($userId);

        $data = $request->validate([
            'title'    => ['nullable','string','max:160'],
            'tagline'  => ['nullable','string','max:500'],
            'template' => ['required','in:zen,frontrow,focuspod'],
            'layout'   => ['nullable','in:list,grid'],
            'brand'    => ['nullable','string','max:20'],
            'font'     => ['nullable','string','max:255'],
            'episodes_per_page' => ['nullable','integer','min:6','max:48'],
            'show_subscribe_badges' => ['nullable'], // checkbox
            'banner'   => ['nullable','image','max:4096'],
            'clear_banner' => ['nullable','in:0,1'],
            'site_slug'=> ['nullable','string','max:60'],
        ]);

      $settings['public_url'] = $this->canonicalUserUrl($userId, $settings['template'], $settings['site_slug']); // ✅ store full link

        SiteSetting::setValue($setKey, $settings, $userId);
        Cache::forget($cacheKey);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'         => true,
                'template'   => $settings['template'],
                'site_slug'  => $settings['site_slug'],
                'public_url' => $settings['public_url'],   // ✅ return canonical link
            ]);
        }
        // Merge remaining fields (preserve existing if null)
        $settings = $this->settingsWithDefaults(array_replace($settings, [
            'title'    => $data['title']    ?? ($settings['title']    ?? null),
            'tagline'  => $data['tagline']  ?? ($settings['tagline']  ?? null),
            'template' => $data['template'] ?? ($settings['template'] ?? 'zen'),
            'layout'   => $data['layout']   ?? ($settings['layout']   ?? 'list'),
            'brand'    => $data['brand']    ?? ($settings['brand']    ?? '#7c3aed'),
            'font'     => $data['font']     ?? ($settings['font']     ?? "system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif"),
            'episodes_per_page' => $data['episodes_per_page'] ?? ($settings['episodes_per_page'] ?? 12),
            'show_subscribe_badges' => array_key_exists('show_subscribe_badges',$data)
                ? (bool)$data['show_subscribe_badges'] : ($settings['show_subscribe_badges'] ?? true),
        ]));

        SiteSetting::setValue($setKey, $settings);
        Cache::forget($cacheKey);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'         => true,
                'template'   => $settings['template'],
                'site_slug'  => $settings['site_slug'],
                'public_url' => $this->canonicalUserUrl($userId, $settings['template'], $settings['site_slug']),
            ]);
        }

        return redirect('/distribution/website')->with('ok', 'Website settings saved.');
    }

    // ------- QUICK SAVE used by "Use Template" button -------
    public function quickSelect(Request $request)
    {
        $user   = $request->user();
        if (!$user) {
            return response()->json(['ok'=>false,'error'=>'unauthenticated'], 401);
        }
        $userId = $user->id;

        $setKey   = $this->userKey($userId);
        $cacheKey = $this->userCacheKey($userId);

        $data = $request->validate([
            'template'  => ['required','in:zen,frontrow,focuspod'],
            'site_slug' => ['nullable','string','max:60'],
        ]);

        $settings = SiteSetting::getValue($setKey, []) ?? [];
        $settings['template']  = $data['template'];
        $settings['site_slug'] = $data['site_slug'] ?: ($settings['site_slug'] ?? $this->computeSiteSlug($user));
        $settings['public_url'] = $this->canonicalUserUrl($userId, $settings['template'], $settings['site_slug']); // ✅

        SiteSetting::setValue($setKey, $settings, $userId);
        Cache::forget($cacheKey);

        return response()->json([
            'ok'         => true,
            'template'   => $settings['template'],
            'site_slug'  => $settings['site_slug'],
            'public_url' => $settings['public_url'],       // ✅
        ]);
    }

    // ------- helpers -------
       private function settingsWithDefaults(array $s): array
    {
        $defaults = [
            'title'    => config('app.name','PowerTime'),
            'tagline'  => null,
            'template' => 'zen',
            'layout'   => 'list',
            'brand'    => '#7c3aed',
            'font'     => "system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif",
            'episodes_per_page' => 12,
            'show_subscribe_badges' => true,
            'banner'   => null,
            'site_slug'=> null,
            'public_url' => null,              // ✅ new
        ];
        return $s + $defaults;
    }
    private function validateTemplate(string $tpl): string
    {
        return in_array($tpl, ['zen','frontrow','focuspod'], true) ? $tpl : 'zen';
    }

    private function payload(array $settings, string $tpl, bool $isPreview): array
    {
        return compact('settings','tpl','isPreview');
    }

    private function computeSiteSlug($user): string
    {
        $uid = $user?->id ?? 'guest';
        $name = $user?->name ?: ('user-'.$uid);
        return Str::of($name)->slug('-').'-'.substr(md5($uid), 0, 6);
    }

   private function canonicalUserUrl($userId, string $template, string $slug): string
    {
        return url('/site/u/'.$userId.'/'.$template.'/'.$slug);
    }

 
}
