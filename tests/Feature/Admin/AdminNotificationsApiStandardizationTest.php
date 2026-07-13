<?php

namespace Tests\Feature\Admin;

use App\Events\NewUserRegistrationEvent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminNotificationsApiStandardizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_returns_canonical_meta_and_legacy_pagination(): void
    {
        Event::fake([NewUserRegistrationEvent::class]);

        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/notifications?page=1&perPage=10');

        $response->assertOk()->assertJsonStructure([
            'success',
            'data',
            'meta' => ['page', 'perPage', 'total', 'lastPage'],
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_notifications_index_loads_sender_and_recipient_relationships(): void
    {
        Event::fake([NewUserRegistrationEvent::class]);

        $admin = User::factory()->admin()->create();
        $recipient = User::factory()->create(['status' => 'active']);

        Notification::query()->create([
            'user_id' => $recipient->id,
            'sender_id' => $admin->id,
            'type' => 'info',
            'title' => 'Test notification',
            'message' => 'Relationship loading test',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/notifications?page=1&perPage=10');

        $response->assertOk();

        $notification = collect($response->json('data'))
            ->firstWhere('title', 'Test notification');

        $this->assertNotNull($notification);
        $this->assertSame($admin->id, $notification['sender']['id'] ?? null);
        $this->assertSame($recipient->id, $notification['recipient']['id'] ?? null);
    }

    public function test_notifications_export_returns_csv_for_super_admin(): void
    {
        Event::fake([NewUserRegistrationEvent::class]);

        $super = User::factory()->superAdmin()->create();

        Sanctum::actingAs($super);

        $response = $this->get('/api/admin/notifications/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }
}