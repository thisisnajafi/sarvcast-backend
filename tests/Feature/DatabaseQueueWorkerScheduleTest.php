<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseQueueWorkerScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_queue_worker_is_scheduled_every_minute_in_the_foreground(): void
    {
        $worker = $this->findEventByDescription('Process queued database jobs until the queue is empty');
        $this->assertNotNull($worker);
        $this->assertSame('* * * * *', $worker->expression);
        $this->assertTrue($worker->withoutOverlapping);
        $this->assertSame(10, $worker->expiresAt);
        $this->assertFalse($worker->runInBackground);
        $this->assertStringContainsString('queue:work', (string) $worker->command);
        $this->assertStringContainsString('database', (string) $worker->command);
        $this->assertStringContainsString('--stop-when-empty', (string) $worker->command);
        $this->assertStringContainsString('--tries=3', (string) $worker->command);
        $this->assertStringContainsString('--max-time=50', (string) $worker->command);
    }

    public function test_existing_schedules_remain_registered(): void
    {
        $expectedDescriptions = [
            'Create daily full system backup',
            'Create weekly database backup',
            'Create monthly files backup',
            'Send daily sales summary push to admins',
            'Send subscription expiring and expired push notifications',
            'Close expired school premium windows and mark ended free grants',
            'Delete expired activity log rows by channel retention',
            'Update SMS log delivery status from Melipayamak',
        ];
        foreach ($expectedDescriptions as $description) {
            $this->assertNotNull(
                $this->findEventByDescription($description),
                'Missing schedule: '.$description
            );
        }
    }

    public function test_jobs_and_failed_jobs_tables_exist_after_migrations(): void
    {
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
        $this->assertTrue(Schema::hasColumns('jobs', [
            'id',
            'queue',
            'payload',
            'attempts',
            'reserved_at',
            'available_at',
            'created_at',
        ]));
        $this->assertTrue(Schema::hasColumns('failed_jobs', [
            'id',
            'uuid',
            'connection',
            'queue',
            'payload',
            'exception',
            'failed_at',
        ]));
    }

    private function findEventByDescription(string $description): ?Event
    {
        foreach (app(Schedule::class)->events() as $event) {
            if ($event->description === $description) {
                return $event;
            }
        }

        return null;
    }
}
