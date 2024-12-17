<?php

namespace App\Providers;

use App\Events\Eversend\CardAdjustedEvent;
use App\Events\Eversend\CardMaintenanceEvent;
use App\Events\Eversend\CardPayementEvent;
use App\Events\Eversend\CardPayementFailedEvent;
use App\Events\Eversend\CardTerminatedEvent;
use App\Listeners\Eversend\CardMaintenanceListener;
use App\Listeners\Eversend\CardPayemenFailedtListener;
use App\Listeners\Eversend\CardPayementListener;
use App\Listeners\Eversend\CardTerminatedListener;
use App\Listeners\Eversend\CardAdjustedListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        CardTerminatedEvent::class=>[
            CardTerminatedListener::class,
        ],
        CardAdjustedEvent::class=>[
            CardAdjustedListener::class,
        ],
        CardPayementEvent::class=>[
            CardPayementListener::class,
        ],
        CardPayementFailedEvent::class=>[
            CardPayemenFailedtListener::class,
        ],
        CardMaintenanceEvent::class=>[
            CardMaintenanceListener::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
