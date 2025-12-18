<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Events
use App\Events\SalesNotificationEvent;
use App\Events\InfluencerCommissionEvent;
use App\Events\NewUserRegistrationEvent;
use App\Events\SubscriptionRenewalEvent;
use App\Events\SubscriptionCancellationEvent;

// Listeners
use App\Listeners\SendTelegramSalesNotification;
use App\Listeners\SendTelegramInfluencerCommissionNotification;
use App\Listeners\SendTelegramNewUserNotification;
use App\Listeners\SendTelegramSubscriptionRenewalNotification;
use App\Listeners\SendTelegramSubscriptionCancellationNotification;

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
        
        // Telegram Notification Events
        SalesNotificationEvent::class => [
            SendTelegramSalesNotification::class,
        ],
        
        InfluencerCommissionEvent::class => [
            SendTelegramInfluencerCommissionNotification::class,
        ],
        
        NewUserRegistrationEvent::class => [
            SendTelegramNewUserNotification::class,
        ],
        
        SubscriptionRenewalEvent::class => [
            SendTelegramSubscriptionRenewalNotification::class,
        ],
        
        SubscriptionCancellationEvent::class => [
            SendTelegramSubscriptionCancellationNotification::class,
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
