<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
    public function uploadCover(Request $request)
    {
        $request->validate([
            // square art recommended ≥1400px; allow jpeg/png/webp up to 5 MB
            'cover' => ['required','image','mimes:jpeg,jpg,png,webp','max:5120','dimensions:min_width=1400,min_height=1400'],
        ]);

        $user = $request->user();

        // Delete old file if present
        if ($user->cover_path && Storage::disk('public')->exists($user->cover_path)) {
            Storage::disk('public')->delete($user->cover_path);
        }

        // Store new file on the public disk
        $path = $request->file('cover')->store("covers/{$user->id}", 'public');

        // Save path on the user
        $user->cover_path = $path;
        $user->save();

        return back()->with('success', 'Cover image updated.');
    }
}

