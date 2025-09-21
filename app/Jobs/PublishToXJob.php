<?php
// app/Jobs/PublishToXJob.php
namespace App\Jobs;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class PublishToXJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $postId) {}

    public function handle(): void
    {
        $post = SocialPost::findOrFail($this->postId);
        $acct = SocialAccount::where('user_id',$post->user_id)->where('provider','x')->first();

        $token = decrypt($acct->access_token);
        $text  = trim($post->body);

        $res = Http::withToken($token)->post('https://api.twitter.com/2/tweets', [
            'text' => $text,
        ]);

        if (!$res->ok()) {
            logger()->error('X post failed', ['status'=>$res->status(), 'body'=>$res->body()]);
            return;
        }

        // optionally store the tweet id
        $tweetId = $res->json('data.id');
        $post->update(['external_ids' => array_merge(($post->external_ids ?? []), ['x'=>$tweetId])]);
    }
}
