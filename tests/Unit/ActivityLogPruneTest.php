<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_deletes_rows_older_than_retention(): void
    {
        ActivityLog::query()->create([
            'channel' => ActivityLog::CHANNEL_APP,
            'actor_type' => 'user',
            'action' => 'episode.play_started',
            'status' => ActivityLog::STATUS_SUCCESS,
            'occurred_at' => now()->subDays(200),
            'created_at' => now()->subDays(200),
        ]);

        ActivityLog::query()->create([
            'channel' => ActivityLog::CHANNEL_APP,
            'actor_type' => 'user',
            'action' => 'episode.play_started',
            'status' => ActivityLog::STATUS_SUCCESS,
            'occurred_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        $service = app(ActivityLogService::class);
        $deleted = $service->pruneExpiredLogs();

        $this->assertSame(1, $deleted['app'] ?? 0);
        $this->assertDatabaseCount('activity_logs', 1);
    }
}
