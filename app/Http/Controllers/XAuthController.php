<?php
// app/Http/Controllers/XAuthController.php
namespace App\Http\Controllers;

use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class XAuthController extends Controller
{
    private function cfg() {
        return [
            'client_id'     => config('services.twitter.client_id'),
            'client_secret' => config('services.twitter.client_secret'),
            'redirect_uri'  => route('social.x.callback'),
            'auth'          => 'https://twitter.com/i/oauth2/authorize',
            'token'         => 'https://api.twitter.com/2/oauth2/token',
            'scope'         => 'tweet.read tweet.write users.read offline.access',
        ];
    }

    public function redirect(Request $r)
    {
        $c     = $this->cfg();
        $state = Str::random(40);
        $r->session()->put('x_oauth_state', $state);
        $codeVerifier  = Str::random(64);
        $r->session()->put('x_code_verifier', $codeVerifier);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $url = $c['auth'].'?'.http_build_query([
            'response_type' => 'code',
            'client_id'     => $c['client_id'],
            'redirect_uri'  => $c['redirect_uri'],
            'scope'         => $c['scope'],
            'state'         => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $r)
    {
        if ($r->input('state') !== $r->session()->pull('x_oauth_state')) {
            return redirect()->route('distribution.social')->with('err', 'X auth: state mismatch.');
        }

        $c = $this->cfg();
        $resp = Http::asForm()->post($c['token'], [
            'grant_type'    => 'authorization_code',
            'client_id'     => $c['client_id'],
            'redirect_uri'  => $c['redirect_uri'],
            'code'          => $r->input('code'),
            'code_verifier' => $r->session()->pull('x_code_verifier'),
        ]);

        if (!$resp->ok()) {
            return redirect()->route('distribution.social')
                ->with('err', 'X auth failed: '.$resp->body());
        }

        $json = $resp->json();
        // $json has: access_token, refresh_token, expires_in, scope, token_type (bearer)

        SocialAccount::updateOrCreate(
            ['user_id' => $r->user()->id, 'provider' => 'x'],
            [
                'external_id'  => null,
                'access_token' => encrypt($json['access_token']),
                'refresh_token'=> isset($json['refresh_token']) ? encrypt($json['refresh_token']) : null,
                'expires_at'   => now()->addSeconds((int)($json['expires_in'] ?? 0)),
                'meta'         => [
                    'token_type' => $json['token_type'] ?? 'bearer',
                    'scope'      => $json['scope'] ?? '',
                    'can_publish'=> str_contains($json['scope'] ?? '', 'tweet.write'),
                ],
            ]
        );

        return redirect()->route('distribution.social')->with('ok','X connected.');
    }
}
