<?php
// app/Http/Controllers/FacebookPublishSetupController.php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FacebookPublishSetupController extends Controller
{
    // Show a list of Pages the user manages; requires the stored *user* token
    public function selectPage(Request $req)
    {
        $user = $req->user();
        $acct = SocialAccount::where('user_id', $user->id)->where('provider', 'facebook')->firstOrFail();

        // This should be the (long-lived) user token with pages_show_list permission
        $userToken = $acct->access_token;

        $resp  = Http::get('https://graph.facebook.com/v19.0/me/accounts', [
            'access_token' => $userToken,
        ]);

        $pages = $resp->ok() ? ($resp->json('data') ?? []) : [];

        return view('pages.facebook_select_page', compact('pages'));
    }

    // Save a Page selection and swap to a *Page access token* for publishing
    public function save(Request $req)
    {
        $validated = $req->validate(['page_id' => 'required|string']);
        $pageId = $validated['page_id'];

        $user = $req->user();
        $userToken = SocialAccount::where('user_id', $user->id)
            ->where('provider', 'facebook')
            ->value('access_token');

        // Request a Page token via Graph
        $res = Http::get("https://graph.facebook.com/v19.0/{$pageId}", [
            'fields'       => 'access_token',
            'access_token' => $userToken,
        ]);

        $pageToken = $res->json('access_token') ?? null;
        if (!$res->ok() || !$pageToken) {
            return back()->with('err',
                'Could not obtain Page access token. Ensure permissions: pages_show_list & pages_manage_posts.'
            );
        }

        // Store Page ID + Page token (this enables publishing)
        SocialAccount::updateOrCreate(
            ['user_id' => $user->id, 'provider' => 'facebook'],
            [
                'external_id'  => $pageId,
                'access_token' => $pageToken,                // now a PAGE token
                'meta'         => ['page_id' => $pageId, 'type' => 'page'],
            ]
        );

        return redirect()->route('distribution.social')
            ->with('ok', 'Facebook Page connected and publish-ready.');
    }
}
