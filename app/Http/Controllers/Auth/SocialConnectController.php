<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite; // ← import THIS

class SocialConnectController extends Controller
{
    // Map UI slugs to Socialite drivers
    protected array $drivers = [
        'linkedin' => 'linkedin',
        'facebook' => 'facebook',
        'x'        => 'twitter-oauth-2', // requires SocialiteProviders/twitter-oauth-2
        'youtube'  => 'google',          // Google OAuth, you’ll still post via YouTube Data API
        // 'instagram' / 'threads' / 'tiktok' need custom providers or APIs
    ];
    public function disconnect(string $provider)
    {
        // TODO: remove tokens from your storage
        return redirect()->route('distribution.social')->with('success', ucfirst($provider).' disconnected.');
    }
    public function redirect(string $provider)
    {
        abort_unless(isset($this->drivers[$provider]), 404, 'Provider not supported yet.');
        return Socialite::driver($this->drivers[$provider])->redirect();
    }

    public function callback(string $provider, Request $request)
    {
        abort_unless(isset($this->drivers[$provider]), 404, 'Provider not supported yet.');

        // If provider aborted or no "code" param, bounce back with an error
        if (!$request->has('code')) {
            return redirect()->route('distribution.social')
                ->with('error', ucfirst($provider).' authorization was cancelled or failed.');
        }

        try {
            $socialUser = Socialite::driver($this->drivers[$provider])->user();
            // TODO: Save $socialUser->token / refreshToken / expiresIn etc. to your connections table
            return redirect()->route('distribution.social')
                ->with('success', ucfirst($provider).' connected.');
        } catch (\Throwable $e) {
            Log::error('Social callback error', ['provider'=>$provider, 'e'=>$e]);
            return redirect()->route('distribution.social')
                ->with('error', 'Could not complete '.$provider.' connect: '.$e->getMessage());
        }
    }
}
