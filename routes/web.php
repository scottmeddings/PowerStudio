<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;   // <-- missing before

/*
|--------------------------------------------------------------------------
| Home: smart redirect
|--------------------------------------------------------------------------
| Guests -> /login, Authed -> /dashboard
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

/*
|--------------------------------------------------------------------------
| Guest routes (no session user)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Login screen
    Route::view('/login', 'auth.login')->name('login');

    // Registration (optional)
    Route::get('/register',  [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    // Social auth
    Route::get('/auth/{provider}', [SocialController::class, 'redirect'])
        ->whereIn('provider', ['google','microsoft','facebook'])
        ->name('social.redirect');

    Route::get('/auth/{provider}/callback', [SocialController::class, 'callback'])
        ->whereIn('provider', ['google','microsoft','facebook'])
        ->name('social.callback');
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Email verification UX (if you use it)
    Route::get('/verify-email', fn () => view('auth.verify-email'))
        ->name('verification.notice');

    Route::get('/verify-email/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');

    // Password & profile
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');

    // Core app pages (require verified account if you want — toggle middleware as needed)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Left-menu pages
    Route::get('/episodes',      [PageController::class, 'episodes'])->name('episodes');
    Route::get('/distribution',  [PageController::class, 'distribution'])->name('distribution');
    Route::get('/statistics',    [PageController::class, 'statistics'])->name('statistics');
    Route::get('/monetization',  [PageController::class, 'monetization'])->name('monetization');
    Route::get('/settings',      [PageController::class, 'settings'])->name('settings');
});


    // Logout (POST)
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    })->name('logout');
});

/*
|--------------------------------------------------------------------------
| Fallback
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
