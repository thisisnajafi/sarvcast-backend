<?php

namespace Tests\Feature\Notifications;

use App\Events\NewUserRegistrationEvent;
use App\Listeners\SendAdminPushNewUserNotification;
use App\Models\User;
use App\Services\AdminPushNotificationService;
use App\Models\Notification;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class NewUserRegistrationNotificationLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_success_for_db_and_push_channels_without_sensitive_data(): void
    {
        $primaryAdmin = User::withoutEvents(fn () => User::factory()->create(['role' => 'super_admin', 'status' => 'active']));
        $secondAdmin = User::withoutEvents(fn () => User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $newUser = User::withoutEvents(fn () => User::factory()->create(['role' => 'user', 'status' => 'active']));
        $inAppNotificationService = Mockery::mock(InAppNotificationService::class);
        $inAppNotificationService->shouldReceive('createNotification')
            ->once()
            ->withArgs(function (int $userId, string $type, string $title, string $message, array $options) use ($newUser, $primaryAdmin): bool {
                return $userId === $primaryAdmin->id
                    && $type === 'info'
                    && $title === 'کاربر جدید'
                    && is_string($message)
                    && ($options['send_push'] ?? null) === false
                    && ($options['data']['user_id'] ?? null) === $newUser->id
                    && ($options['data']['alert_kind'] ?? null) === 'registration';
            })
            ->andReturn(new Notification());
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('sendPushNotification')
            ->once()
            ->withArgs(function (User $admin, string $title, string $message, array $data) use ($newUser, $primaryAdmin): bool {
                return $admin->id === $primaryAdmin->id
                    && $title === 'کاربر جدید'
                    && is_string($message)
                    && ($data['user_id'] ?? null) === $newUser->id
                    && ($data['alert_kind'] ?? null) === 'registration';
            })
            ->andReturn(true);
        $notificationService->shouldReceive('sendPushNotification')
            ->once()
            ->withArgs(function (User $admin, string $title, string $message, array $data) use ($newUser, $secondAdmin): bool {
                return $admin->id === $secondAdmin->id
                    && $title === 'کاربر جدید'
                    && is_string($message)
                    && ($data['user_id'] ?? null) === $newUser->id
                    && ($data['alert_kind'] ?? null) === 'registration';
            })
            ->andReturn(true);
        $this->app->instance(NotificationService::class, $notificationService);
        $loggedContexts = [];
        $this->captureNewUserRegistrationLogs($loggedContexts);
        $service = new AdminPushNotificationService($inAppNotificationService);
        $actualSentCount = $service->sendNewUserNotification($newUser);
        $this->assertSame(2, $actualSentCount);
        $successContexts = $this->contextsForResult($loggedContexts, 'success');
        $this->assertCount(2, $successContexts);
        foreach ($successContexts as $context) {
            $this->assertSame((int) $newUser->id, $context['new_user_id'] ?? null);
            $this->assertContextHasNoSensitiveData($context, (string) $newUser->phone_number);
        }
    }

    public function test_it_logs_skip_for_duplicate_event_without_sensitive_data(): void
    {
        User::withoutEvents(fn () => User::factory()->create(['role' => 'admin', 'status' => 'active']));
        $newUser = User::withoutEvents(fn () => User::factory()->create(['role' => 'user', 'status' => 'active']));
        $dedupeKey = "admin_push_new_user_{$newUser->id}";
        Cache::add($dedupeKey, true, now()->addDays(30));
        $service = Mockery::mock(AdminPushNotificationService::class);
        $listener = new SendAdminPushNewUserNotification($service);
        $loggedContexts = [];
        $this->captureNewUserRegistrationLogs($loggedContexts);
        $listener->handle(new NewUserRegistrationEvent($newUser));
        $skipContexts = $this->contextsForResult($loggedContexts, 'skip', 'duplicate_event_deduped');
        $this->assertCount(2, $skipContexts);
        foreach ($skipContexts as $context) {
            $this->assertSame((int) $newUser->id, $context['new_user_id'] ?? null);
            $this->assertContextHasNoSensitiveData($context, (string) $newUser->phone_number);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $loggedContexts
     */
    private function captureNewUserRegistrationLogs(array &$loggedContexts): void
    {
        Log::shouldReceive('channel')->zeroOrMoreTimes()->andReturnSelf();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes()->andReturnUsing(
            function (string $message, array $context = []) use (&$loggedContexts): void {
                if ($message !== 'new_user_registration_notification') {
                    return;
                }
                $loggedContexts[] = $context;
            }
        );
    }

    /**
     * @param array<int, array<string, mixed>> $loggedContexts
     * @return array<int, array<string, mixed>>
     */
    private function contextsForResult(array $loggedContexts, string $result, ?string $reason = null): array
    {
        return array_values(array_filter($loggedContexts, function (array $context) use ($result, $reason): bool {
            $matches = ($context['event_marker'] ?? null) === 'new_user_registration_notification'
                && in_array(($context['channel'] ?? ''), ['db', 'push'], true)
                && ($context['result'] ?? null) === $result;
            if ($reason !== null) {
                $matches = $matches && ($context['reason'] ?? null) === $reason;
            }

            return $matches;
        }));
    }

    private function assertContextHasNoSensitiveData(array $context, string $phoneNumber): void
    {
        $forbiddenKeys = ['phone', 'phone_number', 'email', 'name', 'first_name', 'last_name'];
        foreach ($forbiddenKeys as $forbiddenKey) {
            $this->assertArrayNotHasKey($forbiddenKey, $context);
        }
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
        $this->assertIsString($contextJson);
        $this->assertStringNotContainsString($phoneNumber, $contextJson);
        $this->assertStringNotContainsString('@', $contextJson);
    }
}
