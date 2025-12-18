<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Episode;
use App\Models\Story;
use App\Models\Category;
use App\Models\ImageTimeline;
use App\Services\ImageTimelineService;

class ImageTimelineTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $episode;
    protected $story;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->category = Category::factory()->create();
        $this->story = Story::factory()->create(['category_id' => $this->category->id]);
        $this->episode = Episode::factory()->create(['story_id' => $this->story->id]);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function can_get_episode_timeline()
    {
        // Create timeline entries
        ImageTimeline::factory()->count(3)->create(['episode_id' => $this->episode->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/episodes/{$this->episode->id}/image-timeline");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'episode_id',
                    'image_timeline' => [
                        '*' => [
                            'id',
                            'start_time',
                            'end_time',
                            'image_url',
                            'image_order'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function can_create_timeline()
    {
        $timelineData = [
            'image_timeline' => [
                [
                    'start_time' => 0,
                    'end_time' => 10,
                    'image_url' => 'https://example.com/image1.jpg'
                ],
                [
                    'start_time' => 11,
                    'end_time' => 20,
                    'image_url' => 'https://example.com/image2.jpg'
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$this->episode->id}/image-timeline", $timelineData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تایم‌لاین تصاویر با موفقیت ذخیره شد'
            ]);

        $this->assertDatabaseHas('image_timelines', [
            'episode_id' => $this->episode->id,
            'start_time' => 0,
            'end_time' => 10
        ]);
    }

    /** @test */
    public function can_get_image_for_specific_time()
    {
        // Create timeline entry
        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'start_time' => 0,
            'end_time' => 15,
            'image_url' => 'https://example.com/image1.jpg'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/episodes/{$this->episode->id}/image-for-time?time=10");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'episode_id' => $this->episode->id,
                    'time' => 10,
                    'image_url' => 'https://example.com/image1.jpg'
                ]
            ]);
    }

    /** @test */
    public function returns_404_when_no_image_for_time()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/episodes/{$this->episode->id}/image-for-time?time=100");

        $response->assertStatus(404);
    }

    /** @test */
    public function can_delete_timeline()
    {
        // Create timeline entry
        ImageTimeline::factory()->create(['episode_id' => $this->episode->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/episodes/{$this->episode->id}/image-timeline");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تایم‌لاین تصاویر با موفقیت حذف شد'
            ]);

        $this->assertDatabaseMissing('image_timelines', [
            'episode_id' => $this->episode->id
        ]);
    }

    /** @test */
    public function validates_timeline_data()
    {
        $invalidData = [
            'image_timeline' => [
                [
                    'start_time' => -1, // Invalid: negative time
                    'end_time' => 10,
                    'image_url' => 'https://example.com/image1.jpg'
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$this->episode->id}/image-timeline", $invalidData);

        $response->assertStatus(422);
    }

    /** @test */
    public function validates_overlapping_times()
    {
        $overlappingData = [
            'image_timeline' => [
                [
                    'start_time' => 0,
                    'end_time' => 10,
                    'image_url' => 'https://example.com/image1.jpg'
                ],
                [
                    'start_time' => 5, // Overlaps with previous
                    'end_time' => 15,
                    'image_url' => 'https://example.com/image2.jpg'
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$this->episode->id}/image-timeline", $overlappingData);

        $response->assertStatus(422);
    }

    /** @test */
    public function validates_invalid_image_urls()
    {
        $invalidUrlData = [
            'image_timeline' => [
                [
                    'start_time' => 0,
                    'end_time' => 10,
                    'image_url' => 'not-a-valid-url' // Invalid URL
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$this->episode->id}/image-timeline", $invalidUrlData);

        $response->assertStatus(422);
    }

    /** @test */
    public function requires_authentication()
    {
        $response = $this->getJson("/api/v1/episodes/{$this->episode->id}/image-timeline");

        $response->assertStatus(401);
    }

    /** @test */
    public function can_get_timeline_statistics()
    {
        // Create multiple timeline entries
        ImageTimeline::factory()->count(5)->create(['episode_id' => $this->episode->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/episodes/{$this->episode->id}/timeline-statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'episode_id',
                    'statistics' => [
                        'total_entries',
                        'first_image_start',
                        'last_image_end',
                        'unique_images'
                    ]
                ]
            ]);
    }

    /** @test */
    public function timeline_service_validation_works()
    {
        $service = new ImageTimelineService();
        
        // Test valid timeline
        $validTimeline = [
            [
                'start_time' => 0,
                'end_time' => 10,
                'image_url' => 'https://example.com/image1.jpg'
            ]
        ];

        $this->expectNotToPerformAssertions();
        $service->validateTimeline(30, $validTimeline); // Should not throw exception
    }

    /** @test */
    public function timeline_service_optimization_works()
    {
        $service = new ImageTimelineService();
        
        $timelineData = [
            [
                'start_time' => 0,
                'end_time' => 10,
                'image_url' => 'https://example.com/image1.jpg'
            ],
            [
                'start_time' => 11,
                'end_time' => 15,
                'image_url' => 'https://example.com/image1.jpg' // Same image
            ]
        ];

        $optimized = $service->optimizeTimeline($timelineData);
        
        $this->assertCount(1, $optimized);
        $this->assertEquals(0, $optimized[0]['start_time']);
        $this->assertEquals(15, $optimized[0]['end_time']);
    }
}