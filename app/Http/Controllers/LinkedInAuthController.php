<?php
// app/Http/Controllers/LinkedInAuthController.php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class LinkedInAuthController extends Controller
{
    private function callbackUrl(): string
    {
        return route('social.linkedin.callback');
    }

    public function redirect(Request $request)
    {
        $cb = $this->callbackUrl();
        Log::info('LI redirect ->', ['cb' => $cb]);

        // Official Socialite driver: 'linkedin-openid'
        return Socialite::driver('linkedin-openid')
            ->scopes(['openid', 'profile', 'w_member_social']) // no email scope
            ->redirectUrl($cb)
            ->redirect();
    }

    public function callback(Request $request)
    {
        Log::info('LI callback params', $request->query());
        if (! $request->has('code')) {
            return redirect()->route('social.linkedin.redirect')
                ->with('err','LinkedIn did not return an authorization code. Please try again.');
        }

        $cb = $this->callbackUrl();

        $liUser = Socialite::driver('linkedin-openid')
            ->redirectUrl($cb)
            ->user();

        $user = $request->user();

        SocialAccount::updateOrCreate(
            ['user_id' => $user->id, 'provider' => 'linkedin'],
            [
                'external_id'   => $liUser->getId(),
                'access_token'  => $liUser->token,
                'refresh_token' => $liUser->refreshToken ?? null,
                'expires_at'    => $liUser->expiresIn ? now()->addSeconds((int)$liUser->expiresIn) : null,
                'meta'          => ['name' => $liUser->getName(), 'avatar' => $liUser->getAvatar()],
            ]
        );

// LinkedInAuthController@callback (end)
return redirect()->route('distribution.social')->with('ok', 'LinkedIn connected.');
    }
}
