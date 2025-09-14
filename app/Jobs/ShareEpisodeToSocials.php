<?php

// app/Jobs/ShareEpisodeToSocials.php
namespace App\Jobs;

use App\Models\Episode;
use App\Models\SocialConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ShareEpisodeToSocials implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Episode $episode) {}

    public function handle(): void
    {
        $userId = $this->episode->user_id;
        $connections = SocialConnection::where('user_id',$userId)
            ->where('is_connected', true)->get();

        foreach ($connections as $conn) {
            // Here we just delegate to the controller’s helper—keeps logic in one place.
            app(\App\Http\Controllers\DistributionController::class)
                ->pushToProvider($conn, $this->episode);
        }
    }
}
