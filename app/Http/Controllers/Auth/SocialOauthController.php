<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialConnectController extends Controller
{
    public function redirect(string $provider)
    {
        $driver = $this->driverFor($provider);

        return match ($provider) {
            'linkedin' => Socialite::driver($driver)
                ->scopes(['r_liteprofile','r_emailaddress','w_member_social'])
                ->redirect(),

            'facebook' => Socialite::driver($driver)
                ->scopes(['public_profile','email'])
                ->redirect(),

            'youtube'  => Socialite::driver($driver) // via Google
                ->scopes(['openid','email','profile','https://www.googleapis.com/auth/youtube.upload'])
                ->redirect(),

            'threads'  => back()->with('social_error', 'Threads does not offer a public OAuth for posting yet.'),
            default    => Socialite::driver($driver)->redirect(),
        };
    }

    public function callback(string $provider)
    {
        $driver = $this->driverFor($provider);

        try {
            // If your provider needs stateful flow, drop ->stateless()
            $oauthUser = Socialite::driver($driver)->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()->route('distribution.social')
                ->with('social_error', "Failed to connect {$provider}: ".$e->getMessage());
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('social_error', 'Please sign in first.');
        }

        // Store whatever columns you actually have. Adjust as needed.
        $user->forceFill([
            "social_{$provider}_id"    => $oauthUser->getId(),
            "social_{$provider}_name"  => $oauthUser->getName(),
            // "social_{$provider}_token"         => encrypt($oauthUser->token ?? ''),
            // "social_{$provider}_refresh_token" => encrypt($oauthUser->refreshToken ?? ''),
        ])->save();

        return redirect()->route('distribution.social')->with('social_success', ucfirst($provider).' connected.');
    }

    public function disconnect(Request $request, string $provider)
    {
        $user = $request->user();

        // Nuke whatever you saved for this provider.
        $user->forceFill([
            "social_{$provider}_id"    => null,
            "social_{$provider}_name"  => null,
            // "social_{$provider}_token"         => null,
            // "social_{$provider}_refresh_token" => null,
        ])->save();

        return back()->with('social_success', ucfirst($provider).' disconnected.');
    }

    private function driverFor(string $provider): string
    {
        // Map friendly slugs to Socialite drivers
        return match ($provider) {
            'x'       => 'twitter',   // X = Twitter
            'youtube' => 'google',    // YouTube via Google
            default   => $provider,
        };
    }
}
