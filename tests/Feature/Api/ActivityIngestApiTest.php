<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityIngestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_ingest_activity_batch(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Sanctum::actingAs($user);

        $eventUuid = (string) Str::uuid();

        $response = $this->postJson('/api/v1/activity/batch', [
            'device_id' => 'device-test-001',
            'app_version' => '1.4.11',
            'platform' => 'android',
            'events' => [
                [
                    'event_uuid' => $eventUuid,
                    'action' => 'episode.play_started',
                    'subject_type' => 'episode',
                    'subject_id' => '42',
                    'subject_label' => 'قسمت اول',
                    'occurred_at' => now()->toIso8601String(),
                    'properties' => [
                        'story_id' => '7',
                        'position_sec' => 0,
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.accepted', 1)
            ->assertJsonPath('data.skipped', 0);

        $this->assertDatabaseHas('activity_logs', [
            'channel' => ActivityLog::CHANNEL_APP,
            'actor_user_id' => $user->id,
            'action' => 'episode.play_started',
            'device_id' => 'device-test-001',
            'event_uuid' => $eventUuid,
            'subject_id' => '42',
        ]);
    }

    public function test_duplicate_event_uuid_is_skipped(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Sanctum::actingAs($user);

        $eventUuid = (string) Str::uuid();
        $payload = [
            'device_id' => 'device-dedupe-001',
            'platform' => 'ios',
            'events' => [
                [
                    'event_uuid' => $eventUuid,
                    'action' => 'favorite.added',
                    'subject_type' => 'story',
                    'subject_id' => '3',
                ],
            ],
        ];

        $this->postJson('/api/v1/activity/batch', $payload)
            ->assertJsonPath('data.accepted', 1);

        $this->postJson('/api/v1/activity/batch', $payload)
            ->assertOk()
            ->assertJsonPath('data.accepted', 0)
            ->assertJsonPath('data.skipped', 1);

        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_duplicate_event_uuid_inside_same_batch_is_skipped(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Sanctum::actingAs($user);

        $eventUuid = (string) Str::uuid();

        $response = $this->postJson('/api/v1/activity/batch', [
            'device_id' => 'device-dedupe-same-batch',
            'platform' => 'android',
            'events' => [
                [
                    'event_uuid' => $eventUuid,
                    'action' => 'app.lifecycle_resumed',
                    'subject_type' => 'app',
                ],
                [
                    'event_uuid' => $eventUuid,
                    'action' => 'app.lifecycle_resumed',
                    'subject_type' => 'app',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.accepted', 1)
            ->assertJsonPath('data.skipped', 1);

        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_guest_cannot_ingest_activity_batch(): void
    {
        $this->postJson('/api/v1/activity/batch', [
            'device_id' => 'device-guest',
            'events' => [
                [
                    'event_uuid' => (string) Str::uuid(),
                    'action' => 'auth.login_completed',
                ],
            ],
        ])->assertUnauthorized();
    }

    public function test_user_can_list_own_app_activity_logs(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Sanctum::actingAs($user);

        ActivityLog::query()->create([
            'channel' => ActivityLog::CHANNEL_APP,
            'actor_user_id' => $user->id,
            'actor_type' => 'user',
            'action' => 'episode.play_started',
            'subject_type' => 'episode',
            'subject_id' => '10',
            'description' => 'فعالیت پخش',
            'device_id' => 'device-list',
            'event_uuid' => (string) Str::uuid(),
            'status' => ActivityLog::STATUS_SUCCESS,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/activity');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'episode.play_started');
    }
}
