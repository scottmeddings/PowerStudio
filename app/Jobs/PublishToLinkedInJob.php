<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Models\SocialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublishToLinkedInJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;
    public $timeout = 90; // seconds

    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }

    public function handle(): void
    {
        $post = SocialPost::find($this->postId);
        if (!$post) { Log::warning('LI job: post missing', ['id'=>$this->postId]); return; }

        $account = SocialAccount::where('user_id', $post->user_id)
            ->where('provider','linkedin')->first();

        if (!$account) { Log::warning('LI job: account missing', ['user'=>$post->user_id]); return; }

        $token = $account->access_token; // if you encrypt via Accessor, this is already decrypted
        $personUrn = 'urn:li:person:' . $account->external_id;

        // Minimal payload (text only)
        $payload = [
            'author' => $personUrn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $post->body ?: ($post->title ?? '')],
                    'shareMediaCategory' => 'NONE',
                ]
            ],
            'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
        ];

        try {
            $res = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->post('https://api.linkedin.com/v2/ugcPosts', $payload);

            if ($res->successful()) {
                $post->update(['status' => 'sent', 'meta' => array_merge((array)$post->meta, ['linkedin' => $res->json()])]);
                Log::info('LI post success', ['post'=>$post->id]);
            } else {
                $post->update(['status' => 'error']);
                Log::error('LI post failed', ['post'=>$post->id, 'code'=>$res->status(), 'body'=>$res->body()]);
                $this->fail(new \RuntimeException('LinkedIn API error '.$res->status()));
            }
        } catch (\Throwable $e) {
            $post->update(['status' => 'error']);
            Log::error('LI post exception', ['post'=>$post->id, 'e'=>$e->getMessage()]);
            throw $e;
        }
    }
}
