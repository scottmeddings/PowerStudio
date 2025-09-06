<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Episode;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct()
    {
        // Commenting requires login
        $this->middleware('auth');

        // Optional: rate-limit posting
        $this->middleware('throttle:20,1')->only('store');
    }

    public function store(Request $request, Episode $episode)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        // Create through relation
        $comment = $episode->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => trim($data['body']),
            'status'  => 'approved', // switch to 'pending' if you want moderation
        ]);

        return redirect()
            ->to(route('episodes.show', $episode) . '#comments')
            ->with('success', 'Comment posted.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        // If you don't have a policy yet, do a simple owner/admin check.
        // Replace "is_admin" with whatever you use.
        if ($request->user()->id !== $comment->user_id && !($request->user()->is_admin ?? false)) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Comment removed.');
    }

    public function approve(Request $request, Comment $comment)
    {
        // Lock this down however you like (policy or simple role check)
        if (!($request->user()->is_admin ?? false)) {
            abort(403);
        }

        $comment->update(['status' => 'approved']);

        return back()->with('success', 'Comment approved.');
    }
}
