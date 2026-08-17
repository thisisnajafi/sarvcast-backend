<?php

namespace Tests\Unit\Services;

use App\Models\Story;
use App\Models\User;
use App\Services\ContributorStoryAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContributorStoryAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContributorStoryAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContributorStoryAccessService::class);
    }

    public function test_normalize_title_strips_english_parenthetical(): void
    {
        $this->assertSame(
            'گاوی و دوستان مزرعه',
            $this->service->normalizeTitle('گاوی و دوستان مزرعه (Gavi and the Happy Farm)'),
        );
    }

    public function test_titles_match_across_editor_and_db_variants(): void
    {
        $this->assertTrue($this->service->titlesMatch(
            'گاوی و دوستان مزرعه (Gavi and the Happy Farm)',
            'گاوی و دوستان مزرعه',
        ));

        $this->assertTrue($this->service->titlesMatch(
            '2 - گاوی و دوستان مزرعه',
            'گاوی و دوستان مزرعه',
        ));

        $this->assertFalse($this->service->titlesMatch('گاوی', 'سیاوش'));
    }

    public function test_head_writer_is_not_full_admin_but_may_access_panel(): void
    {
        $head = User::factory()->headWriter()->create();

        $this->assertFalse($this->service->isFullAdmin($head));
        $this->assertTrue($this->service->isHeadWriter($head));
        $this->assertTrue($this->service->mayAccessAdminPanel($head));
        $this->assertTrue($this->service->canViewAllStories($head));
        $this->assertTrue($this->service->canAssignStoryWriter($head));
        $this->assertFalse($this->service->isContributor($head));
    }

    public function test_writer_may_access_panel_parent_author_may_not(): void
    {
        $writer = User::factory()->writer()->create();
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        $story = $this->makeStory(['author_id' => $parent->id]);

        $this->assertTrue($this->service->mayAccessAdminPanel($writer));
        $this->assertFalse($this->service->mayAccessAdminPanel($parent));
        $this->assertTrue($this->service->canViewStory($parent, $story));
        $this->assertFalse($this->service->canViewAllStories($writer));
    }

    public function test_head_writer_can_view_and_edit_unauthored_story(): void
    {
        $head = User::factory()->headWriter()->create();
        $story = $this->makeStory();

        $this->assertTrue($this->service->canViewStory($head, $story));
        $this->assertTrue($this->service->canEditScript($head, $story));
        $this->assertFalse($this->service->canAccessPackage($head));
    }

    public function test_writer_only_sees_authored_stories(): void
    {
        $writer = User::factory()->writer()->create();
        $other = User::factory()->writer()->create();
        $mine = $this->makeStory(['author_id' => $writer->id]);
        $theirs = $this->makeStory(['author_id' => $other->id]);

        $this->assertTrue($this->service->canViewStory($writer, $mine));
        $this->assertTrue($this->service->canEditScript($writer, $mine));
        $this->assertFalse($this->service->canViewStory($writer, $theirs));
        $this->assertFalse($this->service->canEditScript($writer, $theirs));
    }

    public function test_access_payload_flags_for_head_writer(): void
    {
        $head = User::factory()->headWriter()->create();
        $payload = $this->service->accessPayload($head);

        $this->assertFalse($payload['is_full_admin']);
        $this->assertTrue($payload['is_head_writer']);
        $this->assertFalse($payload['is_writer']);
        $this->assertFalse($payload['is_contributor']);
        $this->assertTrue($payload['can_view_all_stories']);
        $this->assertTrue($payload['can_edit_all_scripts']);
        $this->assertTrue($payload['can_assign_story_writers']);
        $this->assertFalse($payload['can_access_story_package']);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function makeStory(array $attrs = []): Story
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category '.uniqid(),
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Story::query()->create(array_merge([
            'title' => 'داستان تست',
            'description' => 'توضیحات داستان تست',
            'image_url' => 'https://example.com/cover.webp',
            'category_id' => $categoryId,
            'age_group' => '7+',
            'language' => 'fa',
            'duration' => 10,
            'status' => 'draft',
        ], $attrs));
    }
}
