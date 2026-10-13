<?php

namespace Tests\Feature\Admin;

use App\Models\AffiliatePartner;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Smoke every full-admin sidebar category against real admin APIs.
 * Uses RefreshDatabase + SMS log driver (TestCase). Never hits Melipayamak.
 */
class AdminDashboardPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->superAdmin()->create(['status' => 'active']);
        Sanctum::actingAs($this->admin);
    }

    public function test_sidebar_list_endpoints_return_ok(): void
    {
        $paths = [
            // Users & access
            '/api/admin/users',
            '/api/admin/roles',
            '/api/admin/people',
            '/api/admin/team-members',
            '/api/admin/voice-actors',
            '/api/admin/writers',
            '/api/admin/resumes',
            // Schools / CRM partners
            '/api/admin/schools',
            '/api/admin/teachers',
            '/api/admin/influencers',
            '/api/admin/corporate',
            '/api/admin/v1/school-managers',
            '/api/admin/v1/school-managers/premium-overview',
            // Content
            '/api/admin/stories',
            '/api/admin/episodes',
            '/api/admin/categories',
            '/api/admin/sponsors',
            '/api/admin/media',
            '/api/admin/blog-posts',
            '/api/admin/blog-categories',
            '/api/admin/blog-tags',
            '/api/admin/comment-moderation',
            '/api/admin/content-moderation',
            '/api/admin/quiz',
            '/api/admin/timeline-management',
            '/api/admin/audio-management',
            // Subscriptions & payments
            '/api/admin/subscription-plans',
            '/api/admin/subscriptions',
            '/api/admin/payments',
            '/api/admin/coupons',
            '/api/admin/coins',
            // CRM growth
            '/api/admin/affiliate',
            '/api/admin/referrals',
            '/api/admin/commission-payments',
            // Notifications
            '/api/admin/notifications',
            '/api/admin/sms-templates',
            '/api/admin/sms-campaigns',
            // Reports
            '/api/admin/v1/dashboard/stats',
            '/api/admin/specialized-dashboards/sales',
            '/api/admin/user-analytics/overview',
            '/api/admin/flavor-analytics',
            '/api/admin/activity-logs',
            // Settings / ops
            '/api/admin/seo-overview',
            '/api/admin/seo-redirects',
            '/api/admin/app-settings',
            '/api/admin/app-versions',
            '/api/admin/backup',
            '/api/admin/performance-monitoring',
            '/api/admin/file-upload',
        ];

        $failures = [];
        foreach ($paths as $path) {
            $response = $this->getJson($path);
            if ($response->status() >= 400) {
                $failures[] = sprintf('%s → %d', $path, $response->status());
            }
        }

        $this->assertSame([], $failures, "Sidebar API smoke failures:\n".implode("\n", $failures));
    }

    public function test_crm_school_teacher_affiliate_round_trip(): void
    {
        $school = $this->postJson('/api/admin/schools', [
            'school_name' => 'مدرسه دود تست',
            'location' => 'تهران',
            'status' => 'pending',
            'contact_person' => 'مدیر تست',
            'contact_phone' => '09121112233',
        ])->assertCreated()
            ->json('data');

        $this->getJson('/api/admin/schools/'.$school['id'])
            ->assertOk()
            ->assertJsonPath('data.school_name', 'مدرسه دود تست');

        $this->putJson('/api/admin/schools/'.$school['id'], [
            'status' => 'active',
            'location' => 'کرج',
        ])->assertOk()
            ->assertJsonPath('data.location', 'کرج')
            ->assertJsonPath('data.status', 'active');

        $teacherUser = User::factory()->create([
            'role' => 'basic',
            'status' => 'active',
            'phone_number' => '09123334455',
        ]);

        $teacher = $this->postJson('/api/admin/teachers', [
            'user_id' => $teacherUser->id,
            'institution_name' => 'آموزشگاه تست',
            'institution_type' => 'school',
            'status' => 'pending',
        ])->assertCreated()
            ->json('data');

        $this->putJson('/api/admin/teachers/'.$teacher['id'], [
            'status' => 'active',
            'institution_name' => 'آموزشگاه فعال',
        ])->assertOk()
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonPath('data.institution_name', 'آموزشگاه فعال');

        $this->assertDatabaseHas('teacher_accounts', [
            'id' => $teacher['id'],
            'institution_name' => 'آموزشگاه فعال',
            'status' => 'verified',
        ]);

        $partner = AffiliatePartner::create([
            'name' => 'شریک تست',
            'email' => 'crm-smoke@example.com',
            'phone' => '09124445566',
            'type' => 'school',
            'status' => 'active',
            'commission_rate' => 10,
        ]);

        $this->getJson('/api/admin/affiliate')
            ->assertOk();

        $this->getJson('/api/admin/affiliate/'.$partner->id)
            ->assertOk();

        $this->getJson('/api/admin/influencers')->assertOk();
        $this->getJson('/api/admin/corporate')->assertOk();
        $this->getJson('/api/admin/referrals')->assertOk();
        $this->getJson('/api/admin/commission-payments')->assertOk();

        $this->assertDatabaseHas('school_partnerships', [
            'id' => $school['id'],
            'school_name' => 'مدرسه دود تست',
            'location' => 'کرج',
        ]);
    }
}
