<?php

namespace Tests\Unit;

use App\Events\NewUserRegistrationEvent;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationPushPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        // Only fake registration listeners; keep Eloquent creating hooks (defaults).
        Event::fake([NewUserRegistrationEvent::class]);
        $this->notificationService = app(NotificationService::class);
    }

    public function test_new_user_gets_default_push_notifications_enabled(): void
    {
        $user = User::factory()->create([
            'preferences' => null,
        ]);

        $user->refresh();

        $this->assertIsArray($user->preferences);
        $this->assertTrue($user->preferences['push_notifications_enabled']);
        $this->assertTrue($this->notificationService->userAllowsPushNotifications($user));
    }

    public function test_new_user_preserves_explicit_push_preference(): void
    {
        $user = User::factory()->create([
            'preferences' => ['push_notifications_enabled' => false],
        ]);

        $user->refresh();

        $this->assertFalse($user->preferences['push_notifications_enabled']);
        $this->assertFalse($this->notificationService->userAllowsPushNotifications($user));
    }

    /**
     * @dataProvider booleanLikeTruthyProvider
     */
    public function test_boolean_like_truthy_values_allow_push(mixed $value): void
    {
        $user = User::factory()->create([
            'preferences' => ['push_notifications_enabled' => $value],
        ]);

        $this->assertTrue($this->notificationService->userAllowsPushNotifications($user));
    }

    /**
     * @dataProvider booleanLikeFalsyProvider
     */
    public function test_boolean_like_falsy_values_block_push(mixed $value): void
    {
        $user = User::factory()->create([
            'preferences' => ['push_notifications_enabled' => $value],
        ]);

        $this->assertFalse($this->notificationService->userAllowsPushNotifications($user));
    }

    public function test_missing_push_key_defaults_to_allowed(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create([
            'preferences' => ['language' => 'fa'],
        ]));

        // Bypass creating() default by forcing preferences without the key
        $user->forceFill(['preferences' => ['language' => 'fa']])->saveQuietly();
        $user->refresh();

        $this->assertArrayNotHasKey('push_notifications_enabled', $user->preferences ?? []);
        $this->assertTrue($this->notificationService->userAllowsPushNotifications($user));
    }

    public static function booleanLikeTruthyProvider(): array
    {
        return [
            'bool true' => [true],
            'int 1' => [1],
            'string 1' => ['1'],
            'string true' => ['true'],
            'string on' => ['on'],
            'string yes' => ['yes'],
        ];
    }

    public static function booleanLikeFalsyProvider(): array
    {
        return [
            'bool false' => [false],
            'int 0' => [0],
            'string 0' => ['0'],
            'string false' => ['false'],
            'string off' => ['off'],
            'string no' => ['no'],
        ];
    }
}
