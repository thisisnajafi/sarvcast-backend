<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Payment;
use App\Models\PlayHistory;
use App\Models\Story;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * G02 — verifies that revenue/content/system analytics API endpoints
 *        return deterministic data (no rand() drift) seeded from fixtures.
 */
class RealAnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        $this->admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Revenue Analytics
    // ──────────────────────────────────────────────────────────────

    public function test_revenue_overview_returns_real_subscription_counts(): void
    {
        // Seed 3 active subscriptions
        Subscription::factory()->count(3)->create(['status' => 'active']);
        // Seed 1 cancelled
        Subscription::factory()->create(['status' => 'cancelled']);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/revenue-analytics/overview');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'revenue_stats' => ['total_revenue', 'subscription_revenue', 'coin_revenue', 'active_subscriptions', 'total_subscribers', 'average_revenue_per_user', 'monthly_recurring_revenue'],
                    'revenue_trends',
                    'revenue_sources',
                    'date_range',
                ],
            ]);

        $data = $response->json('data.revenue_stats');
        $this->assertEquals(3, $data['active_subscriptions']);
        $this->assertEquals(4, $data['total_subscribers']);
    }

    public function test_revenue_overview_is_deterministic_on_double_call(): void
    {
        Subscription::factory()->count(2)->create(['status' => 'active']);
        Sanctum::actingAs($this->admin);

        $first  = $this->getJson('/api/admin/revenue-analytics/overview')->json('data.revenue_stats');
        $second = $this->getJson('/api/admin/revenue-analytics/overview')->json('data.revenue_stats');

        // Active subscriptions must be identical across calls (no rand() drift)
        $this->assertSame($first['active_subscriptions'], $second['active_subscriptions']);
        $this->assertSame($first['total_subscribers'],    $second['total_subscribers']);
        $this->assertSame($first['total_revenue'],        $second['total_revenue']);
    }

    public function test_revenue_overview_total_revenue_matches_payment_fixtures(): void
    {
        $user = User::factory()->create();
        // 2 completed payments of 100_000 each
        Payment::factory()->count(2)->create([
            'user_id' => $user->id,
            'amount'  => 100000,
            'status'  => 'completed',
        ]);
        // 1 pending payment (should not count)
        Payment::factory()->create([
            'user_id' => $user->id,
            'amount'  => 50000,
            'status'  => 'pending',
        ]);

        Sanctum::actingAs($this->admin);
        $revenue = $this->getJson('/api/admin/revenue-analytics/overview')->json('data.revenue_stats.total_revenue');

        $this->assertEquals(200000, $revenue);
    }

    public function test_revenue_subscriptions_endpoint_returns_success(): void
    {
        Subscription::factory()->count(2)->create();
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/revenue-analytics/subscriptions')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['subscription_stats', 'subscription_plans', 'subscription_trends', 'date_range'],
            ]);
    }

    public function test_revenue_payments_endpoint_returns_success(): void
    {
        $user = User::factory()->create();
        Payment::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'completed', 'amount' => 50000]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/revenue-analytics/payments');
        $response->assertOk()->assertJson(['success' => true]);

        $stats = $response->json('data.payment_stats');
        $this->assertEquals(3, $stats['successful_payments']);
        $this->assertEquals(3, $stats['total_payments']);
    }

    // ──────────────────────────────────────────────────────────────
    // Content Analytics
    // ──────────────────────────────────────────────────────────────

    public function test_content_analytics_overview_returns_real_story_counts(): void
    {
        $cat = Category::create([
            'name'       => 'تست',
            'slug'       => 'test-cat',
            'color'      => '#fff',
            'is_active'  => true,
            'sort_order' => 1,
        ]);
        Story::factory()->count(3)->create(['status' => 'published', 'category_id' => $cat->id]);
        Story::factory()->create(['status' => 'draft', 'category_id' => $cat->id]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/content-analytics/overview');
        $response->assertOk()->assertJson(['success' => true]);

        $stats = $response->json('data.content_stats');
        $this->assertEquals(4, $stats['total_stories']);
        $this->assertEquals(3, $stats['published_content']);
    }

    public function test_content_analytics_overview_is_deterministic(): void
    {
        $cat = Category::create([
            'name' => 'ثبات', 'slug' => 'stable-cat',
            'color' => '#abc', 'is_active' => true, 'sort_order' => 2,
        ]);
        Story::factory()->count(2)->create(['category_id' => $cat->id, 'status' => 'published']);

        Sanctum::actingAs($this->admin);

        $first  = $this->getJson('/api/admin/content-analytics/overview')->json('data.content_stats');
        $second = $this->getJson('/api/admin/content-analytics/overview')->json('data.content_stats');

        $this->assertSame($first['total_stories'],      $second['total_stories']);
        $this->assertSame($first['published_content'],  $second['published_content']);
        $this->assertSame($first['total_listens'],      $second['total_listens']);
    }

    public function test_content_analytics_total_listens_matches_fixture(): void
    {
        $cat = Category::create([
            'name' => 'شنیدنی', 'slug' => 'listen-cat',
            'color' => '#123', 'is_active' => true, 'sort_order' => 3,
        ]);
        $story   = Story::factory()->create(['category_id' => $cat->id, 'status' => 'published']);
        $episode = Episode::create([
            'story_id'       => $story->id,
            'title'          => 'قسمت یک',
            'audio_url'      => 'audio/ep1.mp3',
            'duration'       => 600,
            'episode_number' => 1,
            'status'         => 'published',
        ]);
        $user = User::factory()->create();

        // 5 play history entries today
        for ($i = 0; $i < 5; $i++) {
            PlayHistory::create([
                'user_id'         => $user->id,
                'episode_id'      => $episode->id,
                'story_id'        => $story->id,
                'played_at'       => Carbon::now(),
                'duration_played' => 300,
                'total_duration'  => 600,
                'completed'       => false,
            ]);
        }

        Sanctum::actingAs($this->admin);

        $stats = $this->getJson('/api/admin/content-analytics/overview')->json('data.content_stats');
        $this->assertEquals(5, $stats['total_listens']);
    }

    // ──────────────────────────────────────────────────────────────
    // System Analytics
    // ──────────────────────────────────────────────────────────────

    public function test_system_analytics_overview_returns_success(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/system-analytics/overview')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['system_stats', 'performance_trends', 'resource_usage', 'date_range'],
            ]);
    }

    public function test_system_analytics_overview_is_deterministic(): void
    {
        Sanctum::actingAs($this->admin);

        $first  = $this->getJson('/api/admin/system-analytics/overview')->json('data.system_stats');
        $second = $this->getJson('/api/admin/system-analytics/overview')->json('data.system_stats');

        // Deterministic fields (real system metrics)
        $this->assertSame($first['api_requests'],        $second['api_requests']);
        $this->assertSame($first['database_connections'], $second['database_connections']);
        $this->assertSame($first['error_rate'],           $second['error_rate']);
    }

    public function test_system_analytics_health_returns_success(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/system-analytics/health')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['health_checks', 'system_alerts', 'system_metrics'],
            ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Coin Analytics admin alias (G15)
    // ──────────────────────────────────────────────────────────────

    public function test_coin_analytics_admin_overview_returns_success(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/coin-analytics/overview')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_coin_analytics_admin_overview_is_deterministic(): void
    {
        Sanctum::actingAs($this->admin);

        $first  = $this->getJson('/api/admin/coin-analytics/overview')->json('data');
        $second = $this->getJson('/api/admin/coin-analytics/overview')->json('data');

        $this->assertSame($first['total_transactions'], $second['total_transactions']);
        $this->assertSame($first['total_coins_in_circulation'], $second['total_coins_in_circulation']);
    }
}
