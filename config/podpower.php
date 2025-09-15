<?php

return [
    'rss_progress_store' => env('RSS_PROGRESS_STORE', 'file'),
    // who owns imported episodes if no auth user is present
    'system_user_id'     => env('SYSTEM_USER_ID', 1),
];
