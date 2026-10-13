<?php

namespace Tests\Feature\Admin;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'   => 'admin',
            'email'  => 'support-admin@test.com',
            'status' => 'active',
        ]);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'role'   => 'user',
            'email'  => 'support-user@test.com',
            'status' => 'active',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // index
    // ──────────────────────────────────────────────────────────

    public function test_admin_can_list_tickets(): void
    {
        $admin = $this->makeAdmin();
        SupportTicket::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/support-tickets');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['page', 'perPage', 'total', 'lastPage'],
            ]);
    }

    public function test_non_admin_gets_403_on_list(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/support-tickets')->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────
    // store
    // ──────────────────────────────────────────────────────────

    public function test_admin_can_create_ticket(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/support-tickets', [
            'user_id'  => $user->id,
            'subject'  => 'مشکل پرداخت',
            'priority' => 'high',
            'body'     => 'متن اولیه تیکت',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.subject', 'مشکل پرداخت')
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('support_tickets', ['subject' => 'مشکل پرداخت']);
        $this->assertDatabaseHas('support_ticket_messages', ['body' => 'متن اولیه تیکت', 'is_staff' => true]);
    }

    // ──────────────────────────────────────────────────────────
    // show
    // ──────────────────────────────────────────────────────────

    public function test_admin_can_view_ticket(): void
    {
        $admin  = $this->makeAdmin();
        $ticket = SupportTicket::factory()->create(['subject' => 'بررسی موجودی']);

        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/support-tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.subject', 'بررسی موجودی');
    }

    // ──────────────────────────────────────────────────────────
    // reply
    // ──────────────────────────────────────────────────────────

    public function test_admin_can_reply_to_ticket(): void
    {
        $admin  = $this->makeAdmin();
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        Sanctum::actingAs($admin);

        // FCM may be called by InAppNotificationService — Http::fake handles it.
        $response = $this->postJson("/api/admin/support-tickets/{$ticket->id}/reply", [
            'body' => 'پاسخ تیم پشتیبانی به شما',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.ticket.status', 'pending');

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'body'              => 'پاسخ تیم پشتیبانی به شما',
            'is_staff'          => true,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // close
    // ──────────────────────────────────────────────────────────

    public function test_admin_can_close_ticket(): void
    {
        $admin  = $this->makeAdmin();
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/support-tickets/{$ticket->id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('support_tickets', [
            'id'     => $ticket->id,
            'status' => 'closed',
        ]);
        $this->assertNotNull(SupportTicket::find($ticket->id)?->closed_at);
    }

    // ──────────────────────────────────────────────────────────
    // update
    // ──────────────────────────────────────────────────────────

    public function test_admin_can_update_ticket_priority(): void
    {
        $admin  = $this->makeAdmin();
        $ticket = SupportTicket::factory()->create(['priority' => 'normal']);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/support-tickets/{$ticket->id}", ['priority' => 'urgent'])
            ->assertOk()
            ->assertJsonPath('data.priority', 'urgent');
    }

    // ──────────────────────────────────────────────────────────
    // statistics
    // ──────────────────────────────────────────────────────────

    public function test_statistics_endpoint_returns_counts(): void
    {
        $admin = $this->makeAdmin();
        SupportTicket::factory()->count(2)->create(['status' => 'open']);
        SupportTicket::factory()->count(1)->create(['status' => 'closed']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/support-tickets/statistics/data');

        $response->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.open', 2)
            ->assertJsonPath('data.closed', 1);
    }

    // ──────────────────────────────────────────────────────────
    // filters
    // ──────────────────────────────────────────────────────────

    public function test_filter_by_status_works(): void
    {
        $admin = $this->makeAdmin();
        SupportTicket::factory()->create(['status' => 'open',   'subject' => 'باز']);
        SupportTicket::factory()->create(['status' => 'closed', 'subject' => 'بسته']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/support-tickets?status=open');

        $response->assertOk();

        $subjects = collect($response->json('data'))->pluck('subject');
        $this->assertContains('باز', $subjects);
        $this->assertNotContains('بسته', $subjects);
    }

    public function test_search_by_subject_works(): void
    {
        $admin = $this->makeAdmin();
        SupportTicket::factory()->create(['subject' => 'مشکل پرداخت']);
        SupportTicket::factory()->create(['subject' => 'بررسی اشتراک']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/support-tickets?search=پرداخت');

        $response->assertOk();

        $subjects = collect($response->json('data'))->pluck('subject');
        $this->assertContains('مشکل پرداخت', $subjects);
        $this->assertNotContains('بررسی اشتراک', $subjects);
    }

    // ──────────────────────────────────────────────────────────
    // bulk-action
    // ──────────────────────────────────────────────────────────

    public function test_bulk_close_action_works(): void
    {
        $admin   = $this->makeAdmin();
        $ticket1 = SupportTicket::factory()->create(['status' => 'open']);
        $ticket2 = SupportTicket::factory()->create(['status' => 'pending']);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/support-tickets/bulk-action', [
            'action' => 'close',
            'ids'    => [$ticket1->id, $ticket2->id],
        ])->assertOk()
            ->assertJsonPath('data.affected', 2);

        $this->assertDatabaseHas('support_tickets', ['id' => $ticket1->id, 'status' => 'closed']);
        $this->assertDatabaseHas('support_tickets', ['id' => $ticket2->id, 'status' => 'closed']);
    }
}
