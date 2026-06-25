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
}
