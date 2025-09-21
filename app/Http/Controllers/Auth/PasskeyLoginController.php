<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Http\Requests\AssertionRequest; // options
use Laragear\WebAuthn\Http\Requests\AssertedRequest;  // verify/login

class PasskeyLoginController extends Controller
{
    // DO NOT type-hint JsonResponse here (package returns a Responsable)
    public function options(AssertionRequest $request)
    {
        Log::info('PASSKEY login/options hit');

        return $request
            ->secureLogin() // require user verification (Hello)
            ->toVerify();   // returns JsonTransport (Responsable)
    }

    public function verify(AssertedRequest $request): JsonResponse
    {
        $user = $request->login(remember: true);
        Log::info('PASSKEY login/verify', ['user_id' => $user?->getAuthIdentifier()]);

        return response()->json([
            'ok'       => (bool) $user,
            'redirect' => session('url.intended', url('/')),
        ], $user ? 200 : 422);
    }
}
