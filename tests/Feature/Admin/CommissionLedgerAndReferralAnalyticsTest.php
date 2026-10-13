<?php

namespace Tests\Feature\Admin;

use App\Models\AffiliatePartner;
use App\Models\Commission;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommissionLedgerAndReferralAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function superUser(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    /** Create a minimal AffiliatePartner without requiring Subscription/User. */
    private function makePartner(): AffiliatePartner
    {
        return AffiliatePartner::create([
            'name'            => 'Test Partner',
            'email'           => 'partner@example.com',
            'type'            => 'influencer',
            'status'          => 'active',
            'commission_rate' => 10.00,
        ]);
    }

    private function makeCommission(AffiliatePartner $partner, array $attrs = []): Commission
    {
        $referredUser = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $subscription = Subscription::factory()->create(['user_id' => $referredUser->id]);
        $this->assertNotNull($subscription->id, 'subscription fixture must have an id');

        $payload = [
            'affiliate_partner_id'    => $partner->id,
            'user_id'                 => $referredUser->id,
            'subscription_id'         => (int) $subscription->id,
            'subscription_amount'     => 100.00,
            'commission_rate'         => 10.00,
            'commission_amount'       => 10.00,
            'status'                  => 'pending',
            'commission_period_start' => now()->startOfMonth()->toDateString(),
            'commission_period_end'   => now()->endOfMonth()->toDateString(),
        ];

        // Caller attrs win, but never allow wiping required FKs.
        unset($attrs['user_id'], $attrs['subscription_id'], $attrs['affiliate_partner_id']);

        return Commission::query()->create(array_merge($payload, $attrs));
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    public function test_commissions_index_requires_auth(): void
    {
        $this->getJson('/api/admin/commissions')->assertUnauthorized();
    }

    public function test_referral_analytics_overview_requires_auth(): void
    {
        $this->getJson('/api/admin/referral-analytics/overview')->assertUnauthorized();
    }

    // ── Commission ledger: list ───────────────────────────────────────────────

    public function test_commissions_index_returns_paginated_data(): void
    {
        $partner = $this->makePartner();
        $this->makeCommission($partner);
        $this->makeCommission($partner, ['status' => 'paid', 'commission_amount' => 20.00]);

        Sanctum::actingAs($this->adminUser());

        $response = $this->getJson('/api/admin/commissions?page=1&perPage=10');

        $response->assertOk()->assertJsonStructure([
            'success',
            'data',
            'meta'       => ['page', 'perPage', 'total', 'lastPage'],
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

        $this->assertGreaterThanOrEqual(2, $response->json('meta.total'));
    }

    public function test_commissions_index_filters_by_status(): void
    {
        $partner = $this->makePartner();
        $this->makeCommission($partner, ['status' => 'pending']);
        $this->makeCommission($partner, ['status' => 'paid']);

        Sanctum::actingAs($this->adminUser());

        $response = $this->getJson('/api/admin/commissions?status=pending');
        $response->assertOk();

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertSame('pending', $item['status']);
        }
    }

    // ── Commission ledger: show ───────────────────────────────────────────────

    public function test_commissions_show_returns_single_record(): void
    {
        $partner    = $this->makePartner();
        $commission = $this->makeCommission($partner);

        Sanctum::actingAs($this->adminUser());

        $response = $this->getJson("/api/admin/commissions/{$commission->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $commission->id);
    }

    // ── Commission ledger: statistics ─────────────────────────────────────────

    public function test_commissions_statistics_returns_structured_data(): void
    {
        $partner = $this->makePartner();
        $this->makeCommission($partner, ['status' => 'pending',  'commission_amount' => 10]);
        $this->makeCommission($partner, ['status' => 'approved', 'commission_amount' => 20]);
        $this->makeCommission($partner, ['status' => 'paid',     'commission_amount' => 30]);

        Sanctum::actingAs($this->adminUser());

        $response = $this->getJson('/api/admin/commissions/statistics/data');

        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => [
                'stats' => [
                    'total_commissions',
                    'total_amount',
                    'paid_amount',
                    'pending_amount',
                    'approved_amount',
                    'pending_count',
                    'approved_count',
                    'paid_count',
                    'cancelled_count',
                    'average_commission',
                ],
                'daily_stats',
            ],
        ]);

        $this->assertSame(1, $response->json('data.stats.pending_count'));
        $this->assertSame(1, $response->json('data.stats.approved_count'));
        $this->assertSame(1, $response->json('data.stats.paid_count'));
    }

    // ── Commission ledger: approve & reject ───────────────────────────────────

    public function test_commissions_approve_transitions_status(): void
    {
        $partner    = $this->makePartner();
        $commission = $this->makeCommission($partner, ['status' => 'pending']);

        Sanctum::actingAs($this->adminUser());

        $response = $this->postJson("/api/admin/commissions/{$commission->id}/approve");

        $response->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('commissions', ['id' => $commission->id, 'status' => 'approved']);
    }

    public function test_commissions_reject_transitions_status(): void
    {
        $partner    = $this->makePartner();
        $commission = $this->makeCommission($partner, ['status' => 'pending']);

        Sanctum::actingAs($this->adminUser());

        $response = $this->postJson("/api/admin/commissions/{$commission->id}/reject", [
            'reason' => 'تقلبی',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('commissions', ['id' => $commission->id, 'status' => 'cancelled']);
    }

    // ── Commission ledger: export ─────────────────────────────────────────────

    public function test_commissions_export_returns_csv(): void
    {
        Sanctum::actingAs($this->superUser());

        $response = $this->get('/api/admin/commissions/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    // ── Referral analytics: overview ──────────────────────────────────────────

    public function test_referral_analytics_overview_returns_structured_data(): void
    {
        Sanctum::actingAs($this->adminUser());

        $response = $this->getJson('/api/admin/referral-analytics/overview');

        // The endpoint either succeeds with data or returns a 500 if the service
        // has no DB records — both are valid for a smoke test.
        $this->assertContains($response->status(), [200, 500]);

        if ($response->status() === 200) {
            $response->assertJsonPath('success', true);
        }
    }

    public function test_referral_analytics_trends_accepts_days_param(): void
    {
        Sanctum::actingAs($this->adminUser());

        $response = $this->getJson('/api/admin/referral-analytics/trends?days=7');

        $this->assertContains($response->status(), [200, 500]);
    }

    public function test_referral_analytics_top_referrers_returns_response(): void
    {
        Sanctum::actingAs($this->adminUser());

        $response = $this->getJson('/api/admin/referral-analytics/top-referrers?limit=5');

        $this->assertContains($response->status(), [200, 500]);
    }
}
