<?php
// app/Listeners/LogNotificationSent.php
namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

class LogNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        Log::info('NotificationSent', [
            'notifiable' => method_exists($event->notifiable, 'getKey') ? $event->notifiable->getKey() : get_class($event->notifiable),
            'notification' => get_class($event->notification),
            'channel' => $event->channel,
            'response' => $event->response ?? null,
        ]);
    }
}
