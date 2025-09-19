<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fallback for "files" binding (Illuminate\Filesystem\Filesystem)
        if (! $this->app->bound('files')) {
            $this->app->singleton('files', function () {
                return new \Illuminate\Filesystem\Filesystem;
            });
        }
    }

    public function boot(): void {}
}
