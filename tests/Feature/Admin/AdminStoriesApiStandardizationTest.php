<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminStoriesApiStandardizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stories_index_returns_canonical_meta_and_legacy_pagination(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'stories-meta@test.com',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/stories?page=1&perPage=10');

        $response->assertOk()->assertJsonStructure([
            'success',
            'data',
            'meta' => ['page', 'perPage', 'total', 'lastPage'],
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_stories_export_returns_csv_for_super_admin(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'stories-export@test.com',
            'status' => 'active',
        ]);

        Sanctum::actingAs($super);

        $response = $this->get('/api/admin/stories/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function test_stories_api_store_creates_story_with_dashboard_payload(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'stories-create@test.com',
            'status' => 'active',
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/stories', [
            'title' => 'داستان تست',
            'description' => 'توضیحات کامل داستان تست برای API',
            'category_id' => $categoryId,
            'status' => 'draft',
            'age_rating' => '7+',
            'is_premium' => false,
            'cover_image_url' => 'https://my.manji.ir/storage/media/2026/06/example.webp',
            'tags' => ['تست'],
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('stories', [
            'title' => 'داستان تست',
            'age_group' => '7+',
            'image_url' => 'https://my.manji.ir/storage/media/2026/06/example.webp',
        ]);
    }

    public function test_stories_api_store_without_cover_image_uses_empty_image_url(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'stories-create-no-cover@test.com',
            'status' => 'active',
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'No Cover Category',
            'slug' => 'no-cover-category',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/stories', [
            'title' => 'داستان بدون کاور',
            'description' => 'توضیحات کامل داستان بدون تصویر کاور',
            'category_id' => $categoryId,
            'status' => 'draft',
            'age_rating' => 'all',
            'is_premium' => false,
            'cover_image_url' => '',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('stories', [
            'title' => 'داستان بدون کاور',
            'image_url' => '',
            'age_group' => 'all',
        ]);
    }
}