<?php

// app/Http/Controllers/PublicSiteController.php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Episode;

class PublicSiteController extends Controller
{
    public function index()
    {
        $row = DB::table('site_settings')->where('key','website')->first();
        $s   = $row ? (array) json_decode($row->value, true) : [];

        $settings = array_merge([
            'template' => 'zen',
            'title'    => config('app.name', 'PowerTime'),
            'brand'    => '#7c3aed',
            'font'     => "system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif",
            'layout'   => 'list',
            'episodes_per_page' => 12,
            'show_subscribe_badges' => true,
            'banner'   => null,
        ], $s);

        $episodes = Episode::query()
            ->where('status', 'published')
            ->whereNotNull('audio_path')
            ->latest('published_at')
            ->paginate($settings['episodes_per_page'])
            ->withQueryString();

        $view = 'site.templates.'.$settings['template'];

        return view($view, compact('settings','episodes'));
    }

    public function show(string $slug)
    {
        $episode = Episode::where('slug', $slug)->firstOrFail();

        $row = DB::table('site_settings')->where('key','website')->first();
        $s   = $row ? (array) json_decode($row->value, true) : [];
        $settings = array_merge(['template' => 'zen', 'brand' => '#7c3aed', 'title'=>config('app.name')], $s);

        return view('site.episode', compact('settings','episode'));
    }
}

