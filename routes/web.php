<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\PodcastAppController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EpisodeController;   // ← singular, used for ALL episode routes incl. show
use App\Http\Controllers\PageController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EpisodeChapterController;
use App\Http\Controllers\EpisodeTranscriptController;
use App\Http\Controllers\DistributionController;


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
| Guest routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');

    Route::get('/register',  [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

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

    // Email verification UX (optional; keep if you use it)
    Route::get('/verify-email', fn () => view('auth.verify-email'))->name('verification.notice');

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

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Episodes (CRUD + show)
    Route::get('/episodes',                 [PageController::class, 'episodes'])->name('episodes');         // list
    Route::get('/episodes/create',          [EpisodeController::class, 'create'])->name('episodes.create');
    Route::post('/episodes',                [EpisodeController::class, 'store'])->name('episodes.store');
    Route::get('/episodes/{episode}',       [EpisodeController::class, 'show'])->name('episodes.show');     // ← show (singular controller)
    Route::patch('/episodes/{episode}/publish',   [EpisodeController::class, 'publish'])->name('episodes.publish');
    Route::patch('/episodes/{episode}/unpublish', [EpisodeController::class, 'unpublish'])->name('episodes.unpublish');

    Route::get('/episodes/{episode}/edit', [EpisodeController::class, 'edit'])
    ->middleware('can:update,episode')->name('episodes.edit');

    Route::put('/episodes/{episode}', [EpisodeController::class, 'update'])
        ->middleware('can:update,episode')->name('episodes.update');

    Route::delete('/episodes/{episode}', [EpisodeController::class, 'destroy'])
    ->middleware('can:delete,episode')->name('episodes.destroy');

    // Podcast apps (distribution) - upsert/destroy
    Route::middleware('auth')->group(function () {
        Route::post('/distribution/apps/{provider}', [PodcastAppController::class,'upsert'])
            ->name('apps.upsert');        // Manage/Submit modal posts here

        Route::delete('/distribution/apps/{provider}', [PodcastAppController::class,'destroy'])
            ->name('apps.destroy');
    });
    
    Route::middleware('auth')->group(function () {
        Route::get('/distribution', [PageController::class, 'distribution'])->name('distribution');

        // Save / update a directory config
        Route::post('/distribution/{slug}', [DistributionController::class, 'save'])
            ->name('distribution.save');

        // Disconnect / clear a directory config
        Route::delete('/distribution/{slug}', [DistributionController::class, 'disconnect'])
            ->name('distribution.disconnect');
    });
    

    // Comments
    Route::post('/episodes/{episode}/comments', [CommentController::class, 'store'])
    ->middleware(['auth','throttle:20,1'])
    ->name('comments.store');

    Route::delete('/comments/{comment}',        [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/approve',  [CommentController::class, 'approve'])->name('comments.approve');

    // Other left-menu pages
    Route::get('/distribution',  [PageController::class, 'distribution'])->name('distribution');
    Route::get('/statistics',    [PageController::class, 'statistics'])->name('statistics');
    Route::get('/monetization',  [PageController::class, 'monetization'])->name('monetization');
    Route::get('/settings',      [PageController::class, 'settings'])->name('settings');

    // routes settings
    Route::middleware('auth')->group(function () {
    Route::post('/settings/cover',        [ProfileController::class, 'uploadCover'])->name('settings.cover.upload');
    Route::delete('/settings/cover',      [ProfileController::class, 'deleteCover'])->name('settings.cover.delete');
});

// EPOSIDES - cover upload/remove, publish/unpublish
    Route::patch('/episodes/{episode}/cover', [EpisodeController::class, 'uploadCover'])
        ->name('episodes.cover.upload');
    Route::delete('/episodes/{episode}/cover', [EpisodeController::class, 'removeCover'])
        ->name('episodes.cover.remove');
    Route::put('/episodes/{episode}', [EpisodeController::class, 'update'])->name('episodes.update');
    Route::patch('/episodes/{episode}/publish', [EpisodeController::class, 'publish'])->name('episodes.publish');
    Route::patch('/episodes/{episode}/unpublish', [EpisodeController::class, 'unpublish'])->name('episodes.unpublish');
    Route::match(['put', 'patch'], '/episodes/{episode}', [EpisodeController::class, 'update'])
    ->name('episodes.update');
    Route::prefix('episodes/{episode}')->group(function () {
    // Chapters
    Route::get ('/chapters',       [EpisodeChapterController::class, 'index'])->name('episodes.chapters.index');
    Route::post('/chapters/sync',  [EpisodeChapterController::class, 'sync'])->name('episodes.chapters.sync');
    Route::delete('/chapters/{chapter}', [EpisodeChapterController::class, 'destroy'])->name('episodes.chapters.destroy');

    // Transcript
    Route::get ('/transcript',     [EpisodeTranscriptController::class, 'show'])->name('episodes.transcript.show');
    Route::post('/transcript',     [EpisodeTranscriptController::class, 'store'])->name('episodes.transcript.store');
    Route::delete('/transcript',   [EpisodeTranscriptController::class, 'destroy'])->name('episodes.transcript.destroy');
    Route::get ('/transcript/download', [EpisodeTranscriptController::class, 'download'])->name('episodes.transcript.download');
});



    // Test screen
    Route::get('/test/totals', [TestController::class, 'totals'])->name('test.totals');

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
