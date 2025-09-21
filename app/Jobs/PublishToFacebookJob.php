<?php
// app/Jobs/PublishToFacebookJob.php
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

class PublishToFacebookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $postId) {}

    public function handle(): void
    {
        $post = SocialPost::find($this->postId);
        if (!$post) return;

        $acct = SocialAccount::where('user_id',$post->user_id)->where('provider','facebook')->first();
        if (!$acct) { Log::warning('FB job: no account'); return; }

       
        $pageId = $acct->meta['page_id'] ?? $acct->external_id;
        $token  = $acct->access_token;
        if (!$pageId || !$token) {
            Log::warning('FB job: missing page token or page id (you likely saved a user token).');
            return;
        }
        $res = Http::asForm()->post("https://graph.facebook.com/v19.0/{$pageId}/feed", [
        'message' => $message,
        'access_token' => $token,
        ]);
        

        if ($res->successful()) {
            $post->update(['status'=>'sent', 'meta'=>array_merge((array)$post->meta, ['facebook'=>$res->json()])]);
        } else {
            $post->update(['status'=>'error']);
            Log::error('FB post failed', ['status'=>$res->status(), 'body'=>$res->body()]);
            $this->fail(new \RuntimeException('Facebook API error '.$res->status()));
        }
    }
}
