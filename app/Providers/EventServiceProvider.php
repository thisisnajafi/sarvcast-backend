<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Events
use App\Events\SalesNotificationEvent;
use App\Events\InfluencerCommissionEvent;
use App\Events\NewUserRegistrationEvent;
use App\Events\SubscriptionRenewalEvent;
use App\Events\SubscriptionCancellationEvent;

// Listeners
use App\Listeners\SendAdminPushSalesNotification;
use App\Listeners\SendAdminPushInfluencerCommissionNotification;
use App\Listeners\SendAdminPushNewUserNotification;
use App\Listeners\SendWelcomeInAppNotification;
use App\Listeners\SendAdminPushSubscriptionRenewalNotification;
use App\Listeners\SendAdminPushSubscriptionCancellationNotification;

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

        SalesNotificationEvent::class => [
            SendAdminPushSalesNotification::class,
        ],

        InfluencerCommissionEvent::class => [
            SendAdminPushInfluencerCommissionNotification::class,
        ],

        NewUserRegistrationEvent::class => [
            SendAdminPushNewUserNotification::class,
            SendWelcomeInAppNotification::class,
        ],

        SubscriptionRenewalEvent::class => [
            SendAdminPushSubscriptionRenewalNotification::class,
        ],

        SubscriptionCancellationEvent::class => [
            SendAdminPushSubscriptionCancellationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
