<?php

namespace Tests\Feature\Notifications;

use App\Events\NewUserRegistrationEvent;
use App\Listeners\SendAdminPushNewUserNotification;
use App\Listeners\SendTelegramNewUserNotification;
use App\Models\User;
use App\Services\AdminPushNotificationService;
use App\Support\NewUserNotificationHttp;
use App\Support\NewUserNotificationLogger;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

class NewUserAdminPushRunsSynchronouslyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_push_listener_is_synchronous_and_after_commit(): void
    {
        $this->assertNotInstanceOf(ShouldQueue::class, new SendAdminPushNewUserNotification(
            Mockery::mock(AdminPushNotificationService::class)
        ));
        $this->assertInstanceOf(
            ShouldHandleEventsAfterCommit::class,
            new SendAdminPushNewUserNotification(Mockery::mock(AdminPushNotificationService::class))
        );
    }

    public function test_creating_a_user_does_not_queue_new_user_listeners(): void
    {
        config(['queue.default' => 'database']);
        Http::preventStrayRequests();
        User::factory()->create(['status' => 'active', 'role' => 'user']);
        $payloads = DB::table('jobs')->pluck('payload')->all();
        $queuedNewUserListeners = array_values(array_filter(
            $payloads,
            static function (mixed $payload): bool {
                $text = (string) $payload;

                return str_contains($text, SendAdminPushNewUserNotification::class)
                    || str_contains($text, SendTelegramNewUserNotification::class);
            }
        ));
        $this->assertSame([], $queuedNewUserListeners);
    }

    public function test_push_failure_does_not_escape_the_listener(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create([
            'status' => 'active',
            'role' => 'user',
        ]));
        $service = Mockery::mock(AdminPushNotificationService::class);
        $service->shouldReceive('sendNewUserNotification')
            ->once()
            ->andThrow(new RuntimeException('fcm down'));
        $listener = new SendAdminPushNewUserNotification($service);
        $listener->handle(new NewUserRegistrationEvent($user));
        $this->assertTrue(Cache::has("admin_push_new_user_{$user->id}"));
    }

    public function test_registration_http_client_times_out_at_five_seconds(): void
    {
        $client = NewUserNotificationHttp::client();
        $this->assertInstanceOf(PendingRequest::class, $client);
        $options = new ReflectionProperty(PendingRequest::class, 'options');
        $options->setAccessible(true);
        $actual = $options->getValue($client);
        $this->assertSame(NewUserNotificationHttp::TIMEOUT_SECONDS, $actual['timeout']);
        $this->assertSame(NewUserNotificationHttp::TIMEOUT_SECONDS, $actual['connect_timeout']);
        $this->assertSame(5, NewUserNotificationHttp::TIMEOUT_SECONDS);
    }

    public function test_event_dispatch_runs_admin_push_without_queue_context(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create([
            'status' => 'active',
            'role' => 'user',
        ]));
        $service = Mockery::mock(AdminPushNotificationService::class);
        $service->shouldReceive('sendNewUserNotification')->once()->andReturn(1);
        $this->app->instance(AdminPushNotificationService::class, $service);
        $context = NewUserNotificationLogger::queueContext(
            $this->app->make(SendAdminPushNewUserNotification::class)
        );
        $this->assertFalse($context['queued']);
        $this->assertNull($context['queue_connection']);
        event(new NewUserRegistrationEvent($user));
    }
}
