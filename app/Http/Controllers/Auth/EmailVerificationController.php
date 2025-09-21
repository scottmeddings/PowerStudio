<?php

// app/Http/Controllers/Auth/EmailVerificationController.php
namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class EmailVerificationController extends Controller
{
    public function send(Request $request)
    {
        Log::info('verification.send', ['user_id' => $request->user()?->id]);

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('verification.send failed', ['ex' => $e->getMessage()]);
            return back()->with('verification.error', $e->getMessage());
        }

        return back()->with('status', 'verification-link-sent');
    }
}
