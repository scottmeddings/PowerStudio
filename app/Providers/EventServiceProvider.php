<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class.'@handle',
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
