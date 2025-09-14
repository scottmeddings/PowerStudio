<?php

// app/Http/Controllers/WebsiteController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteController extends Controller
{
    public function edit(Request $request)
    {
        // Load saved site settings (simple key/value row)
        $row = DB::table('site_settings')->where('key', 'website')->first();
        $settings = $row ? (array) json_decode($row->value, true) : [];

        // Available templates (hosted by your app)
        $templates = [
            [
                'slug' => 'zen',
                'name' => 'Zen 1.0',
                'by'   => 'PodPower',
                'img'  => asset('images/themes/zen.jpg'),
                'desc' => 'Large banner image, simple episode list, fast loads.',
            ],
            [
                'slug' => 'frontrow',
                'name' => 'Frontrow',
                'by'   => 'PodPower',
                'img'  => asset('images/themes/frontrow.jpg'),
                'desc' => 'Profile card left, episodes right, great for bios.',
            ],
            [
                'slug' => 'focuspod',
                'name' => 'Focuspod',
                'by'   => 'PodPower',
                'img'  => asset('images/themes/focuspod.jpg'),
                'desc' => 'Hero image with grid of episodes and tags.',
            ],
        ];

        return view('distribution.website.themes', [
            'templates' => $templates,
            'settings'  => $settings + [
                'template' => $settings['template'] ?? 'zen',
                'title'    => $settings['title']    ?? config('app.name', 'PowerTime'),
                'brand'    => $settings['brand']    ?? '#7c3aed',
                'font'     => $settings['font']     ?? "system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif",
                'layout'   => $settings['layout']   ?? 'list', // list|grid
                'banner'   => $settings['banner']   ?? null,
                'episodes_per_page' => $settings['episodes_per_page'] ?? 12,
                'show_subscribe_badges' => $settings['show_subscribe_badges'] ?? true,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'template' => 'required|in:zen,frontrow,focuspod',
            'title'    => 'required|string|max:120',
            'brand'    => 'required|string|max:9', // e.g. #7c3aed
            'font'     => 'required|string|max:300',
            'layout'   => 'required|in:list,grid',
            'episodes_per_page' => 'required|integer|min:6|max:48',
            'show_subscribe_badges' => 'nullable|boolean',
            'banner'   => 'nullable|image|max:4096', // 4MB
        ]);

        // Handle banner upload (optional)
        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('banners', 'public');
            $data['banner'] = $path;
        } else {
            // keep existing banner if not re-uploaded
            $existing = DB::table('site_settings')->where('key', 'website')->first();
            if ($existing) {
                $prev = (array) json_decode($existing->value, true);
                if (!isset($data['banner']) && isset($prev['banner'])) {
                    $data['banner'] = $prev['banner'];
                }
            }
        }

        $data['show_subscribe_badges'] = (bool) ($data['show_subscribe_badges'] ?? false);

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'website'],
            ['value' => json_encode($data), 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->route('website.update')->with('ok', 'Website settings saved.');
    }
}


