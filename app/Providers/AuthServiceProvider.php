<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // \App\Models\Post::class => \App\Policies\PostPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        // Do NOT reference Gate here.
    }
}

