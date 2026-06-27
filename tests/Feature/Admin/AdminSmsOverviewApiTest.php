<?php

namespace Tests\Feature\Admin;

use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSmsOverviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_statistics_returns_combined_metrics(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        SmsTemplate::create([
            'name' => 'قالب فعال',
            'slug' => 'active-template',
            'melipayamak_body_id' => 380001,
            'preview_text' => 'سلام {0}',
            'parameters' => [
                ['index' => 0, 'label' => 'نام', 'source' => 'user.first_name', 'fallback' => 'کاربر'],
            ],
            'category' => 'marketing',
            'is_active' => true,
        ]);

        SmsTemplate::create([
            'name' => 'قالب غیرفعال',
            'slug' => 'inactive-template',
            'melipayamak_body_id' => 380002,
            'preview_text' => 'سلام {0}',
            'parameters' => [
                ['index' => 0, 'label' => 'نام', 'source' => 'user.first_name', 'fallback' => 'کاربر'],
            ],
            'category' => 'marketing',
            'is_active' => false,
        ]);

        $admin = User::where('role', 'super_admin')->first();

        SmsCampaign::create([
            'name' => 'کمپین تست',
            'sms_template_id' => SmsTemplate::first()->id,
            'audience_type' => 'manual_phones',
            'audience_filters' => ['phone_numbers' => ['09120000001']],
            'status' => SmsCampaign::STATUS_COMPLETED,
            'sent_count' => 5,
            'failed_count' => 1,
            'created_by' => $admin->id,
        ]);

        SmsLog::create([
            'phone_number' => '09120000001',
            'message' => 'test',
            'provider' => 'melipayamak',
            'status' => 'delivered',
            'message_id' => 'msg-1',
            'sent_at' => now(),
            'delivered_at' => now(),
        ]);

        SmsLog::create([
            'phone_number' => '09120000002',
            'message' => 'test',
            'provider' => 'melipayamak',
            'status' => 'sent',
            'message_id' => 'msg-2',
            'sent_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/sms/overview/statistics/data');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.templates.total', 2)
            ->assertJsonPath('data.templates.active', 1)
            ->assertJsonPath('data.campaigns.total', 1)
            ->assertJsonPath('data.campaigns.completed', 1)
            ->assertJsonPath('data.campaigns.total_sent', 5)
            ->assertJsonPath('data.logs.today_total', 2)
            ->assertJsonPath('data.logs.today_delivered', 1)
            ->assertJsonPath('data.logs.pending_delivery', 1);
    }

    private function createAdminUser(): User
    {
        return User::create([
            'phone_number' => '09130000001',
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'role' => 'super_admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);
    }
}
