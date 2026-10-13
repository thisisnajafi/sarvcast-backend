<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Story;
use App\Models\Category;
use App\Models\Episode;
use App\Models\ImageTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->category = Category::factory()->create();
        $this->story = Story::factory()->create(['category_id' => $this->category->id]);
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test get all stories
     */
    public function test_can_get_all_stories()
    {
        Story::factory()->count(5)->create(['status' => 'published']);

        $response = $this->getJson('/api/v1/stories');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'category_id',
                            'author',
                            'status',
                            'created_at'
                        ]
                    ]
                ]);
    }

    /**
     * Test get story by ID
     */
    public function test_can_get_story_by_id()
    {
        $response = $this->getJson("/api/v1/stories/{$this->story->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'story' => [
                            'id',
                            'title',
                            'description',
                            'category_id',
                            'author',
                            'status',
                            'episodes_count',
                            'created_at'
                        ]
                    ]
                ]);
    }

    /**
     * Test get story episodes
     */
    public function test_can_get_story_episodes()
    {
        $episodes = Episode::factory()->count(3)->create([
            'story_id' => $this->story->id,
            'status' => 'published',
            'is_premium' => false,
        ]);
        foreach ($episodes as $episode) {
            ImageTimeline::factory()->create([
                'episode_id' => $episode->id,
                'story_id' => $this->story->id,
            ]);
        }

        $response = $this->getJson("/api/v1/stories/{$this->story->id}/episodes");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'episodes' => [
                            '*' => [
                                'id',
                                'title',
                                'description',
                                'story_id',
                                'episode_number',
                                'duration',
                                'status'
                            ]
                        ],
                        'pagination'
                    ]
                ]);
    }

    /**
     * Test add story to favorites
     */
    public function test_can_add_story_to_favorites()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson("/api/v1/stories/{$this->story->id}/favorite");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Story added to favorites'
                ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'story_id' => $this->story->id
        ]);
    }

    /**
     * Test remove story from favorites
     */
    public function test_can_remove_story_from_favorites()
    {
        // First add to favorites
        $this->user->favorites()->create(['story_id' => $this->story->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/v1/stories/{$this->story->id}/favorite");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Story removed from favorites'
                ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'story_id' => $this->story->id
        ]);
    }

    /**
     * Test rate a story
     */
    public function test_can_rate_story()
    {
        $ratingData = [
            'rating' => 5,
            'comment' => 'Great story!'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson("/api/v1/stories/{$this->story->id}/rating", $ratingData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Story rated successfully'
                ]);

        $this->assertDatabaseHas('ratings', [
            'user_id' => $this->user->id,
            'story_id' => $this->story->id,
            'rating' => 5
        ]);
    }

    /**
     * Test story rating validation
     */
    public function test_story_rating_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson("/api/v1/stories/{$this->story->id}/rating", [
            'rating' => 6 // Invalid rating
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['rating']);
    }

    /**
     * Test get stories by category
     */
    public function test_can_get_stories_by_category()
    {
        Story::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/v1/categories/{$this->category->id}/stories?page=1");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'category_id'
                        ]
                    ],
                    'pagination'
                ]);
    }

    /**
     * Test story search
     */
    public function test_can_search_stories()
    {
        Story::factory()->create(['title' => 'Test Story', 'status' => 'published']);
        Story::factory()->create(['title' => 'Another Story', 'status' => 'published']);

        $response = $this->getJson('/api/v1/stories?search=Test');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => ['id', 'title']
                    ]
                ])
                ->assertJsonFragment(['title' => 'Test Story']);
    }

    /**
     * Test story filtering by status
     */
    public function test_can_filter_stories_by_status()
    {
        Story::factory()->create(['status' => 'published', 'title' => 'Published Story']);
        Story::factory()->create(['status' => 'draft', 'title' => 'Draft Story']);

        $response = $this->getJson('/api/v1/stories?status=published');

        $response->assertStatus(200)
                ->assertJsonFragment(['title' => 'Published Story'])
                ->assertJsonMissing(['title' => 'Draft Story']);
    }

    /**
     * Test story sorting
     */
    public function test_can_sort_stories()
    {
        Story::factory()->count(3)->create(['status' => 'published']);

        $response = $this->getJson('/api/v1/stories?sort=title&order=asc');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => ['id', 'title']
                    ]
                ]);
    }

    /**
     * Test get user favorites
     */
    public function test_can_get_user_favorites()
    {
        $favoriteStory = Story::factory()->create();
        $this->user->favorites()->create(['story_id' => $favoriteStory->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/v1/user/favorites');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'favorites' => [
                            '*' => [
                                'id',
                                'title',
                                'description',
                                'category_id'
                            ]
                        ],
                        'pagination'
                    ]
                ]);
    }

    /**
     * Test get user play history
     */
    public function test_can_get_user_play_history()
    {
        $episode = Episode::factory()->create(['story_id' => $this->story->id]);
        $this->user->playHistories()->create([
            'episode_id' => $episode->id,
            'story_id' => $this->story->id,
            'played_at' => now(),
            'duration_played' => 10,
            'total_duration' => $episode->duration,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/v1/user/play-history');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'play_history' => [
                            '*' => [
                                'id',
                                'episode_id',
                                'played_at'
                            ]
                        ],
                        'pagination'
                    ]
                ]);
    }

    /**
     * Test episode details includes story favorite status
     */
    public function test_episode_details_includes_story_favorite_status()
    {
        $episode = Episode::factory()->create([
            'story_id' => $this->story->id,
            'status' => 'published',
            'is_premium' => false,
        ]);
        ImageTimeline::factory()->create([
            'episode_id' => $episode->id,
            'story_id' => $this->story->id,
        ]);

        // Test when story is not favorited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/v1/episodes/{$episode->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'episode',
                        'access_info',
                        'is_story_favorited'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'is_story_favorited' => false
                    ]
                ]);

        // Add story to favorites
        $this->user->favorites()->create(['story_id' => $this->story->id]);

        // Test when story is favorited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/v1/episodes/{$episode->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'is_story_favorited' => true
                    ]
                ]);
    }
}
