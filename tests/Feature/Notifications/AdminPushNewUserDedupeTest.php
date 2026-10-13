<?php

namespace Tests\Feature\Notifications;

use App\Events\NewUserRegistrationEvent;
use App\Listeners\SendAdminPushNewUserNotification;
use App\Models\User;
use App\Services\AdminPushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * G18 — Admin push new-user deduplication.
 *
 * The listener holds a 30-day Cache::add marker.  A second dispatch for the
 * same user must be silently skipped without calling the push service again.
 */
class AdminPushNewUserDedupeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    /**
     * Create a user while suppressing model events, then flush cache.
     * We suppress events because User::created fires NewUserRegistrationEvent which
     * would set the dedupe cache key before the test can assert on it.
     */
    private function makeUser(): User
    {
        $user = User::withoutEvents(fn () => User::factory()->create(['status' => 'active', 'role' => 'user']));
        // Also flush any leftover cache from prior tests
        Cache::flush();
        return $user;
    }

    private function makeListener(?AdminPushNotificationService $service = null): SendAdminPushNewUserNotification
    {
        $svc = $service ?? Mockery::mock(AdminPushNotificationService::class);
        return new SendAdminPushNewUserNotification($svc);
    }

    public function test_first_dispatch_calls_push_service(): void
    {
        $user = $this->makeUser();

        $svc = Mockery::mock(AdminPushNotificationService::class);
        $svc->shouldReceive('sendNewUserNotification')
            ->once()
            ->with(Mockery::type(User::class))
            ->andReturn(1);

        $listener = $this->makeListener($svc);
        $listener->handle(new NewUserRegistrationEvent($user));
    }

    public function test_second_dispatch_is_skipped(): void
    {
        $user = $this->makeUser();

        $svc = Mockery::mock(AdminPushNotificationService::class);
        // Should only be called once across two handle() invocations
        $svc->shouldReceive('sendNewUserNotification')
            ->once()
            ->with(Mockery::type(User::class))
            ->andReturn(1);

        $listener = $this->makeListener($svc);
        $event    = new NewUserRegistrationEvent($user);

        $listener->handle($event); // first: sends
        $listener->handle($event); // second: skipped
    }

    public function test_cache_key_exists_after_first_send(): void
    {
        $user = $this->makeUser();

        $svc = Mockery::mock(AdminPushNotificationService::class);
        $svc->shouldReceive('sendNewUserNotification')->once()->andReturn(1);

        $listener = $this->makeListener($svc);
        $listener->handle(new NewUserRegistrationEvent($user));

        $this->assertTrue(Cache::has("admin_push_new_user_{$user->id}"));
    }

    public function test_cache_key_survives_beyond_10_minutes(): void
    {
        // Ensure TTL is 30 days, not the old 10-minute value.
        $user = $this->makeUser();

        $svc = Mockery::mock(AdminPushNotificationService::class);
        $svc->shouldReceive('sendNewUserNotification')->once()->andReturn(1);

        $listener = $this->makeListener($svc);
        $listener->handle(new NewUserRegistrationEvent($user));

        // Travel 11 minutes into the future — key must still exist (30-day TTL)
        $this->travel(11)->minutes();

        $this->assertTrue(
            Cache::has("admin_push_new_user_{$user->id}"),
            'Dedupe key must survive beyond 10 minutes (TTL is now 30 days, not 10 minutes)'
        );
    }
}
