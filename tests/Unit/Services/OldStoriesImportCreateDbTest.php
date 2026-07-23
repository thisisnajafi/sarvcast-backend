<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Story;
use App\Models\StoryProductionFile;
use App\Services\OldStoriesImportService;
use App\Services\StoryEditorRepository;
use App\Services\StoryMarkdownService;
use App\Services\StoryProductionImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Exercises --create-db import against a minimal sqlite schema
 * (avoids MySQL-only migrations used by RefreshDatabase).
 */
class OldStoriesImportCreateDbTest extends TestCase
{
    private string $tempRoot;

    private string $storiesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'old_import_cdb_' . uniqid();
        $this->storiesPath = $this->tempRoot . DIRECTORY_SEPARATOR . 'manji-stories';
        mkdir($this->storiesPath, 0755, true);

        config([
            'story_editor.stories_path' => $this->storiesPath,
            'story_editor.default_stories_path' => $this->storiesPath,
            'story_editor.discovery_paths' => [],
        ]);

        $this->createMinimalSchema();
        $this->assertTrue(Schema::hasColumn('episodes', 'script_file_url'), 'episodes.script_file_url missing from test schema');
        \Illuminate\Support\Facades\Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ([
            'story_production_files',
            'story_production_assets',
            'characters',
            'episodes',
            'stories',
            'categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->deleteDirectory($this->tempRoot);
        parent::tearDown();
    }

    public function test_import_package_create_db_sets_age_group_workflow_and_links_files(): void
    {
        Category::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $folderName = '120 - create db sample';
        $packagePath = $this->buildPackage($folderName);

        $service = new OldStoriesImportService(
            new StoryEditorRepository(app(StoryMarkdownService::class)),
            app(StoryProductionImportService::class),
        );

        $manifest = json_decode(
            (string) file_get_contents($packagePath . DIRECTORY_SEPARATOR . 'import_manifest.json'),
            true
        );

        $result = $service->importPackage([
            'folder_name' => $folderName,
            'path' => $packagePath,
            'manifest' => $manifest,
            'story_slug' => app(StoryEditorRepository::class)->storyIdFromFolder($folderName),
        ], $this->storiesPath, [
            'create_db' => true,
            'force' => true,
            'skip_conflicts' => true,
        ]);

        $this->assertSame('imported', $result['status'], 'errors: ' . json_encode($result['errors'] ?? [], JSON_UNESCAPED_UNICODE));
        $this->assertNotEmpty($result['story_id']);

        $story = Story::find($result['story_id']);
        $this->assertNotNull($story);
        $this->assertSame('7-12', $story->age_group);
        $this->assertSame(Story::WORKFLOW_CHARACTERS_MADE, $story->workflow_status);

        $this->assertTrue(
            StoryProductionFile::query()
                ->where('story_id', $story->id)
                ->where('file_type', StoryProductionFile::TYPE_CHARACTERS)
                ->exists()
        );

        $this->assertDirectoryExists($this->storiesPath . DIRECTORY_SEPARATOR . $folderName);
    }

    public function test_dry_run_does_not_write_files_or_db(): void
    {
        Category::query()->create([
            'name' => 'Test',
            'slug' => 'test',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $folderName = '121 - dry run unit';
        $packagePath = $this->buildPackage($folderName);
        $manifest = json_decode(
            (string) file_get_contents($packagePath . DIRECTORY_SEPARATOR . 'import_manifest.json'),
            true
        );

        $service = new OldStoriesImportService(
            new StoryEditorRepository(app(StoryMarkdownService::class)),
            app(StoryProductionImportService::class),
        );

        $result = $service->importPackage([
            'folder_name' => $folderName,
            'path' => $packagePath,
            'manifest' => $manifest,
            'story_slug' => 'dry_run_unit',
        ], $this->storiesPath, [
            'create_db' => true,
            'dry_run' => true,
        ]);

        $this->assertSame('dry_run', $result['status']);
        $this->assertSame(0, Story::query()->count());
        $this->assertDirectoryDoesNotExist($this->storiesPath . DIRECTORY_SEPARATOR . $folderName);
    }

    private function createMinimalSchema(): void
    {
        Schema::dropIfExists('story_production_files');
        Schema::dropIfExists('story_production_assets');
        Schema::dropIfExists('characters');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('stories');
        Schema::dropIfExists('categories');

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('icon_path')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('story_count')->default(0);
            $table->unsignedInteger('total_episodes')->default(0);
            $table->unsignedInteger('total_duration')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->string('age_group', 20)->nullable();
            $table->string('language', 10)->default('fa');
            $table->unsignedInteger('duration')->default(0);
            $table->unsignedInteger('total_episodes')->default(0);
            $table->unsignedInteger('free_episodes')->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_completely_free')->default(false);
            $table->string('status')->default('draft');
            $table->string('workflow_status')->nullable();
            $table->timestamps();
        });

        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('audio_url')->nullable();
            $table->unsignedInteger('duration')->default(0);
            $table->unsignedInteger('episode_number')->default(1);
            $table->string('status')->default('draft');
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_free')->default(true);
            $table->boolean('use_image_timeline')->default(false);
            $table->string('script_file_url')->nullable();
            $table->timestamps();
        });

        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->nullable();
            $table->string('name')->nullable();
            $table->string('name_persian')->nullable();
            $table->string('name_english')->nullable();
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('story_production_files', function (Blueprint $table) {
            $table->id();
            $table->string('story_slug', 191)->index();
            $table->string('episode_slug', 191)->nullable()->index();
            $table->string('file_type');
            $table->string('original_filename', 500)->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->string('source_path', 500)->nullable();
            $table->unsignedBigInteger('story_id')->nullable()->index();
            $table->unsignedBigInteger('episode_id')->nullable()->index();
            $table->unsignedInteger('episode_number')->nullable();
            $table->json('parsed_summary')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('story_production_assets', function (Blueprint $table) {
            $table->id();
            $table->string('story_slug', 191)->index();
            $table->string('episode_slug', 191)->nullable()->index();
            $table->string('asset_type');
            $table->string('asset_key', 191);
            $table->string('name_persian', 500)->nullable();
            $table->string('name_english', 500)->nullable();
            $table->text('prompt')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->unsignedBigInteger('story_id')->nullable()->index();
            $table->unsignedBigInteger('episode_id')->nullable()->index();
            $table->unsignedBigInteger('character_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function buildPackage(string $folderName): string
    {
        $storyDir = $this->tempRoot . DIRECTORY_SEPARATOR . 'pkg_' . uniqid() . DIRECTORY_SEPARATOR . $folderName;
        $episodeDir = $storyDir . DIRECTORY_SEPARATOR . 'episode_1_create_db_sample';
        mkdir($episodeDir, 0755, true);

        file_put_contents(
            $storyDir . DIRECTORY_SEPARATOR . 'characters_and_objects.json',
            json_encode([
                'story_title' => 'نمونه ساخت دیتابیس (Create DB Sample)',
                'target_age' => '7-12',
                'story_summary' => 'خلاصه تست',
                'characters' => [
                    'hero' => [
                        'name_persian' => 'قهرمان',
                        'name_english' => 'Hero',
                    ],
                ],
                'objects' => [],
                'settings' => [],
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        file_put_contents(
            $episodeDir . DIRECTORY_SEPARATOR . 'create_db_sample_story.md',
            "# نمونه\n\n## متن داستان\n\n### صحنه ۱: آغاز\n\n**راوی**: «سلام»\n"
        );
        file_put_contents(
            $episodeDir . DIRECTORY_SEPARATOR . 'create_db_sample_image_prompts.json',
            json_encode([
                'episode_number' => 1,
                'scenes' => [['scene_number' => 1, 'prompt' => 'hero waves']],
            ], JSON_UNESCAPED_UNICODE)
        );

        $manifest = [
            'story_title' => 'create db sample',
            'story_summary' => 'خلاصه تست',
            'total_episodes' => 1,
            'target_age' => '7-12',
            'episodes' => [[
                'episode_number' => 1,
                'episode_slug' => 'create_db_sample',
                'source_folder' => 'episode 1 - قسمت اول',
                'target_folder' => 'episode_1_create_db_sample',
                'has_script' => true,
                'has_prompts' => true,
                'needs_script' => false,
                'title_hint' => 'قسمت اول',
            ]],
        ];
        file_put_contents(
            $storyDir . DIRECTORY_SEPARATOR . 'import_manifest.json',
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        return $storyDir;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
