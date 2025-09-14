<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SiteSetting;
use App\Models\Episode;

class SiteController extends Controller
{
    /** Public site using the SAVED template */
    public function show()
    {
        $settings = $this->settingsWithDefaults(SiteSetting::getValue('website', []) ?? []);
        $tpl = $this->validateTemplate($settings['template']);

        $payload = $this->payload($settings, $tpl, false);
        return view("site.templates.$tpl", $payload);
    }

    /** Preview any template without saving */
    public function preview(string $template)
    {
        $tpl = $this->validateTemplate($template);
        $settings = $this->settingsWithDefaults(SiteSetting::getValue('website', []) ?? []);
        $settings['template'] = $tpl;

        $payload = $this->payload($settings, $tpl, true);
        return view("site.templates.$tpl", $payload);
    }

    // -------- helpers --------

    private function settingsWithDefaults(array $s): array
    {
        return $s + [
            'template' => 'zen',
            'title'    => config('app.name', 'Your Podcast'),
            'brand'    => '#7c3aed',
            'font'     => "system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif",
            'layout'   => 'list',
            'episodes_per_page' => 12,
            'show_subscribe_badges' => true,
            'banner'   => null,
        ];
    }

    private function validateTemplate(string $slug): string
    {
        return in_array($slug, ['zen','frontrow','focuspod'], true) ? $slug : 'zen';
    }

    /**
     * Build payload. IMPORTANT: returns a Paginator so templates can call ->links()
     */
    private function payload(array $settings, string $tpl, bool $isPreview): array
    {
        $perPage = (int)($settings['episodes_per_page'] ?? 12);

        if (class_exists(Episode::class)) {
            $episodes = Episode::query()
                ->latest('published_at')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            // Query builder paginate also returns LengthAwarePaginator
            $episodes = DB::table('episodes')
                ->orderByDesc('published_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        // Make pagination links point to the right route
        $episodes->withPath(
            $isPreview
                ? route('site.preview', ['template' => $tpl])
                : route('site.show')
        );

        return [
            'settings' => $settings,
            'episodes' => $episodes, // paginator (->links() works)
        ];
    }
}
