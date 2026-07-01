<?php

namespace Tests\Unit;

use App\Jobs\PollSmsLogDeliveryJob;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Database\Seeders\SmsSystemTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SmsDeliveryPollingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'melipayamak.username' => 'test-user',
            'melipayamak.password' => 'test-pass',
            'sms.delivery.polling_enabled' => true,
            'sms.delivery.initial_delay_seconds' => 30,
        ]);
    }

    public function test_poll_sms_log_delivery_marks_log_as_delivered(): void
    {
        $log = SmsLog::create([
            'phone_number' => '09120000001',
            'message' => 'test',
            'provider' => 'melipayamak',
            'status' => 'sent',
            'message_id' => '12345',
            'sent_at' => now(),
        ]);

        $service = Mockery::mock(SmsService::class)->makePartial();
        $service->shouldReceive('checkDeliveryStatus')
            ->once()
            ->with('12345')
            ->andReturn([
                'success' => true,
                'delivered' => true,
                'status' => 1,
                'response' => (object) ['Value' => 1],
            ]);

        $this->app->instance(SmsService::class, $service);

        $delivered = $service->pollSmsLogDelivery($log->fresh());

        $this->assertTrue($delivered);
        $this->assertDatabaseHas('sms_logs', [
            'id' => $log->id,
            'status' => 'delivered',
        ]);
        $this->assertNotNull($log->fresh()->delivered_at);
    }

    public function test_schedule_delivery_poll_dispatches_job_when_enabled(): void
    {
        Queue::fake();

        app(SmsService::class)->scheduleDeliveryPoll(42);

        Queue::assertPushed(PollSmsLogDeliveryJob::class, function (PollSmsLogDeliveryJob $job) {
            return $job->smsLogId === 42;
        });
    }

    public function test_resolve_verification_template_id_uses_database_template(): void
    {
        SmsTemplate::create([
            'name' => 'کد تایید',
            'slug' => 'verification-otp',
            'melipayamak_body_id' => 999888,
            'preview_text' => 'کد {0}',
            'parameters' => [
                ['index' => 0, 'label' => 'کد', 'source' => 'custom', 'fallback' => ''],
            ],
            'category' => 'system',
            'is_active' => true,
        ]);

        config(['services.melipayamk.templates.verification' => 485459]);

        $this->assertSame(999888, app(SmsService::class)->resolveVerificationTemplateId());
    }

    public function test_system_template_seeder_creates_verification_otp_template(): void
    {
        config(['services.melipayamk.templates.verification' => 485459]);

        $this->seed(SmsSystemTemplateSeeder::class);

        $this->assertDatabaseHas('sms_templates', [
            'slug' => 'verification-otp',
            'melipayamak_body_id' => 485459,
            'category' => 'system',
            'is_active' => true,
        ]);
    }
}
