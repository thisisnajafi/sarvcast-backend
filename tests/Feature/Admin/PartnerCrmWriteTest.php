<?php

namespace Tests\Feature\Admin;

use App\Models\AffiliatePartner;
use App\Models\CorporateSponsorship;
use App\Models\InfluencerCampaign;
use App\Models\TeacherAccount;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnerCrmWriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed(RolePermissionSeeder::class);
    }

    // ───────────────────── Teachers ─────────────────────

    public function test_admin_can_create_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $response = $this->postJson('/api/admin/teachers', [
            'user_id' => $user->id,
            'institution_name' => 'دبیرستان آزمایش',
            'institution_type' => 'school',
            'teaching_subject' => 'ریاضی',
            'years_of_experience' => 5,
            'student_count' => 120,
            'status' => 'pending',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.institution_name', 'دبیرستان آزمایش');

        $this->assertDatabaseHas('teacher_accounts', [
            'user_id' => $user->id,
            'institution_name' => 'دبیرستان آزمایش',
        ]);
    }

    public function test_admin_can_update_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->create();
        $teacher = TeacherAccount::create([
            'user_id' => $user->id,
            'institution_name' => 'مدرسه اولیه',
            'institution_type' => 'school',
            'teaching_subject' => 'علوم',
            'years_of_experience' => 2,
            'student_count' => 50,
            'status' => 'pending',
        ]);

        $this->putJson("/api/admin/teachers/{$teacher->id}", [
            'institution_name' => 'مدرسه به‌روز شده',
            'student_count' => 80,
            'status' => 'verified',
        ])->assertOk()
            ->assertJsonPath('data.institution_name', 'مدرسه به‌روز شده')
            ->assertJsonPath('data.student_count', 80);
    }

    // ───────────────────── Influencers ─────────────────────

    public function test_admin_can_create_influencer_campaign(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $partner = AffiliatePartner::factory()->create();

        $response = $this->postJson('/api/admin/influencers', [
            'affiliate_partner_id' => $partner->id,
            'campaign_name' => 'کمپین پاییزی',
            'campaign_description' => 'توضیحات کمپین',
            'campaign_type' => 'story_review',
            'content_type' => 'post',
            'required_posts' => 3,
            'required_stories' => 1,
            'compensation_per_post' => 50000,
            'commission_rate' => 15,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'draft',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.campaign_name', 'کمپین پاییزی')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('influencer_campaigns', [
            'campaign_name' => 'کمپین پاییزی',
            'affiliate_partner_id' => $partner->id,
        ]);
    }

    public function test_admin_can_update_influencer_campaign(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $partner = AffiliatePartner::factory()->create();
        $campaign = InfluencerCampaign::create([
            'affiliate_partner_id' => $partner->id,
            'campaign_name' => 'کمپین قدیمی',
            'campaign_description' => 'توضیحات',
            'campaign_type' => 'brand_partnership',
            'content_type' => 'story',
            'required_posts' => 2,
            'required_stories' => 0,
            'compensation_per_post' => 30000,
            'commission_rate' => 10,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'draft',
        ]);

        $this->putJson("/api/admin/influencers/{$campaign->id}", [
            'campaign_name' => 'کمپین جدید',
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.campaign_name', 'کمپین جدید')
            ->assertJsonPath('data.status', 'active');
    }

    public function test_admin_can_delete_influencer_campaign(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $partner = AffiliatePartner::factory()->create();
        $campaign = InfluencerCampaign::create([
            'affiliate_partner_id' => $partner->id,
            'campaign_name' => 'کمپین حذفی',
            'campaign_description' => 'توضیحات',
            'campaign_type' => 'story_review',
            'content_type' => 'reel',
            'required_posts' => 1,
            'required_stories' => 0,
            'compensation_per_post' => 20000,
            'commission_rate' => 8,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'draft',
        ]);

        $this->deleteJson("/api/admin/influencers/{$campaign->id}")
            ->assertOk();

        $this->assertDatabaseMissing('influencer_campaigns', ['id' => $campaign->id]);
    }

    public function test_influencer_store_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/influencers', [
            'campaign_name' => 'بدون اطلاعات کافی',
        ])->assertStatus(422);
    }

    // ───────────────────── Corporate ─────────────────────

    public function test_admin_can_create_corporate_sponsorship(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $partner = AffiliatePartner::factory()->create();

        $response = $this->postJson('/api/admin/corporate', [
            'affiliate_partner_id' => $partner->id,
            'company_name' => 'شرکت آزمایشی',
            'company_type' => 'tech',
            'industry' => 'technology',
            'company_size' => 'medium',
            'contact_person' => 'علی احمدی',
            'contact_email' => 'ali@test.com',
            'contact_phone' => '09120000001',
            'company_description' => 'شرح شرکت',
            'sponsorship_type' => 'brand_partnership',
            'sponsorship_amount' => 5000000,
            'payment_frequency' => 'monthly',
            'sponsorship_start_date' => now()->toDateString(),
            'sponsorship_end_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.company_name', 'شرکت آزمایشی')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('corporate_sponsorships', [
            'company_name' => 'شرکت آزمایشی',
        ]);
    }

    public function test_admin_can_update_corporate_sponsorship(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $partner = AffiliatePartner::factory()->create();
        $corp = CorporateSponsorship::create([
            'affiliate_partner_id' => $partner->id,
            'company_name' => 'شرکت قدیم',
            'company_type' => 'education',
            'industry' => 'education',
            'company_size' => 'small',
            'contact_person' => 'رضا رضایی',
            'contact_email' => 'reza@test.com',
            'contact_phone' => '09130000001',
            'company_description' => 'توضیح',
            'sponsorship_type' => 'educational_initiative',
            'sponsorship_amount' => 2000000,
            'currency' => 'IRR',
            'payment_frequency' => 'annually',
            'sponsorship_start_date' => now()->toDateString(),
            'sponsorship_end_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);

        $this->putJson("/api/admin/corporate/{$corp->id}", [
            'company_name' => 'شرکت جدید',
            'status' => 'approved',
            'sponsorship_amount' => 3000000,
        ])->assertOk()
            ->assertJsonPath('data.company_name', 'شرکت جدید')
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_admin_can_delete_corporate_sponsorship(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $partner = AffiliatePartner::factory()->create();
        $corp = CorporateSponsorship::create([
            'affiliate_partner_id' => $partner->id,
            'company_name' => 'شرکت حذفی',
            'company_type' => 'media',
            'industry' => 'entertainment',
            'company_size' => 'large',
            'contact_person' => 'مریم موسوی',
            'contact_email' => 'maryam@test.com',
            'contact_phone' => '09140000001',
            'company_description' => 'توضیح',
            'sponsorship_type' => 'content_sponsorship',
            'sponsorship_amount' => 1000000,
            'currency' => 'IRR',
            'payment_frequency' => 'one_time',
            'sponsorship_start_date' => now()->toDateString(),
            'sponsorship_end_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);

        $this->deleteJson("/api/admin/corporate/{$corp->id}")
            ->assertOk();

        $this->assertDatabaseMissing('corporate_sponsorships', ['id' => $corp->id]);
    }

    public function test_corporate_store_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/corporate', [
            'company_name' => 'بدون اطلاعات کافی',
        ])->assertStatus(422);
    }

    // ───────────────────── Non-admin forbidden ─────────────────────

    public function test_non_admin_cannot_create_influencer(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/admin/influencers', [
            'campaign_name' => 'محدود',
        ])->assertForbidden();
    }

    public function test_non_admin_cannot_create_corporate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/admin/corporate', [
            'company_name' => 'محدود',
        ])->assertForbidden();
    }

    public function test_unauthenticated_cannot_write_partners(): void
    {
        $this->postJson('/api/admin/teachers', [])->assertUnauthorized();
        $this->postJson('/api/admin/influencers', [])->assertUnauthorized();
        $this->postJson('/api/admin/corporate', [])->assertUnauthorized();
    }
}
