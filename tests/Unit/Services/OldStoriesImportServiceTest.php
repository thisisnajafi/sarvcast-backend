<?php

namespace Tests\Unit\Services;

use App\Services\OldStoriesImportService;
use App\Services\StoryEditorRepository;
use App\Services\StoryMarkdownService;
use App\Services\StoryProductionImportService;
use Tests\TestCase;

class OldStoriesImportServiceTest extends TestCase
{
    public function test_has_id_conflict_when_same_numeric_prefix_differs(): void
    {
        $service = new OldStoriesImportService(
            new StoryEditorRepository(app(StoryMarkdownService::class)),
            $this->createMock(StoryProductionImportService::class),
        );

        $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'old_stories_import_test_' . uniqid();
        mkdir($dest, 0755, true);
        mkdir($dest . DIRECTORY_SEPARATOR . '1 - fereydon and zahhak', 0755, true);

        $this->assertTrue($service->hasIdConflict('1 - pashmalo', $dest));

        @rmdir($dest . DIRECTORY_SEPARATOR . '1 - fereydon and zahhak');
        @rmdir($dest);
    }

    public function test_no_conflict_for_offset_ids(): void
    {
        $service = new OldStoriesImportService(
            new StoryEditorRepository(app(StoryMarkdownService::class)),
            $this->createMock(StoryProductionImportService::class),
        );

        $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'old_stories_import_test_' . uniqid();
        mkdir($dest, 0755, true);
        mkdir($dest . DIRECTORY_SEPARATOR . '1 - fereydon and zahhak', 0755, true);

        $this->assertFalse($service->hasIdConflict('111 - ferydon and zahhak', $dest));

        @rmdir($dest . DIRECTORY_SEPARATOR . '1 - fereydon and zahhak');
        @rmdir($dest);
    }
}
