<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModelActivityLogObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_story_update_records_field_level_diff_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        Sanctum::actingAs($admin);

        $story = Story::factory()->create([
            'title' => 'عنوان قدیم',
            'status' => 'draft',
        ]);

        $story->update(['title' => 'عنوان جدید']);

        $log = ActivityLog::query()
            ->where('action', 'story.updated')
            ->where('subject_type', 'story')
            ->where('subject_id', (string) $story->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('عنوان قدیم', $log->old_values['title'] ?? null);
        $this->assertSame('عنوان جدید', $log->new_values['title'] ?? null);
        $this->assertSame($admin->id, $log->actor_user_id);
    }

    public function test_story_publish_records_published_action(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        Sanctum::actingAs($admin);

        $story = Story::factory()->create(['status' => 'draft']);
        $story->update(['status' => 'published']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'story.published',
            'subject_type' => 'story',
            'subject_id' => (string) $story->id,
        ]);
    }

    public function test_model_audit_skipped_without_admin_actor(): void
    {
        $story = Story::factory()->create(['title' => 'بدون ادمین']);
        $beforeCount = ActivityLog::query()->count();

        $story->update(['title' => 'تغییر بدون ادمین']);

        $this->assertSame($beforeCount, ActivityLog::query()->count());
    }
}
