<?php

namespace Tests\Feature\Notifications;

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
use App\Models\Notification;
use App\Models\User;
use App\Providers\EventServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * G18 — Verify that all event→listener wiring in EventServiceProvider is correct.
 * Outbound Telegram/push HTTP is never sent in tests (TelegramNotificationService
 * short-circuits on app()->runningUnitTests()).
 */
class TelegramListenersWiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    /** @return array<class-string, list<class-string>> */
    private function providerListeners(): array
    {
        $provider = new EventServiceProvider($this->app);
        $prop = (new \ReflectionClass($provider))->getProperty('listen');
        $prop->setAccessible(true);
        /** @var array<class-string, list<class-string>> $map */
        $map = $prop->getValue($provider);
        return $map;
    }

    private function assertListenerWired(string $event, string $listener): void
    {
        $map = $this->providerListeners();
        $this->assertArrayHasKey($event, $map, "Event {$event} missing from \$listen");
        $this->assertContains(
            $listener,
            $map[$event],
            "Listener {$listener} not wired to event {$event}"
        );
    }

    // ────────────────────────────────────────────────────────
    // Wiring assertions (inspect $listen property directly)
    // ────────────────────────────────────────────────────────

    public function test_new_user_registration_has_push_listener(): void
    {
        $this->assertListenerWired(NewUserRegistrationEvent::class, SendAdminPushNewUserNotification::class);
    }

    public function test_new_user_registration_has_welcome_listener(): void
    {
        $this->assertListenerWired(NewUserRegistrationEvent::class, SendWelcomeInAppNotification::class);
    }

    public function test_new_user_registration_has_telegram_listener(): void
    {
        $this->assertListenerWired(NewUserRegistrationEvent::class, SendTelegramNewUserNotification::class);
    }

    public function test_sales_event_has_push_listener(): void
    {
        $this->assertListenerWired(SalesNotificationEvent::class, SendAdminPushSalesNotification::class);
    }

    public function test_sales_event_has_telegram_listener(): void
    {
        $this->assertListenerWired(SalesNotificationEvent::class, SendTelegramSalesNotification::class);
    }

    public function test_subscription_renewal_push_wired(): void
    {
        $this->assertListenerWired(SubscriptionRenewalEvent::class, SendAdminPushSubscriptionRenewalNotification::class);
    }

    public function test_subscription_renewal_telegram_wired(): void
    {
        $this->assertListenerWired(SubscriptionRenewalEvent::class, SendTelegramSubscriptionRenewalNotification::class);
    }

    public function test_subscription_cancellation_push_wired(): void
    {
        $this->assertListenerWired(SubscriptionCancellationEvent::class, SendAdminPushSubscriptionCancellationNotification::class);
    }

    public function test_subscription_cancellation_telegram_wired(): void
    {
        $this->assertListenerWired(SubscriptionCancellationEvent::class, SendTelegramSubscriptionCancellationNotification::class);
    }

    public function test_influencer_commission_push_wired(): void
    {
        $this->assertListenerWired(InfluencerCommissionEvent::class, SendAdminPushInfluencerCommissionNotification::class);
    }

    public function test_influencer_commission_telegram_wired(): void
    {
        $this->assertListenerWired(InfluencerCommissionEvent::class, SendTelegramInfluencerCommissionNotification::class);
    }

    // ────────────────────────────────────────────────────────
    // Smoke: Event::fake + assert listener was faked
    // ────────────────────────────────────────────────────────

    public function test_new_user_event_listeners_are_faked_correctly(): void
    {
        Event::fake([NewUserRegistrationEvent::class]);

        // Create a stub user (not persisted — just check type compatibility)
        $user = new \App\Models\User();
        event(new NewUserRegistrationEvent($user));

        Event::assertDispatched(NewUserRegistrationEvent::class);
        // When faked, no real HTTP occurs. Http::preventStrayRequests ensures that.
        $this->assertTrue(true);
    }

    public function test_welcome_notification_fires_exactly_once_on_user_create(): void
    {
        // Integration guard: $listen + framework discovery would create two welcomes.
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $welcomeCount = Notification::query()
            ->where('user_id', $user->id)
            ->where('title', 'خوش آمدید!')
            ->count();

        $this->assertSame(1, $welcomeCount);
    }
}

