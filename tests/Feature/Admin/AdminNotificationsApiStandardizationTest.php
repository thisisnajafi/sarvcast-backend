<?php

namespace Tests\Feature\Admin;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminNotificationsApiStandardizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_returns_canonical_meta_and_legacy_pagination(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'notifications-meta@test.com',
            'status' => 'active',
        ]);

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
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'notifications-relations@test.com',
            'status' => 'active',
        ]);
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

        $response->assertOk()
            ->assertJsonPath('data.0.sender.id', $admin->id)
            ->assertJsonPath('data.0.recipient.id', $recipient->id);
    }

    public function test_notifications_export_returns_csv_for_super_admin(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'notifications-export@test.com',
            'status' => 'active',
        ]);

        Sanctum::actingAs($super);

        $response = $this->get('/api/admin/notifications/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }
}