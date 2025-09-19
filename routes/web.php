<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\PlayerEmbedController;
use App\Http\Controllers\PodcastAppController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EpisodeController;               // singular controller
use App\Http\Controllers\PageController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EpisodeChapterController;
use App\Http\Controllers\EpisodeTranscriptController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\Auth\LocalAuthController;
use App\Http\Controllers\EpisodeAiController;             // <-- make sure class name matches the file
use App\Http\Controllers\PodcastFeedController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SettingsController;  
use App\Http\Controllers\WebsiteController;     
use App\Http\Controllers\PublicSiteController;  
use App\Http\Controllers\StatisticsController; 
use App\Http\Controllers\SiteController;   
use App\Http\Controllers\FeedAliasController; 
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Settings\RssImportController;
use App\Jobs\QueueHealthcheckJob;
use App\Http\Controllers\MonetizationController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\SponsorshipsController;
use App\Http\Controllers\HouseAdsController;
use App\Http\Controllers\AdMarketplaceController;
use App\Http\Controllers\DynamicAdsController;
use Laragear\WebAuthn\Http\Controllers\LoginController as PasskeyLogin;
use Laragear\WebAuthn\Http\Controllers\RegisterKeyController as PasskeyRegister;

require __DIR__.'/auth.php';



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
| Public RSS (no auth)
|--------------------------------------------------------------------------
*/

Route::fallback(FeedAliasController::class);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::get('/login',  [LocalAuthController::class, 'create'])->name('login');           // show form
    Route::post('/login', [LocalAuthController::class, 'store'])->name('login.attempt');   // handle form

    Route::get('/register',  [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/auth/{provider}', [SocialController::class, 'redirect'])
        ->whereIn('provider', ['google','microsoft','facebook'])
        ->name('social.redirect');

    Route::get('/auth/{provider}/callback', [SocialController::class, 'callback'])
        ->whereIn('provider', ['google','microsoft','facebook'])
        ->name('social.callback');
});
    Route::middleware('guest')->group(function () {
        Route::post('/passkeys/options', [PasskeyLogin::class, 'options'])->name('passkeys.options');
        Route::post('/passkeys/verify',  [PasskeyLogin::class, 'verify'])->name('passkeys.verify');
});

 Route::get('/podcast', function () {
    $row = DB::table('site_settings')->where('key','website')->first();
    $s = $row ? (array) json_decode($row->value, true) : ['template'=>'zen'];
    // dispatch to a blade per template
    return view('site.templates.'.$s['template'], ['settings'=>$s]);
});


Route::get('/invite/accept/{token}', [\App\Http\Controllers\CollaboratorsController::class, 'accept'])
    ->name('collab.accept');

// e.g. /podpower/feed.xml
Route::get('/{slug}/feed.xml', [PodcastFeedController::class, 'index'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('feed.bySlug');


Route::get('/podcast/{slug}', [PublicSiteController::class, 'show'])->name('site.episode');

// Public site using the SAVED template
Route::get('/site', [SiteController::class, 'show'])->name('site.show');

// Preview a specific template without saving
Route::get('/site/preview/{template}', [SiteController::class, 'preview'])
    ->whereIn('template', ['zen','frontrow','focuspod'])
    ->name('site.preview');





/*
|--------------------------------------------------------------------------
| admin routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth','can:manage-collaborators'])
    ->group(function () {
        Route::get('/settings/collaborators', [SettingsController::class, 'collaborators'])
            ->name('settings.collaborators');
        Route::post('/settings/collaborators/invite', [SettingsController::class, 'invite']);
        Route::delete('/settings/collaborators/{id}', [SettingsController::class, 'revoke']);
    });

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
        Route::post('/passkeys/register/options', [PasskeyRegister::class, 'options'])->name('passkeys.register.options');
        Route::post('/passkeys/register',          [PasskeyRegister::class, 'create'])->name('passkeys.register');
        Route::delete('/passkeys/{credentialId}',  [PasskeyRegister::class, 'destroy'])->name('passkeys.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings');

    Route::post('/settings/collaborators/invite', [\App\Http\Controllers\CollaboratorsController::class, 'invite'])
        ->name('collab.invite');
    Route::post('/settings/collaborators/{id}/revoke', [\App\Http\Controllers\CollaboratorsController::class, 'revoke'])
        ->name('collab.revoke');

    // Put your Episodes CRUD & other admin pages here so collaborators get full access.
});

Route::middleware('auth')->group(function () {
    // Email verification UX (optional)
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

    // website
    Route::get('/distribution/website', [WebsiteController::class, 'edit'])->name('website.themes.edit');
    Route::post('/distribution/website', [WebsiteController::class, 'update'])->name('website.themes.update');
    Route::post('/distribution/website/clear-banner', [WebsiteController::class, 'clearBanner'])->name('website.banner.clear');
    Route::get('/distribution/website', [WebsiteController::class, 'edit'])->name('website.edit');
    Route::post('/distribution/website', [WebsiteController::class, 'update'])->name('website.update');
    Route::post('/distribution/website/clear-banner', [WebsiteController::class, 'clearBanner'])->name('website.banner.clear');
    Route::get('/website/themes', [WebsiteController::class, 'edit'])->name('website.themes');
    Route::post('/website/themes', [WebsiteController::class, 'update'])->name('website.themes.update');
            

    Route::middleware(['web','auth','adminlike'])->group(function () {
        Route::redirect('/settings', '/settings/general')->name('settings');
        Route::get('/settings/import',        [RssImportController::class, 'show'])->name('settings.import');
        Route::post('/settings/import',       [RssImportController::class, 'handle'])->name('settings.import.handle');
        Route::get('/settings/import/status', [RssImportController::class, 'status'])->name('settings.import.status');
    });


    
        // Podcasting 2.0 (only if you have the controller)
    // Route::get('/episodes/{episode}/chapters.json', [EpisodeChaptersJsonController::class, 'show'])
    //     ->name('episodes.chapters.json');

    /*
    |--------------------------------------------------------------------------
    | Episodes (CRUD + show)
    |--------------------------------------------------------------------------
    */
    Route::get('/episodes',                 [PageController::class, 'episodes'])->name('episodes'); // list page (blade)
    Route::get('/episodes/create',          [EpisodeController::class, 'create'])->name('episodes.create');
    Route::post('/episodes',                [EpisodeController::class, 'store'])->name('episodes.store');
    Route::get('/episodes/{episode}',       [EpisodeController::class, 'show'])->name('episodes.show');

    Route::get('/episodes/{episode}/edit',  [EpisodeController::class, 'edit'])
        ->middleware('can:update,episode')->name('episodes.edit');

    Route::put   ('/episodes/{episode}',    [EpisodeController::class, 'update'])
        ->middleware('can:update,episode')->name('episodes.update');

    Route::delete('/episodes/{episode}',    [EpisodeController::class, 'destroy'])
        ->middleware('can:delete,episode')->name('episodes.destroy');

    Route::get('/episodes/{episode}/download', [EpisodeController::class, 'download'])
    ->name('episodes.download');
    // routes/web.php
    Route::patch('/episodes/{episode}/plays', [\App\Http\Controllers\EpisodeController::class, 'setPlays'])
        ->name('episodes.plays.set');
    Route::put('/episodes/{episode}/plays', [\App\Http\Controllers\EpisodeController::class, 'setPlays'])
    ->name('episodes.plays.set');

    Route::get('/episodes', [EpisodeController::class, 'index'])->name('episodes');
    
    // Publish / Unpublish
    Route::patch('/episodes/{episode}/publish',   [EpisodeController::class, 'publish'])->name('episodes.publish');
    Route::patch('/episodes/{episode}/unpublish', [EpisodeController::class, 'unpublish'])->name('episodes.unpublish');

    // Cover upload/remove
    Route::patch ('/episodes/{episode}/cover', [EpisodeController::class, 'uploadCover'])->name('episodes.cover.upload');
    Route::delete('/episodes/{episode}/cover', [EpisodeController::class, 'removeCover'])->name('episodes.cover.remove');

    // AI actions (consistent naming with EpisodeAiController)
    Route::post('/episodes/{episode}/ai/enhance',  [EpisodeAiController::class, 'enhance'])->name('episodes.ai.enhance');
    Route::post('/episodes/{episode}/ai/cancel',   [EpisodeAiController::class, 'cancel'])->name('episodes.ai.cancel');
    Route::get ('/episodes/{episode}/ai/progress', [EpisodeAiController::class, 'progress'])->name('episodes.ai.progress');

    // Episode sub-resources
    Route::prefix('episodes/{episode}')->group(function () {
        // Chapters
        Route::get   ('/chapters',                [EpisodeChapterController::class, 'index'])->name('episodes.chapters.index');
        Route::post  ('/chapters/sync',           [EpisodeChapterController::class, 'sync'])->name('episodes.chapters.sync');
        Route::delete('/chapters/{chapter}',      [EpisodeChapterController::class, 'destroy'])->name('episodes.chapters.destroy');

        // Transcript
        Route::get   ('/transcript',              [EpisodeTranscriptController::class, 'show'])->name('episodes.transcript.show');
        Route::post  ('/transcript',              [EpisodeTranscriptController::class, 'store'])->name('episodes.transcript.store');
        Route::delete('/transcript',              [EpisodeTranscriptController::class, 'destroy'])->name('episodes.transcript.destroy');
        Route::get   ('/transcript/download',     [EpisodeTranscriptController::class, 'download'])->name('episodes.transcript.download');
    });

      /*
    |--------------------------------------------------------------------------
    | sTSATISTICS
    |--------------------------------------------------------------------------
    */
    
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');
    Route::get('/statistics/range/{range}', [StatisticsController::class, 'index'])->whereNumber('range')->name('statistics.range');


      /*
    |--------------------------------------------------------------------------
    | pLAYER EMBED
    |--------------------------------------------------------------------------
    */
   

    Route::get('/embed/player', [PlayerEmbedController::class, 'iframe'])   // HTML for <iframe>
     ->name('embed.player');
    Route::get('/embed/player.js', [PlayerEmbedController::class, 'script']) // JS loader (no iframe needed in markup)
        ->name('embed.player.script');
    Route::get('/oembed', [PlayerEmbedController::class, 'oembed'])         // optional: oEmbed JSON
     ->name('embed.oembed');

    /*
    |--------------------------------------------------------------------------
    | Distribution, Statistics, Monetization (left menu)
    |--------------------------------------------------------------------------
    */
    
    Route::get('/distribution', [DistributionController::class, 'index'])->name('distribution');
    Route::prefix('distribution')->name('distribution.')->group(function () {
        Route::get('/podcast-apps', [DistributionController::class, 'player'])->name('distribution.player');
        Route::get('/social', [DistributionController::class, 'social'])->name('social');
        Route::get('/apps',    [DistributionController::class, 'apps'])->name('apps');
        Route::get('/social',  [DistributionController::class, 'social'])->name('social');
        Route::get('/website', [DistributionController::class, 'website'])->name('website');
        Route::get('/player',  [DistributionController::class, 'player'])->name('player');
        Route::post('/{slug}',   [DistributionController::class, 'save'])->name('save');
        Route::delete('/{slug}', [DistributionController::class, 'disconnect'])->name('disconnect');
        Route::prefix('social')->name('social.')->group(function () {
        // Start connect
        Route::get('/auth/{provider}', [DistributionController::class, 'socialRedirect'])
                    ->whereIn('provider', ['facebook','linkedin','youtube','tumblr','wordpress'])
                    ->name('auth');

        // OAuth callback
        Route::get('/auth/{provider}/callback', [DistributionController::class, 'socialCallback'])
                    ->whereIn('provider', ['facebook','linkedin','youtube','tumblr','wordpress'])
                    ->name('callback');

        // Disconnect
        Route::delete('/{provider}', [DistributionController::class, 'socialDisconnect'])
                    ->whereIn('provider', ['facebook','linkedin','youtube','tumblr','wordpress'])
                    ->name('disconnect');

        });




});
        
    

        
    
          
        
    
           
    
    
    
    /*
    |--------------------------------------------------------------------------
    | SETTINGS (grouped, with proper name prefix -> settings.*)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth']) // adjust middleware as needed
        ->prefix('settings')
        ->name('settings.')
        ->group(function () {
            // Landing page (/settings)
            Route::get('/', [PageController::class, 'settings'])->name('index');

            // Profile / Cover / Account actions
            Route::post  ('/cover',         [ProfileController::class, 'uploadCover'])->name('cover.upload');
            Route::delete('/cover',         [ProfileController::class, 'deleteCover'])->name('cover.delete');
            Route::patch ('/account',       [PageController::class,    'updateAccount'])->name('account');
            Route::post  ('/profile-photo', [PageController::class,    'uploadProfilePhoto'])->name('profile-photo');
            Route::delete('/profile-photo', [PageController::class,    'removeProfilePhoto'])->name('profile-photo.remove');

            // Pages: General
            Route::get ('/general', [SettingsController::class, 'general'])->name('general');
            Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');
            

            // Pages: Feed
            Route::get ('/feed', [SettingsController::class, 'feed'])->name('feed');
            Route::post('/feed', [SettingsController::class, 'updateFeed'])->name('feed.update');
            // Route::put('/feed', [SettingsController::class, 'updateFeed'])->name('feed.update');

            // Plugins
            Route::get ('/plugins', [SettingsController::class, 'plugins'])->name('plugins');
            Route::post('/plugins', [SettingsController::class, 'updatePlugins'])->name('plugins.update');

            
        });

    /*
    |--------------------------------------------------------------------------
    | Comments
    |--------------------------------------------------------------------------

    */
    Route::post('/episodes/{episode}/comments', [CommentController::class, 'store'])
        ->middleware(['auth','throttle:20,1'])
        ->name('comments.store');

    Route::delete('/comments/{comment}',       [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');



    // Test screen
    Route::get('/test/totals', [TestController::class, 'totals'])->name('test.totals');

    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    })->name('logout');
});



/* <monitisation></monitisation> */


Route::middleware(['auth'])->group(function () {

    Route::get('/monetization', [MonetizationController::class,'index'])
     ->name('monetization');  // base page

    // Dynamic Ad Insertion
    Route::get('/monetization/dynamic', [AdMarketplaceController::class,'show'])->name('monetization.dynamic.show');
    Route::post('/monetization/dynamic', [AdMarketplaceController::class,'save'])->name('monetization.dynamic.save');

    // Sponsorships
    Route::get('/monetization/sponsorships/new', [SponsorshipsController::class,'create'])->name('monetization.sponsorships.new');
    Route::post('/monetization/sponsorships', [SponsorshipsController::class,'store'])->name('monetization.sponsorships.store');

    // Stripe
    Route::post('/monetization/stripe/connect', [StripeConnectController::class, 'connect'])->name('monetization.stripe.connect');
    Route::post('/monetization/stripe/refresh', [StripeConnectController::class, 'refresh'])->name('monetization.stripe.refresh');

    // House Ads
    Route::get('/monetization/house/new', [HouseAdsController::class,'create'])->name('monetization.house.new');
    Route::post('/monetization/house', [HouseAdsController::class,'store'])->name('monetization.house.store');
    Route::post('/monetization/house/import', [HouseAdsController::class,'import'])->name('monetization.house.import');
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
