<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider; // <-- the ONLY import here

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void {}
}

