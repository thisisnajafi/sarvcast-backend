<?php

namespace Tests\Feature\Admin;

use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaLibraryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_media_library_lists_assets(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        MediaAsset::create([
            'uuid' => fake()->uuid(),
            'disk' => 'public',
            'path' => 'media/2026/06/test.jpg',
            'url' => 'https://example.test/storage/media/2026/06/test.jpg',
            'original_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'media_type' => MediaAsset::TYPE_IMAGE,
            'size_bytes' => 1024,
            'folder' => 'general',
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);

        $response = $this->getJson('/api/admin/media');

        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'uuid', 'url', 'thumbnail_url', 'original_name', 'folder'],
            ],
            'meta',
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_media_library_uploads_image(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('cover.jpg', 800, 600);

        $response = $this->postJson('/api/admin/media', [
            'files' => [$file],
            'folder' => 'stories',
            'title' => 'کاور تست',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('media_assets', [
            'folder' => 'stories',
            'title' => 'کاور تست',
            'media_type' => MediaAsset::TYPE_IMAGE,
            'uploaded_by' => $admin->id,
            'extension' => 'webp',
            'mime_type' => 'image/webp',
        ]);

        $asset = MediaAsset::query()->where('title', 'کاور تست')->first();
        $this->assertNotNull($asset);
        $this->assertStringEndsWith('.webp', (string) $asset->path);
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_media_library_keeps_gif_without_webp_conversion(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('anim.gif', 120, 120);

        $response = $this->postJson('/api/admin/media', [
            'files' => [$file],
            'folder' => 'general',
            'title' => 'گیف تست',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('media_assets', [
            'title' => 'گیف تست',
            'extension' => 'gif',
        ]);
    }

    public function test_media_library_can_disable_webp_conversion(): void
    {
        config(['media_library.convert_to_webp' => false]);

        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('cover.png', 400, 300);

        $response = $this->postJson('/api/admin/media', [
            'files' => [$file],
            'folder' => 'stories',
            'title' => 'بدون تبدیل',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('media_assets', [
            'title' => 'بدون تبدیل',
            'extension' => 'png',
        ]);
    }

    public function test_media_library_uploads_mp3_audio(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->create('episode.mp3', 512, 'audio/mpeg');

        $response = $this->postJson('/api/admin/media', [
            'files' => [$file],
            'folder' => 'episodes',
            'title' => 'اپیزود تست',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('media_assets', [
            'folder' => 'episodes',
            'title' => 'اپیزود تست',
            'media_type' => MediaAsset::TYPE_AUDIO,
            'mime_type' => 'audio/mpeg',
            'extension' => 'mp3',
            'uploaded_by' => $admin->id,
        ]);
    }

    public function test_media_library_filters_by_media_type_audio(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        MediaAsset::create([
            'uuid' => fake()->uuid(),
            'disk' => 'public',
            'path' => 'media/2026/06/audio.mp3',
            'url' => 'https://example.test/storage/media/2026/06/audio.mp3',
            'original_name' => 'audio.mp3',
            'mime_type' => 'audio/mpeg',
            'extension' => 'mp3',
            'media_type' => MediaAsset::TYPE_AUDIO,
            'size_bytes' => 2048,
            'folder' => 'episodes',
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);

        MediaAsset::create([
            'uuid' => fake()->uuid(),
            'disk' => 'public',
            'path' => 'media/2026/06/photo.jpg',
            'url' => 'https://example.test/storage/media/2026/06/photo.jpg',
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'media_type' => MediaAsset::TYPE_IMAGE,
            'size_bytes' => 1024,
            'folder' => 'general',
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);

        $audioResponse = $this->getJson('/api/admin/media?media_type=audio');
        $audioResponse->assertOk()->assertJsonPath('success', true);
        $this->assertCount(1, $audioResponse->json('data'));
        $this->assertSame('audio', $audioResponse->json('data.0.media_type'));

        $imageResponse = $this->getJson('/api/admin/media?media_type=image');
        $imageResponse->assertOk()->assertJsonPath('success', true);
        $this->assertCount(1, $imageResponse->json('data'));
        $this->assertSame('image', $imageResponse->json('data.0.media_type'));
    }

    public function test_media_library_archives_asset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $asset = MediaAsset::create([
            'uuid' => fake()->uuid(),
            'disk' => 'public',
            'path' => 'media/2026/06/remove.jpg',
            'url' => 'https://example.test/storage/media/2026/06/remove.jpg',
            'original_name' => 'remove.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 512,
            'folder' => 'general',
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);

        $response = $this->deleteJson("/api/admin/media/{$asset->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('media_assets', [
            'id' => $asset->id,
            'status' => MediaAsset::STATUS_ARCHIVED,
        ]);
    }

    public function test_media_library_blocks_delete_when_in_use(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $asset = MediaAsset::create([
            'uuid' => fake()->uuid(),
            'disk' => 'public',
            'path' => 'media/2026/06/in-use.jpg',
            'url' => 'https://example.test/storage/media/2026/06/in-use.jpg',
            'original_name' => 'in-use.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 512,
            'folder' => 'production',
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);

        MediaUsage::create([
            'media_asset_id' => $asset->id,
            'usable_type' => MediaAsset::class,
            'usable_id' => $asset->id,
            'field' => 'image_url',
            'created_at' => now(),
        ]);

        $response = $this->deleteJson("/api/admin/media/{$asset->id}");

        $response->assertStatus(422)->assertJsonPath('error', 'IN_USE');
        $this->assertDatabaseHas('media_assets', [
            'id' => $asset->id,
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);
    }

    public function test_media_library_imports_legacy_public_images(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $legacyDir = public_path('images/categories');
        if (! is_dir($legacyDir)) {
            mkdir($legacyDir, 0755, true);
        }

        $legacyFile = $legacyDir . '/test-legacy-' . uniqid() . '.jpg';
        file_put_contents($legacyFile, UploadedFile::fake()->image('legacy.jpg')->getContent());

        $preview = $this->getJson('/api/admin/media/import-legacy/preview');
        $preview->assertOk()->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(1, $preview->json('data.pending'));

        $import = $this->postJson('/api/admin/media/import-legacy', ['limit' => 10]);
        $import->assertOk()->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(1, $import->json('data.imported'));

        @unlink($legacyFile);
    }

    public function test_media_library_imports_story_editor_markdown(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $storiesRoot = storage_path('app/testing-manji-stories');
        $episodeDir = $storiesRoot . '/1-test-story/episode_1';
        if (! is_dir($episodeDir)) {
            mkdir($episodeDir, 0755, true);
        }

        $mdFile = $episodeDir . '/episode_1_story.md';
        file_put_contents($mdFile, "# اپیزود تست\n");

        config(['story_editor.stories_path' => $storiesRoot]);

        $import = $this->postJson('/api/admin/media/import-legacy', ['limit' => 50]);
        $import->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('media_assets', [
            'original_name' => 'episode_1_story.md',
            'media_type' => MediaAsset::TYPE_DOCUMENT,
            'disk' => \App\Services\MediaLegacyImportService::STORY_EDITOR_DISK,
            'folder' => 'production',
        ]);

        @unlink($mdFile);
        @rmdir($episodeDir);
        @rmdir($storiesRoot . '/1-test-story');
        @rmdir($storiesRoot);
    }

    public function test_media_library_imports_timeline_images_into_timeline_folder(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $timelineDir = public_path('images/episodes/timeline');
        if (! is_dir($timelineDir)) {
            mkdir($timelineDir, 0755, true);
        }

        $legacyFile = $timelineDir . '/timeline-' . uniqid() . '.jpg';
        file_put_contents($legacyFile, UploadedFile::fake()->image('frame.jpg')->getContent());

        $import = $this->postJson('/api/admin/media/import-legacy', ['limit' => 20]);
        $import->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('media_assets', [
            'folder' => 'timeline',
            'media_type' => MediaAsset::TYPE_IMAGE,
        ]);

        @unlink($legacyFile);
    }

    public function test_media_library_filters_documents(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        MediaAsset::create([
            'uuid' => fake()->uuid(),
            'disk' => \App\Services\MediaLegacyImportService::STORY_EDITOR_DISK,
            'path' => '1-test/episode_1/story.md',
            'url' => 'https://example.test/api/admin/media/' . fake()->uuid() . '/stream',
            'original_name' => 'story.md',
            'mime_type' => 'text/markdown',
            'extension' => 'md',
            'media_type' => MediaAsset::TYPE_DOCUMENT,
            'size_bytes' => 128,
            'folder' => 'production',
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);

        $response = $this->getJson('/api/admin/media?media_type=document');
        $response->assertOk()->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('document', $response->json('data.0.media_type'));
    }

    public function test_media_library_renames_asset_title(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        Sanctum::actingAs($admin);

        $asset = MediaAsset::create([
            'uuid' => fake()->uuid(),
            'disk' => 'public',
            'path' => 'media/2026/06/rename-me.jpg',
            'url' => 'https://example.test/storage/media/2026/06/rename-me.jpg',
            'original_name' => 'rename-me.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'media_type' => MediaAsset::TYPE_IMAGE,
            'size_bytes' => 1024,
            'folder' => 'stories',
            'title' => 'Old Title',
            'status' => MediaAsset::STATUS_ACTIVE,
        ]);

        $response = $this->putJson("/api/admin/media/{$asset->id}", [
            'title' => 'کاور جدید',
        ]);

        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.title', 'کاور جدید');
        $this->assertDatabaseHas('media_assets', [
            'id' => $asset->id,
            'title' => 'کاور جدید',
            'original_name' => 'rename-me.jpg',
        ]);
    }
}
