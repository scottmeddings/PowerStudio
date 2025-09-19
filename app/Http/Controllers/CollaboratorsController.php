<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Notifications\CollaboratorInviteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
 use Illuminate\Support\Facades\Notification;

class CollaboratorsController extends Controller
{
 


    public function invite(Request $r)
    {
        $data = $r->validate(['email'=>'required|email','role'=>'nullable|in:admin,viewer']);
        $role = $data['role'] ?? 'admin';
        $email = strtolower($data['email']);

        $collab = \App\Models\Collaborator::firstOrCreate(
            ['email'=>$email,'accepted_at'=>null],
            ['role'=>$role,'token'=>\Illuminate\Support\Str::random(48),'invited_by'=>auth()->id()]
        );

        $inviteUrl = url()->route('collab.accept', ['token'=>$collab->token]); // matches current host

        try {
            Notification::route('mail', $email)
                ->notify(new \App\Notifications\CollaboratorInviteNotification($email, $inviteUrl, $role));

            return back()->with('ok', "Invitation emailed to {$email}.");
        } catch (TransportExceptionInterface $e) {
            \Log::warning('Invite email rate-limited', ['email'=>$email,'msg'=>$e->getMessage()]);
            // Email failed, but the invite exists—tell them to copy the link
            return back()->with('err',
                'Email was rate-limited by Mailtrap. Use “Copy Invite Link” below to share the invite manually.'
            );
        }
    }


    public function accept(Request $r, string $token)
    {
        $collab = Collaborator::where('token', $token)->firstOrFail();

        if (!auth()->check()) {
            // Save intended URL and push to login
            session()->put('url.intended', $r->fullUrl());
            return redirect()->route('login')->with('info', 'Please sign in to accept your invite.');
        }

        $collab->user_id     = auth()->id();
        $collab->accepted_at = now();
        $collab->save();

        return redirect()->route('settings/general')->with('ok', 'You now have access.');
    }

    public function revoke(Request $r, int $id)
    {
        $collab = Collaborator::findOrFail($id);
        $collab->delete();
        return back()->with('ok', 'Access revoked for '.$collab->email.'.');
    }
}
