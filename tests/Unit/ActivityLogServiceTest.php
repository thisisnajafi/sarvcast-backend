<?php

namespace Tests\Unit;

use App\DataTransferObjects\ActivityLogEntry;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_persists_redacted_properties(): void
    {
        $service = app(ActivityLogService::class);

        $service->record(new ActivityLogEntry(
            channel: ActivityLog::CHANNEL_ADMIN,
            actorUserId: null,
            actorType: 'admin',
            action: 'updated',
            properties: [
                'password' => 'secret123',
                'title' => 'Test Story',
            ],
        ));

        $log = ActivityLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame('[REDACTED]', $log->properties['password']);
        $this->assertSame('Test Story', $log->properties['title']);
    }

    public function test_diff_returns_only_changed_fields(): void
    {
        $service = app(ActivityLogService::class);

        [$old, $new] = $service->diff(
            ['title' => 'A', 'status' => 'draft', 'token' => 'abc'],
            ['title' => 'B', 'status' => 'draft', 'token' => 'xyz'],
        );

        $this->assertSame(['title' => 'A', 'token' => '[REDACTED]'], $old);
        $this->assertSame(['title' => 'B', 'token' => '[REDACTED]'], $new);
    }
}
