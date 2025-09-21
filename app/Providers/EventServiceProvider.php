<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [ 
        
        
        Illuminate\Notifications\Events\NotificationSending::class => [
        App\Listeners\LogNotificationSending::class,
        ],
        Illuminate\Notifications\Events\NotificationSent::class => [
        App\Listeners\LogNotificationSent::class,
        ],


        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class.'@handle',
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
