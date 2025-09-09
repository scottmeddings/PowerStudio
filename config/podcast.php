<?php

return [
    'title'       => env('PODCAST_TITLE', 'My Podcast'),
    'subtitle'    => env('PODCAST_SUBTITLE', ''),
    'description' => env('PODCAST_DESCRIPTION', 'About this podcast…'),
    'language'    => env('PODCAST_LANGUAGE', 'en-us'),
    'author'      => env('PODCAST_AUTHOR', 'Your Name'),
    'owner_name'  => env('PODCAST_OWNER_NAME', 'Your Name or Company'),
    'owner_email' => env('PODCAST_OWNER_EMAIL', 'you@example.com'),
    'category'    => env('PODCAST_CATEGORY', 'Technology'),
    'explicit'    => env('PODCAST_EXPLICIT', 'no'), // 'yes' | 'no' | 'clean'
    // Channel cover (recommended 3000x3000 jpg/png)
    'cover_url'   => env('PODCAST_COVER_URL', 'https://example.com/podcast-cover.jpg'),
    // Your public site for <link> and <atom:link self>
    'site_url'    => env('PODCAST_SITE_URL', env('APP_URL')),
];
