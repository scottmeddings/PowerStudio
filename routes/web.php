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
use App\Http\Controllers\Auth\LocalAuthController;
use App\Http\Controllers\EpisodeAiController;
use App\Http\Controllers\PodcastFeedController;
use App\Http\Controllers\FeedController;






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
Route::get('/feed/podcast.xml', [PodcastFeedController::class, 'index'])
    ->name('feed.podcast')
    ->withoutMiddleware('auth'); // safety guard in case this line ever gets moved
Route::get('/feed.xml', [FeedController::class, 'podcast'])
    ->name('feed.podcast');
    /*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::get('/login',  [LocalAuthController::class, 'create'])->name('login'); // show form
    Route::post('/login', [LocalAuthController::class, 'store'])->name('login.attempt'); // handle form

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

    // Podcast RSS feed (public)
    

    // Podcasting 2.0 helpers (optional but recommended)
    Route::get('/episodes/{episode}/chapters.json', [EpisodeChaptersJsonController::class, 'show'])
        ->name('episodes.chapters.json');
    /*
    You already have transcript download in your EpisodeTranscriptController.
    Ensure you have a public download route that returns the stored transcript file:
    Route::get('/episodes/{episode}/transcript', [EpisodeTranscriptController::class, 'download'])
        ->name('episodes.transcript.download');
    */

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
    
    Route::post('/episodes/{episode}/ai/enhance', [EpisodeAiController::class, 'enhance'])
    ->middleware(['auth'])
    ->name('episodes.ai.enhance');

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
 
    Route::post('/settings/cover',        [ProfileController::class, 'uploadCover'])->name('settings.cover.upload');
    Route::delete('/settings/cover',      [ProfileController::class, 'deleteCover'])->name('settings.cover.delete');
    Route::patch('/settings/account',      [PageController::class, 'updateAccount'])->name('settings.account');
    Route::post('/settings/profile-photo', [PageController::class, 'uploadProfilePhoto'])->name('settings.profile-photo');
    Route::delete('/settings/profile-photo',[PageController::class, 'removeProfilePhoto'])->name('settings.profile-photo.remove');
    

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
 
    Route::middleware('auth')->group(function () {
        Route::post('/episodes/{episode}/ai/enhance',  [EpisodeAIController::class, 'enhance'])->name('episodes.ai.enhance');
        Route::post('/episodes/{episode}/ai/cancel',  [EpisodeAIController::class, 'cancel'])->name('episodes.ai.cancel');
        Route::get ('/episodes/{episode}/ai/progress', [EpisodeAIController::class, 'progress'])->name('episodes.ai.progress');
    });     

   // ai test screen
    Route::get('/episodes/{episode}/ai/debug', function (\App\Models\Episode $episode) {
        $id = $episode->id;
        return response()->json([
            'env_queue' => config('queue.default'),
            'db_status' => $episode->only(['ai_status','ai_progress','ai_message']),
            'cache'     => Cache::get("ai:$id:progress"),
            'pid'       => Cache::get("ai:$id:pid"),
            'queueSize' => method_exists(\Queue::class, 'size') ? \Queue::size('default') : null,
        ]);
    })->middleware('auth');

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
