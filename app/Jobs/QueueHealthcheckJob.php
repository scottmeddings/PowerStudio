<?php

// app/Jobs/QueueHealthcheckJob.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class QueueHealthcheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 30; public $tries = 1;

    public function handle(): void
    {
        Log::info('[QHC] Healthcheck job picked up by worker');
        usleep(300000); // 0.3s
        Log::info('[QHC] Healthcheck job finished');
    }
}
