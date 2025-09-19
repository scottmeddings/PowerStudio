<?php

// app/Providers/AuthServiceProvider.php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = []; // your model policies if any

    public function boot(): void
    {
        // Register abilities
        Gate::define('manage-collaborators', fn($user) => $user?->role === 'admin');

        // Optional: let admins do everything else automatically
        Gate::before(function ($user) {
            return $user->role === 'admin' ? true : null;
        });
    }
}
