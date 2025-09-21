<?php

// app/Listeners/LogNotificationSending.php
namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Log;

class LogNotificationSending
{
    public function handle(NotificationSending $event): void
    {
        Log::info('NotificationSending', [
            'notifiable' => method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : get_class($event->notifiable),
            'notification' => get_class($event->notification),
            'channel' => $event->channel,
        ]);
    }
}
