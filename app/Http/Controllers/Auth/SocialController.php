<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        // For Microsoft, we’ll use the SocialiteProviders package (see step 7).
        $socialUser = Socialite::driver($provider)->user();

        $user = User::where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            // Optional: match by email to link existing accounts
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'provider_name' => $provider,
                    'provider_id'   => $socialUser->getId(),
                    'avatar'        => $socialUser->getAvatar(),
                ]);
            } else {
                $user = User::create([
                    'name'          => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User '.Str::random(6),
                    'email'         => $socialUser->getEmail(),
                    'password'      => bcrypt(Str::random(32)), // placeholder
                    'provider_name' => $provider,
                    'provider_id'   => $socialUser->getId(),
                    'avatar'        => $socialUser->getAvatar(),
                ]);
            }
        }

        Auth::login($user, remember: true);

        return redirect()->intended('/dashboard');
    }
}
