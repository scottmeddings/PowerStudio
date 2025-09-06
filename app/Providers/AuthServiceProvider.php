<?php

namespace App\Providers;

use App\Models\Episode;
use App\Policies\EpisodePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Episode::class => EpisodePolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
