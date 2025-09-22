<?php
// app/Http/Controllers/WebsiteController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\SiteSetting;

class WebsiteController extends Controller
{
    private const SETTINGS_KEY_BASE = 'website';
    private const CACHE_KEY_BASE    = 'website:settings';

    // ------- UTIL: user-scoped keys -------
    private function userKey(?int $userId): string
    {
        return sprintf('%s:u:%s', self::SETTINGS_KEY_BASE, $userId ?? 'guest');
    }

    private function userCacheKey(?int $userId): string
    {
        return sprintf('%s:u:%s', self::CACHE_KEY_BASE, $userId ?? 'guest');
    }

    // ------- SETTINGS PAGE (AUTH USER) -------
    public function edit(Request $request)
    {
        $userId   = $request->user()?->id;
        $setKey   = $this->userKey($userId);

        $settings = $this->settingsWithDefaults(
            SiteSetting::getValue($setKey, []) ?? []
        );

        // Ensure a stable, unique site_slug for links
        if (empty($settings['site_slug'])) {
            $settings['site_slug'] = $this->computeSiteSlug($request->user());
        }

        // Themes
        $templates = [
            ['slug'=>'zen','name'=>'Zen 1.0','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Large banner image, simple episode list, fast loads.'],
            ['slug'=>'frontrow','name'=>'Frontrow','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Profile card left, episodes right.'],
            ['slug'=>'focuspod','name'=>'Focuspod','by'=>'PodPower','img'=>asset('images/powertime-hero.png'),'desc'=>'Hero + grid of episodes and tags.'],
        ];

        // Pre-compute a safe banner URL for previews (Blade will consume if provided)
        $bannerUrl = $this->bannerUrl($settings['banner'] ?? null);

        return view('pages.distribution.website', compact('settings','templates','bannerUrl'));
    }

    // Optional alias so you can name the route 'website.themes.update'
    public function updateThemes(Request $request)
    {
        return $this->update($request);
    }

    public function update(Request $request)
    {
        $user      = $request->user();
        $userId    = $user?->id;
        $setKey    = $this->userKey($userId);
        $cacheKey  = $this->userCacheKey($userId);

        $data = $request->validate([
            'title'    => ['nullable','string','max:160'],
            'tagline'  => ['nullable','string','max:500'],
            'template' => ['required','in:zen,frontrow,focuspod'],
            'layout'   => ['nullable','in:list,grid'],
            'banner'   => ['nullable','image','max:4096'], // png/jpg/webp
            // Allow user to customize slug later if you expose it in the form
            'site_slug'=> ['nullable','string','max:60'],
            'clear_banner' => ['nullable','in:0,1'],
        ]);

        $settings = SiteSetting::getValue($setKey, []) ?? [];

        // banner: clear?
        if (($data['clear_banner'] ?? '0') === '1') {
            if (!empty($settings['banner']) && !Str::startsWith($settings['banner'], ['http://','https://'])) {
                Storage::disk('public')->delete($settings['banner']);
            }
            $settings['banner'] = null;
        }

        // banner: upload?
        if ($request->hasFile('banner')) {
            if (!empty($settings['banner']) && !Str::startsWith($settings['banner'], ['http://','https://'])) {
                Storage::disk('public')->delete($settings['banner']);
            }
            $path = $request->file('banner')->store('images', 'public'); // storage/app/public/images/…
            $data['banner'] = $path; // relative path; Blade uses Storage::url()
        } else {
            // keep existing banner if not replaced/cleared
            if (!array_key_exists('banner', $data) && isset($settings['banner'])) {
                $data['banner'] = $settings['banner'];
            }
        }

        // site_slug: ensure stable & unique-ish if not provided
        $data['site_slug'] = $this->normalizeSlug(
            $data['site_slug'] ?? ($settings['site_slug'] ?? $this->computeSiteSlug($user))
        );

        // merge & persist (user-scoped key)
        $settings = $this->settingsWithDefaults(array_replace($settings, $data));
        SiteSetting::setValue($setKey, $settings);

        // invalidate only this user's cached public settings
        Cache::forget($cacheKey);

        return redirect('/distribution/website')->with('ok', 'Website settings saved.');
    }

    public function clearBanner(Request $request)
    {
        $userId   = $request->user()?->id;
        $setKey   = $this->userKey($userId);
        $cacheKey = $this->userCacheKey($userId);

        $settings = SiteSetting::getValue($setKey, []) ?? [];
        if (!empty($settings['banner']) && !Str::startsWith($settings['banner'], ['http://','https://'])) {
            Storage::disk('public')->delete($settings['banner']);
        }
        unset($settings['banner']);

        SiteSetting::setValue($setKey, $settings);
        Cache::forget($cacheKey);

        return redirect('/distribution/website')->with('ok', 'Banner removed.');
    }

    // ------- PUBLIC (default saved template for auth user — keeps existing route) -------
    public function show(Request $request)
    {
        $userId   = $request->user()?->id;
        $cacheKey = $this->userCacheKey($userId);
        $setKey   = $this->userKey($userId);

        $settings = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($setKey) {
            return $this->settingsWithDefaults(SiteSetting::getValue($setKey, []) ?? []);
        });

        $tpl = $this->validateTemplate($settings['template']);
        $payload = $this->payload($settings, $tpl, false);

        return view("site.templates.$tpl", $payload);
    }

    // ------- PUBLIC PREVIEW (auth user) -------
    public function preview(Request $request, string $template)
    {
        $userId   = $request->user()?->id;
        $setKey   = $this->userKey($userId);

        $tpl = $this->validateTemplate($template);
        $settings = $this->settingsWithDefaults(SiteSetting::getValue($setKey, []) ?? []);
        $settings['template'] = $tpl;

        $payload = $this->payload($settings, $tpl, true);
        return view("site.templates.$tpl", $payload);
    }

    // ------- PUBLIC USER-SCOPED SITE: /site/u/{user}/{template}/{slug} -------
    public function userSite(int $userId, string $template, string $slug)
    {
        $setKey   = $this->userKey($userId);
        $cacheKey = $this->userCacheKey($userId);

        // cache per-owner
        $settings = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($setKey) {
            return $this->settingsWithDefaults(SiteSetting::getValue($setKey, []) ?? []);
        });

        // Optional: enforce slug match (if you want)
        if (!empty($settings['site_slug']) && $this->normalizeSlug($settings['site_slug']) !== $this->normalizeSlug($slug)) {
            // If slug doesn't match, you can 404 or redirect to canonical
            // return redirect()->to($this->canonicalUserUrl($userId, $settings['template'], $settings['site_slug']))->setStatusCode(301);
        }

        $tpl = $this->validateTemplate($template);
        $payload = $this->payload($settings, $tpl, false);

        return view("site.templates.$tpl", $payload);
    }

    // ---------- helpers ----------
    private function settingsWithDefaults(array $s): array
    {
        $defaults = [
            'title'     => config('app.name','PowerTime'),
            'tagline'   => null,
            'template'  => 'zen',
            'layout'    => 'list',
            'banner'    => null, // relative path (public disk) OR full URL
            'site_slug' => null, // unique per user
        ];
        return $s + $defaults;
    }

    private function validateTemplate(string $tpl): string
    {
        return in_array($tpl, ['zen','frontrow','focuspod'], true) ? $tpl : 'zen';
    }

    private function payload(array $settings, string $tpl, bool $isPreview): array
    {
        // Provide a resolved banner URL for convenience in the view
        $bannerUrl = $this->bannerUrl($settings['banner'] ?? null);
        return compact('settings','tpl','isPreview','bannerUrl');
    }

    private function bannerUrl(?string $value): ?string
    {
        if (!$value) return null;
        if (Str::startsWith($value, ['http://','https://'])) return $value;
        return Storage::disk('public')->url(ltrim($value, '/'));
    }
  

    public function quickSelect(Request $request)
    {
        $user     = $request->user();
        $userId   = $user?->id;
        $setKey   = $this->userKey($userId);
        $cacheKey = $this->userCacheKey($userId);

        $data = $request->validate([
            'template'  => ['required','in:zen,frontrow,focuspod'],
            'site_slug' => ['nullable','string','max:60'],
        ]);

        $settings = SiteSetting::getValue($setKey, []) ?? [];

        // persist selected template + slug (slug optional—keeps URL stable)
        $settings['template']  = $data['template'];
        if (!empty($data['site_slug'])) {
            $settings['site_slug'] = $this->normalizeSlug($data['site_slug']);
        } elseif (empty($settings['site_slug'])) {
            $settings['site_slug'] = $this->computeSiteSlug($user);
        }

        SiteSetting::setValue($setKey, $settings);
        Cache::forget($cacheKey);

        // respond with canonical URL for the page to show/update
        $canonicalUrl = $this->canonicalUserUrl(
            $userId,
            $settings['template'],
            $settings['site_slug']
        );

        // return JSON for AJAX
        return response()->json([
            'ok'          => true,
            'template'    => $settings['template'],
            'site_slug'   => $settings['site_slug'],
            'public_url'  => $canonicalUrl,
        ]);
    }


    private function computeSiteSlug($user): string
    {
        $uid   = $user?->id ?? 0;
        $name  = $user?->name ?: 'user-'.$uid;
        // Human friendly + short hash suffix for uniqueness
        return $this->normalizeSlug(Str::slug($name).'-'.substr(md5((string)$uid), 0, 6));
    }

    private function normalizeSlug(?string $slug): string
    {
        $slug = (string) $slug;
        $slug = Str::of($slug)->slug('-');
        // Avoid empty slugs
        return $slug != '' ? (string)$slug : 'site-'.substr(md5(uniqid('', true)), 0, 6);
    }

    private function canonicalUserUrl(int $userId, string $template, string $slug): string
    {
        return url('/site/u/'.$userId.'/'.$this->validateTemplate($template).'/'.$this->normalizeSlug($slug));
    }
}
