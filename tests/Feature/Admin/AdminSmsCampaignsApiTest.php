<?php

namespace Tests\Feature\Admin;

use App\Jobs\ProcessSmsCampaign;
use App\Jobs\SendCampaignSms;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Models\SmsTemplate;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AdminSmsCampaignsApiTest extends TestCase
{
    use RefreshDatabase;

    private SmsTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'melipayamak.username' => 'test-user',
            'melipayamak.password' => 'test-pass',
            'services.melipayamk.sender' => '50002710008883',
            'sms.campaign.daily_dispatch_limit' => 10,
        ]);

        $this->template = SmsTemplate::create([
            'name' => 'قالب کمپین',
            'slug' => 'campaign-template-380001',
            'melipayamak_body_id' => 380001,
            'preview_text' => 'سلام {0}',
            'parameters' => [
                ['index' => 0, 'label' => 'نام', 'source' => 'user.first_name', 'fallback' => 'کاربر'],
            ],
            'category' => 'marketing',
            'is_active' => true,
        ]);
    }

    public function test_can_create_preview_and_dispatch_campaign(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->createAdminUser());

        $targetUser = User::create([
            'phone_number' => '09120000011',
            'first_name' => 'Ali',
            'last_name' => 'Test',
            'role' => 'parent',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'phone_number' => '09120000012',
            'first_name' => 'Premium',
            'last_name' => 'User',
            'role' => 'parent',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        Subscription::create([
            'user_id' => User::where('phone_number', '09120000012')->value('id'),
            'type' => '1month',
            'status' => 'active',
            'start_date' => now()->subWeek(),
            'end_date' => now()->addMonth(),
            'price' => 50000,
        ]);

        $createResponse = $this->postJson('/api/admin/sms-campaigns', [
            'name' => 'کمپین غیر پریمیوم',
            'sms_template_id' => $this->template->id,
            'audience_type' => 'non_premium',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $campaignId = $createResponse->json('data.id');

        $previewResponse = $this->postJson("/api/admin/sms-campaigns/{$campaignId}/preview");
        $previewResponse->assertOk()
            ->assertJsonPath('data.total_recipients', 1)
            ->assertJsonPath('data.sample_users.0.id', $targetUser->id);

        $dispatchResponse = $this->postJson("/api/admin/sms-campaigns/{$campaignId}/dispatch");
        $dispatchResponse->assertOk()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.total_recipients', 1);

        Queue::assertPushed(ProcessSmsCampaign::class, fn ($job) => $job->campaignId === $campaignId);
    }

    public function test_process_campaign_job_creates_recipients_and_dispatches_send_jobs(): void
    {
        Queue::fake([SendCampaignSms::class]);

        $user = User::create([
            'phone_number' => '09120000021',
            'first_name' => 'Sara',
            'last_name' => 'Test',
            'role' => 'parent',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $campaign = SmsCampaign::create([
            'name' => 'کمپین تست',
            'sms_template_id' => $this->template->id,
            'audience_type' => 'specific_users',
            'audience_filters' => [
                'user_ids' => [$user->id],
                'exclude_admins' => false,
            ],
            'status' => SmsCampaign::STATUS_QUEUED,
            'created_by' => $user->id,
        ]);

        $job = new ProcessSmsCampaign($campaign->id);
        $job->handle(app(\App\Services\SmsCampaignService::class));

        $campaign->refresh();
        $this->assertSame(SmsCampaign::STATUS_PROCESSING, $campaign->status);
        $this->assertSame(1, $campaign->total_recipients);
        $this->assertDatabaseCount('sms_campaign_recipients', 1);

        Queue::assertPushed(SendCampaignSms::class, 1);
    }

    public function test_send_campaign_sms_job_marks_recipient_sent_and_completes_campaign(): void
    {
        $admin = $this->createAdminUser();

        $campaign = SmsCampaign::create([
            'name' => 'کمپین ارسال',
            'sms_template_id' => $this->template->id,
            'audience_type' => 'manual_phones',
            'audience_filters' => [
                'phone_numbers' => ['09120000031'],
            ],
            'status' => SmsCampaign::STATUS_PROCESSING,
            'total_recipients' => 1,
            'created_by' => $admin->id,
            'started_at' => now(),
        ]);

        $recipient = SmsCampaignRecipient::create([
            'sms_campaign_id' => $campaign->id,
            'phone_number' => '09120000031',
            'resolved_parameters' => ['کاربر'],
            'status' => SmsCampaignRecipient::STATUS_PENDING,
        ]);

        $mock = Mockery::mock(SmsService::class);
        $mock->shouldReceive('sendSmsWithTemplate')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'msg-999',
                'sms_log_id' => 1,
            ]);
        $this->app->instance(SmsService::class, $mock);

        $job = new SendCampaignSms($recipient->id);
        $job->handle(
            app(SmsService::class),
            app(\App\Services\SmsParameterResolver::class),
            app(\App\Services\SmsCampaignService::class),
        );

        $recipient->refresh();
        $campaign->refresh();

        $this->assertSame(SmsCampaignRecipient::STATUS_SENT, $recipient->status);
        $this->assertSame(SmsCampaign::STATUS_COMPLETED, $campaign->status);
        $this->assertSame(1, $campaign->sent_count);
    }

    public function test_can_cancel_processing_campaign(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $campaign = SmsCampaign::create([
            'name' => 'کمپین لغو',
            'sms_template_id' => $this->template->id,
            'audience_type' => 'manual_phones',
            'audience_filters' => ['phone_numbers' => ['09120000041']],
            'status' => SmsCampaign::STATUS_PROCESSING,
            'total_recipients' => 1,
            'created_by' => User::create([
                'phone_number' => '09120000040',
                'first_name' => 'Admin',
                'last_name' => 'Two',
                'role' => 'super_admin',
                'status' => 'active',
                'password' => bcrypt('password'),
            ])->id,
            'started_at' => now(),
        ]);

        SmsCampaignRecipient::create([
            'sms_campaign_id' => $campaign->id,
            'phone_number' => '09120000041',
            'resolved_parameters' => ['کاربر'],
            'status' => SmsCampaignRecipient::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/admin/sms-campaigns/{$campaign->id}/cancel");
        $response->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('sms_campaign_recipients', [
            'sms_campaign_id' => $campaign->id,
            'status' => SmsCampaignRecipient::STATUS_SKIPPED,
        ]);
    }

    public function test_cannot_update_non_draft_campaign(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $campaign = SmsCampaign::create([
            'name' => 'کمپین',
            'sms_template_id' => $this->template->id,
            'audience_type' => 'manual_phones',
            'audience_filters' => ['phone_numbers' => ['09120000051']],
            'status' => SmsCampaign::STATUS_COMPLETED,
            'created_by' => User::create([
                'phone_number' => '09120000050',
                'first_name' => 'Admin',
                'last_name' => 'Three',
                'role' => 'super_admin',
                'status' => 'active',
                'password' => bcrypt('password'),
            ])->id,
        ]);

        $this->putJson("/api/admin/sms-campaigns/{$campaign->id}", [
            'name' => 'نام جدید',
        ])->assertStatus(422);
    }

    public function test_can_export_campaign_recipients_csv(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $campaign = SmsCampaign::create([
            'name' => 'کمپین خروجی',
            'sms_template_id' => $this->template->id,
            'audience_type' => 'manual_phones',
            'audience_filters' => ['phone_numbers' => ['09120000061']],
            'status' => SmsCampaign::STATUS_COMPLETED,
            'total_recipients' => 1,
            'created_by' => User::create([
                'phone_number' => '09120000060',
                'first_name' => 'Admin',
                'last_name' => 'Export',
                'role' => 'super_admin',
                'status' => 'active',
                'password' => bcrypt('password'),
            ])->id,
        ]);

        SmsCampaignRecipient::create([
            'sms_campaign_id' => $campaign->id,
            'phone_number' => '09120000061',
            'resolved_parameters' => ['کاربر'],
            'status' => SmsCampaignRecipient::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $response = $this->get("/api/admin/sms-campaigns/{$campaign->id}/recipients/export");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('09120000061', $response->streamedContent());
    }

    private function createAdminUser(): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'phone_number' => '0914'.str_pad((string) $counter, 7, '0', STR_PAD_LEFT),
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'role' => 'super_admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);
    }
}
