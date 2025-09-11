<?php

// app/Http/Controllers/FeedController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Episode;

class FeedController extends Controller
{
    public function podcast(Request $request)
    {
        $site = [
            'title'         => config('app.name', 'PowerTime'),
            'link'          => rtrim(config('app.url'), '/'),
            'lang'          => 'en-us',
            'desc'          => 'PowerTime — AI, low-code, and digital transformation.',
            'itunes_author' => 'Scott Meddings',
            'itunes_summary'=> 'Conversations for Microsoft technical leaders.',
            'itunes_image'  => url('/images/podcast-cover.jpg'),
            'owner_name'    => 'PowerTime',
            'owner_email'   => 'hello@powertime.au',
            'explicit'      => false,
            'category'      => 'Technology',
            'type'          => 'episodic', // or 'serial'
        ];

        $episodes = Episode::query()
            ->whereNotNull('audio_path')
            ->where(function($q){
                $q->whereNotNull('published_at')->orWhereNotNull('created_at');
            })
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(150) // reasonable cap
            ->get();

        return response()
            ->view('feed.podcast', compact('site', 'episodes'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
