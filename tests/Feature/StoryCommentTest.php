<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Story;
use App\Models\Category;
use App\Models\StoryComment;
use Illuminate\Support\Facades\RateLimiter;

class StoryCommentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $story;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->category = Category::factory()->create();
        $this->story = Story::factory()->create(['category_id' => $this->category->id]);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function can_get_story_comments()
    {
        // Create approved comments
        StoryComment::factory()->count(3)->create([
            'story_id' => $this->story->id,
            'is_approved' => true,
            'is_visible' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/stories/{$this->story->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'story_id',
                    'comments' => [
                        '*' => [
                            'id',
                            'comment',
                            'is_approved',
                            'is_visible',
                            'created_at',
                            'time_since_created',
                            'user' => [
                                'id',
                                'name',
                                'avatar'
                            ]
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /** @test */
    public function can_add_comment_to_story()
    {
        $commentData = [
            'comment' => 'داستان بسیار زیبا و آموزنده بود'
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stories/{$this->story->id}/comments", $commentData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'نظر شما با موفقیت ارسال شد و در انتظار تایید است'
            ]);

        $this->assertDatabaseHas('story_comments', [
            'story_id' => $this->story->id,
            'user_id' => $this->user->id,
            'comment' => 'داستان بسیار زیبا و آموزنده بود',
            'is_approved' => false
        ]);
    }

    /** @test */
    public function can_get_user_comments()
    {
        // Create comments by the user
        StoryComment::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'story_id' => $this->story->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/comments/my-comments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'comments' => [
                        '*' => [
                            'id',
                            'comment',
                            'is_approved',
                            'is_visible',
                            'created_at',
                            'time_since_created',
                            'user',
                            'story' => [
                                'id',
                                'title',
                                'image_url'
                            ]
                        ]
                    ],
                    'pagination'
                ]
            ]);
    }

    /** @test */
    public function can_delete_own_comment()
    {
        $comment = StoryComment::factory()->create([
            'user_id' => $this->user->id,
            'story_id' => $this->story->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'نظر با موفقیت حذف شد'
            ]);

        $this->assertDatabaseMissing('story_comments', [
            'id' => $comment->id
        ]);
    }

    /** @test */
    public function cannot_delete_other_users_comment()
    {
        $otherUser = User::factory()->create();
        $comment = StoryComment::factory()->create([
            'user_id' => $otherUser->id,
            'story_id' => $this->story->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function validates_comment_length()
    {
        $shortComment = ['comment' => 'ک']; // Too short
        $longComment = ['comment' => str_repeat('ک', 1001)]; // Too long

        // Test short comment
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stories/{$this->story->id}/comments", $shortComment);
        $response->assertStatus(422);

        // Test long comment
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stories/{$this->story->id}/comments", $longComment);
        $response->assertStatus(422);
    }

    /** @test */
    public function validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stories/{$this->story->id}/comments", []);

        $response->assertStatus(422);
    }

    /** @test */
    public function enforces_rate_limiting()
    {
        // Clear any existing rate limits
        RateLimiter::clear('post_comment:' . $this->user->id);

        // First comment should succeed
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stories/{$this->story->id}/comments", [
                'comment' => 'اولین نظر'
            ]);
        $response->assertStatus(201);

        // Second comment should be rate limited
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stories/{$this->story->id}/comments", [
                'comment' => 'دومین نظر'
            ]);
        $response->assertStatus(429);
    }

    /** @test */
    public function requires_authentication()
    {
        $response = $this->postJson("/api/v1/stories/{$this->story->id}/comments", [
            'comment' => 'نظر بدون احراز هویت'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function can_get_comment_statistics()
    {
        // Create various comments
        StoryComment::factory()->count(5)->create([
            'story_id' => $this->story->id,
            'is_approved' => true
        ]);
        
        StoryComment::factory()->count(2)->create([
            'story_id' => $this->story->id,
            'is_approved' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/stories/{$this->story->id}/comments/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'story_id',
                    'statistics' => [
                        'total_comments',
                        'approved_comments',
                        'pending_comments',
                        'recent_comments'
                    ]
                ]
            ]);
    }

    /** @test */
    public function only_shows_approved_comments_by_default()
    {
        // Create mixed comments
        StoryComment::factory()->create([
            'story_id' => $this->story->id,
            'is_approved' => true,
            'is_visible' => true
        ]);
        
        StoryComment::factory()->create([
            'story_id' => $this->story->id,
            'is_approved' => false,
            'is_visible' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/stories/{$this->story->id}/comments");

        $response->assertStatus(200);
        
        $comments = $response->json('data.comments');
        $this->assertCount(1, $comments);
        $this->assertTrue($comments[0]['is_approved']);
    }

    /** @test */
    public function admin_can_see_pending_comments()
    {
        // Make user an admin
        $this->user->role = 'admin';
        $this->user->save();

        // Create mixed comments
        StoryComment::factory()->create([
            'story_id' => $this->story->id,
            'is_approved' => true,
            'is_visible' => true
        ]);
        
        StoryComment::factory()->create([
            'story_id' => $this->story->id,
            'is_approved' => false,
            'is_visible' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/stories/{$this->story->id}/comments?include_pending=true");

        $response->assertStatus(200);
        
        $comments = $response->json('data.comments');
        $this->assertCount(2, $comments);
    }

    /** @test */
    public function comment_model_relationships_work()
    {
        $comment = StoryComment::factory()->create([
            'story_id' => $this->story->id,
            'user_id' => $this->user->id
        ]);

        // Test user relationship
        $this->assertEquals($this->user->id, $comment->user->id);
        
        // Test story relationship
        $this->assertEquals($this->story->id, $comment->story->id);
    }

    /** @test */
    public function comment_scopes_work()
    {
        // Create mixed comments
        StoryComment::factory()->create([
            'story_id' => $this->story->id,
            'is_approved' => true,
            'is_visible' => true
        ]);
        
        StoryComment::factory()->create([
            'story_id' => $this->story->id,
            'is_approved' => false,
            'is_visible' => true
        ]);

        // Test approved scope
        $approvedComments = StoryComment::approved()->get();
        $this->assertCount(1, $approvedComments);
        $this->assertTrue($approvedComments->first()->is_approved);

        // Test visible scope
        $visibleComments = StoryComment::visible()->get();
        $this->assertCount(2, $visibleComments);
    }
}