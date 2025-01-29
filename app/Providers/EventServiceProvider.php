<?php

namespace App\Providers;

/*use App\Events\Eversend\CardAdjustedEvent;
use App\Events\Eversend\CardMaintenanceEvent;
use App\Events\Eversend\CardPayementEvent;
use App\Events\Eversend\CardPayementFailedEvent;
use App\Events\Eversend\CardTerminatedEvent;
use App\Listeners\Eversend\CardMaintenanceListener;
use App\Listeners\Eversend\CardPayemenFailedtListener;
use App\Listeners\Eversend\CardPayementListener;
use App\Listeners\Eversend\CardTerminatedListener;
use App\Listeners\Eversend\CardAdjustedListener;*/
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
        
        \App\Events\Eversend\CardTerminatedEvent::class=>[
            \App\Listeners\Eversend\CardTerminatedListener::class,
        ],
        \App\Events\Eversend\CardAdjustedEvent::class=>[
            \App\Listeners\Eversend\CardAdjustedListener::class,
        ],
        \App\Events\Eversend\CardPayementEvent::class=>[
            \App\Listeners\Eversend\CardPayementListener::class,
        ],
        \App\Events\Eversend\CardPayementFailedEvent::class=>[
            \App\Listeners\Eversend\CardPayemenFailedtListener::class,
        ],
        \App\Events\Eversend\CardMaintenanceEvent::class=>[
            \App\Listeners\Eversend\CardMaintenanceListener::class,
        ],

        \App\Events\Strowallet\CardTerminatedEvent::class=>[
            \App\Listeners\Strowallet\CardTerminatedListener::class,
        ],
        \App\Events\Strowallet\CardPayementEvent::class=>[
            \App\Listeners\Strowallet\CardPayementListener::class,
        ],
        \App\Events\Strowallet\CardPayementFailedEvent::class=>[
            \App\Listeners\Strowallet\CardPayemenFailedtListener::class,
        ],
        \App\Events\Maplerad\CardPayementEvent::class=>[
            \App\Listeners\Maplerad\CardPayementListener::class,
        ],
        \App\Events\Maplerad\CardTerminatedEvent::class=>[
            \App\Listeners\Maplerad\CardTerminatedListener::class,
        ],
        \App\Events\Maplerad\CardPayementFailedEvent::class=>[
            \App\Listeners\Maplerad\CardPayemenFailedtListener::class,
        ],
        \App\Events\Soleaspay\CardPayementEvent::class=>[
            \App\Listeners\Soleaspay\CardPayementListener::class,
        ],
        \App\Events\Soleaspay\CardPayementFailedEvent::class=>[
            \App\Listeners\Soleaspay\CardPayemenFailedtListener::class,
        ],
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
