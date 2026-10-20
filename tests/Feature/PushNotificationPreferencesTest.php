<?php

namespace Tests\Feature;

use App\Events\NewUserRegistrationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PushNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([NewUserRegistrationEvent::class]);
    }

    public function test_update_notification_preferences_merges_without_wiping(): void
    {
        $user = User::factory()->create([
            'preferences' => [
                'language' => 'fa',
                'push_notifications_enabled' => true,
            ],
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/v1/user/notification-preferences', [
            'push_notifications_enabled' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.push_notifications_enabled', false);

        $user->refresh();
        $this->assertSame('fa', $user->preferences['language'] ?? null);
        $this->assertFalse($user->preferences['push_notifications_enabled']);
    }

    public function test_update_profile_merges_preferences_without_wiping_push_flag(): void
    {
        $user = User::factory()->create([
            'preferences' => [
                'push_notifications_enabled' => true,
                'language' => 'fa',
            ],
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/v1/auth/profile', [
            'preferences' => [
                'audio_quality' => 'high',
            ],
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertTrue($user->preferences['push_notifications_enabled']);
        $this->assertSame('fa', $user->preferences['language']);
        $this->assertSame('high', $user->preferences['audio_quality']);
    }

    public function test_backfill_command_dry_run_does_not_write(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->forceFill(['preferences' => null])->saveQuietly();

        $this->artisan('users:backfill-push-notification-preferences', ['--dry-run' => true])
            ->assertSuccessful();

        $user->refresh();
        $this->assertNull($user->preferences);
    }

    public function test_backfill_command_sets_missing_push_flag(): void
    {
        $missing = User::withoutEvents(fn () => User::factory()->create());
        $missing->forceFill(['preferences' => ['language' => 'fa']])->saveQuietly();

        $alreadySet = User::withoutEvents(fn () => User::factory()->create());
        $alreadySet->forceFill([
            'preferences' => ['push_notifications_enabled' => false, 'language' => 'en'],
        ])->saveQuietly();

        $nullPrefs = User::withoutEvents(fn () => User::factory()->create());
        $nullPrefs->forceFill(['preferences' => null])->saveQuietly();

        $this->artisan('users:backfill-push-notification-preferences')
            ->assertSuccessful();

        $missing->refresh();
        $alreadySet->refresh();
        $nullPrefs->refresh();

        $this->assertTrue($missing->preferences['push_notifications_enabled']);
        $this->assertSame('fa', $missing->preferences['language']);
        $this->assertFalse($alreadySet->preferences['push_notifications_enabled']);
        $this->assertTrue($nullPrefs->preferences['push_notifications_enabled']);
    }
}
