<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Http\Requests\AttestationRequest; // options
use Laragear\WebAuthn\Http\Requests\AttestedRequest;    // verify/save

class PasskeyRegisterController extends Controller
{
    // DO NOT type-hint JsonResponse here (package returns a Responsable)
    public function options(AttestationRequest $request)
    {
        Log::info('PASSKEY register/options hit');

        return $request
            ->userless()           // discoverable (resident) credential
            ->secureRegistration() // prompt Windows Hello
            ->toCreate();          // returns JsonTransport (Responsable)
    }

    public function store(AttestedRequest $request): JsonResponse
    {
        Log::info('PASSKEY register/store hit');

        try {
            $request->save(); // persists and links to current user
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('PASSKEY store failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, string $credentialId): JsonResponse
    {
        DB::table('webauthn_credentials')
            ->where('id', $credentialId)
            ->where('authenticatable_id', $request->user()->getKey())
            ->delete();

        return response()->json(['ok' => true]);
    }
}
