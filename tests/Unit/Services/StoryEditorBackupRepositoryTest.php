<?php

namespace Tests\Unit\Services;

use App\Services\StoryEditorRepository;
use App\Services\StoryMarkdownService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StoryEditorBackupRepositoryTest extends TestCase
{
    private string $tempRoot;

    private string $storiesPath;

    private string $storySlug;

    private string $episodeSlug;

    private string $episodeDir;

    private string $liveMarkdownPath;

    private StoryEditorRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = storage_path('app/testing-story-editor-backups-unit-'.uniqid());
        $this->storiesPath = $this->tempRoot.DIRECTORY_SEPARATOR.'manji-stories';
        File::ensureDirectoryExists($this->storiesPath);

        config([
            'story_editor.stories_path' => $this->storiesPath,
            'story_editor.default_stories_path' => $this->storiesPath,
            'story_editor.discovery_paths' => [],
        ]);

        $storyDir = $this->storiesPath.DIRECTORY_SEPARATOR.'99 - backup test story';
        $this->episodeDir = $storyDir.DIRECTORY_SEPARATOR.'episode_1_test_episode';
        File::ensureDirectoryExists($this->episodeDir);

        $this->liveMarkdownPath = $this->episodeDir.DIRECTORY_SEPARATOR.'test_episode_story.md';
        file_put_contents($this->liveMarkdownPath, $this->sampleMarkdown('نسخه زنده', 'خط زنده'));

        $this->repository = new StoryEditorRepository(app(StoryMarkdownService::class));
        $this->storySlug = $this->repository->storyIdFromFolder('99 - backup test story');
        $this->episodeSlug = $this->repository->episodeIdFromFolder('episode_1_test_episode');
    }

    protected function tearDown(): void
    {
        if (isset($this->tempRoot) && is_dir($this->tempRoot)) {
            File::deleteDirectory($this->tempRoot);
        }

        parent::tearDown();
    }

    public function test_list_preview_restore_and_delete_backups(): void
    {
        $backupDir = $this->episodeDir.DIRECTORY_SEPARATOR.'_backups';
        File::ensureDirectoryExists($backupDir);
        $backupId = 'test_episode_story.20260101_120000.bak';
        $backupPath = $backupDir.DIRECTORY_SEPARATOR.$backupId;
        file_put_contents($backupPath, $this->sampleMarkdown('نسخه پشتیبان', 'خط پشتیبان'));

        $list = $this->repository->listEpisodeBackups($this->storySlug, $this->episodeSlug);
        $this->assertNotNull($list);
        $this->assertCount(1, $list);
        $this->assertSame($backupId, $list[0]['id']);
        $this->assertSame(1, $list[0]['summary']['scene_count']);

        $preview = $this->repository->getEpisodeBackup($this->storySlug, $this->episodeSlug, $backupId);
        $this->assertNotNull($preview);
        $this->assertStringContainsString('خط پشتیبان', $preview['raw_markdown']);

        $restored = $this->repository->restoreEpisodeBackup($this->storySlug, $this->episodeSlug, $backupId);
        $this->assertNotNull($restored);
        $this->assertStringContainsString('خط پشتیبان', (string) file_get_contents($this->liveMarkdownPath));
        $this->assertFileExists((string) $restored['backup_path']);
        $this->assertSame($backupId, $restored['restored_from']);

        $this->assertTrue($this->repository->deleteEpisodeBackup($this->storySlug, $this->episodeSlug, $backupId));
        $this->assertFileDoesNotExist($backupPath);
    }

    public function test_sanitize_rejects_path_traversal(): void
    {
        $this->assertNull($this->repository->sanitizeBackupId('../secret.bak'));
        $this->assertNull($this->repository->sanitizeBackupId('folder/secret.bak'));
        $this->assertNull($this->repository->sanitizeBackupId('secret.txt'));
        $this->assertSame(
            'test_episode_story.20260101_120000.bak',
            $this->repository->sanitizeBackupId('test_episode_story.20260101_120000.bak')
        );
    }

    public function test_bulk_delete_backups(): void
    {
        $backupDir = $this->episodeDir.DIRECTORY_SEPARATOR.'_backups';
        File::ensureDirectoryExists($backupDir);
        $ids = [
            'test_episode_story.20260101_120000.bak',
            'test_episode_story.20260102_130000.bak',
        ];
        foreach ($ids as $id) {
            file_put_contents($backupDir.DIRECTORY_SEPARATOR.$id, $this->sampleMarkdown('بکاپ', 'متن'));
        }

        $result = $this->repository->deleteEpisodeBackups($this->storySlug, $this->episodeSlug, $ids);
        $this->assertSame($ids, $result['deleted']);
        $this->assertSame([], $result['missing']);
        foreach ($ids as $id) {
            $this->assertFileDoesNotExist($backupDir.DIRECTORY_SEPARATOR.$id);
        }
    }

    private function sampleMarkdown(string $title, string $line): string
    {
        return <<<MD
# {$title}
## قسمت ۱ از ۱

## اطلاعات داستان
- **رده سنی**: ۸–۱۲
- **مدت زمان تخمینی**: ۵ دقیقه
- **دسته‌بندی**: test
- **پیام اصلی**: پیام تست
- **شخصیت‌های این قسمت**: راوی

---

## شخصیت‌های حاضر در این قسمت

- **راوی** (narrator): راوی داستان

---

## متن داستان

### صحنه ۱: شروع
*محیط تست*

**راوی**: «{$line}»

---

## خلاصه قسمت (Episode Summary)
خلاصه تست

## پیام آموزشی (Educational Message)
پیام آموزشی تست

## هوک پایانی (Soft Hook)
**راوی** (آهسته و کنجکاوانه): «ادامه دارد»

---
MD;
    }
}
