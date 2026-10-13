<?php

namespace App\Providers;

use App\Events\InfluencerCommissionEvent;
use App\Events\NewUserRegistrationEvent;
use App\Events\SalesNotificationEvent;
use App\Events\SubscriptionCancellationEvent;
use App\Events\SubscriptionRenewalEvent;
use App\Listeners\SendAdminPushInfluencerCommissionNotification;
use App\Listeners\SendAdminPushNewUserNotification;
use App\Listeners\SendAdminPushSalesNotification;
use App\Listeners\SendAdminPushSubscriptionCancellationNotification;
use App\Listeners\SendAdminPushSubscriptionRenewalNotification;
use App\Listeners\SendTelegramInfluencerCommissionNotification;
use App\Listeners\SendTelegramNewUserNotification;
use App\Listeners\SendTelegramSalesNotification;
use App\Listeners\SendTelegramSubscriptionCancellationNotification;
use App\Listeners\SendTelegramSubscriptionRenewalNotification;
use App\Listeners\SendWelcomeInAppNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * Discovery is OFF (shouldDiscoverEvents = false), so ALL listeners
     * must be registered here explicitly.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        NewUserRegistrationEvent::class => [
            SendAdminPushNewUserNotification::class,
            SendWelcomeInAppNotification::class,
            SendTelegramNewUserNotification::class,
        ],

        SalesNotificationEvent::class => [
            SendAdminPushSalesNotification::class,
            SendTelegramSalesNotification::class,
        ],

        SubscriptionRenewalEvent::class => [
            SendAdminPushSubscriptionRenewalNotification::class,
            SendTelegramSubscriptionRenewalNotification::class,
        ],

        SubscriptionCancellationEvent::class => [
            SendAdminPushSubscriptionCancellationNotification::class,
            SendTelegramSubscriptionCancellationNotification::class,
        ],

        InfluencerCommissionEvent::class => [
            SendAdminPushInfluencerCommissionNotification::class,
            SendTelegramInfluencerCommissionNotification::class,
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
