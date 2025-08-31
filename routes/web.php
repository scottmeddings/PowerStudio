<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialController;

Route::prefix('auth')->name('social.')->group(function () {
    Route::get('{provider}', [SocialController::class, 'redirect'])
        ->whereIn('provider', ['google','microsoft'])
        ->name('redirect');

    Route::get('{provider}/callback', [SocialController::class, 'callback'])
        ->whereIn('provider', ['google','microsoft'])
        ->name('callback');
});

