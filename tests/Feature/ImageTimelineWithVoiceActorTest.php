<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Episode;
use App\Models\Person;
use App\Models\EpisodeVoiceActor;
use App\Models\ImageTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImageTimelineWithVoiceActorTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $episode;
    protected $person;
    protected $voiceActor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'parent']);
        $this->episode = Episode::factory()->create(['duration' => 300]);
        $this->person = Person::factory()->create();
        
        $this->voiceActor = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'start_time' => 0,
            'end_time' => 120
        ]);
    }

    /** @test */
    public function can_get_timeline_with_voice_actor_information()
    {
        // Create image timeline with voice actor
        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'start_time' => 0,
            'end_time' => 45,
            'image_url' => 'https://example.com/image1.jpg',
            'scene_description' => 'شروع داستان در جنگل',
            'transition_type' => 'fade',
            'is_key_frame' => true
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/episodes/{$this->episode->id}/image-timeline-with-voice-actors");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'episode_id',
                    'image_timeline' => [
                        '*' => [
                            'id',
                            'start_time',
                            'end_time',
                            'image_url',
                            'scene_description',
                            'transition_type',
                            'is_key_frame',
                            'voice_actor' => [
                                'id',
                                'person' => ['id', 'name'],
                                'role',
                                'character_name'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function can_create_timeline_with_voice_actor_association()
    {
        $timelineData = [
            [
                'start_time' => 0,
                'end_time' => 45,
                'image_url' => 'https://example.com/image1.jpg',
                'voice_actor_id' => $this->voiceActor->id,
                'scene_description' => 'شروع داستان در جنگل',
                'transition_type' => 'fade',
                'is_key_frame' => true
            ],
            [
                'start_time' => 46,
                'end_time' => 90,
                'image_url' => 'https://example.com/image2.jpg',
                'voice_actor_id' => $this->voiceActor->id,
                'scene_description' => 'ملاقات با شاهزاده',
                'transition_type' => 'slide',
                'is_key_frame' => false
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/episodes/{$this->episode->id}/image-timeline", [
                'image_timeline' => $timelineData
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('image_timelines', [
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'scene_description' => 'شروع داستان در جنگل',
            'transition_type' => 'fade',
            'is_key_frame' => true
        ]);
    }

    /** @test */
    public function can_get_timeline_for_specific_voice_actor()
    {
        // Create multiple timelines with different voice actors
        $voiceActor2 = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'start_time' => 120,
            'end_time' => 300
        ]);

        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'start_time' => 0,
            'end_time' => 45
        ]);

        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $voiceActor2->id,
            'start_time' => 120,
            'end_time' => 180
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/episodes/{$this->episode->id}/image-timeline-for-voice-actor?voice_actor_id={$this->voiceActor->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'episode_id',
                    'voice_actor_id',
                    'image_timeline'
                ]
            ]);

        // Should only return timelines for the specified voice actor
        $timelineData = $response->json('data.image_timeline');
        $this->assertCount(1, $timelineData);
        $this->assertEquals($this->voiceActor->id, $timelineData[0]['voice_actor']['id']);
    }

    /** @test */
    public function can_get_key_frames_for_episode()
    {
        // Create timelines with and without key frames
        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'is_key_frame' => true
        ]);

        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'is_key_frame' => false
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/episodes/{$this->episode->id}/key-frames");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'episode_id',
                    'key_frames'
                ]
            ]);

        // Should only return key frames
        $keyFrames = $response->json('data.key_frames');
        $this->assertCount(1, $keyFrames);
        $this->assertTrue($keyFrames[0]['is_key_frame']);
    }

    /** @test */
    public function can_get_timeline_by_transition_type()
    {
        // Create timelines with different transition types
        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'transition_type' => 'fade'
        ]);

        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'transition_type' => 'slide'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/episodes/{$this->episode->id}/timeline-by-transition-type?transition_type=fade");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'episode_id',
                    'transition_type',
                    'image_timeline'
                ]
            ]);

        // Should only return timelines with fade transition
        $timelineData = $response->json('data.image_timeline');
        $this->assertCount(1, $timelineData);
        $this->assertEquals('fade', $timelineData[0]['transition_type']);
    }

    /** @test */
    public function validates_timeline_with_voice_actor_data()
    {
        $invalidData = [
            [
                'start_time' => 0,
                'end_time' => 45,
                'image_url' => 'invalid-url', // Invalid URL
                'voice_actor_id' => 999, // Non-existent voice actor
                'transition_type' => 'invalid-type' // Invalid transition type
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/episodes/{$this->episode->id}/image-timeline", [
                'image_timeline' => $invalidData
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'image_timeline.0.image_url',
                'image_timeline.0.voice_actor_id',
                'image_timeline.0.transition_type'
            ]);
    }

    /** @test */
    public function can_get_enhanced_timeline_statistics()
    {
        // Create timelines with different properties
        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'transition_type' => 'fade',
            'is_key_frame' => true
        ]);

        ImageTimeline::factory()->create([
            'episode_id' => $this->episode->id,
            'voice_actor_id' => $this->voiceActor->id,
            'transition_type' => 'slide',
            'is_key_frame' => false
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/episodes/{$this->episode->id}/timeline-statistics");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'episode_id',
                    'statistics' => [
                        'total_segments',
                        'total_duration',
                        'unique_images',
                        'average_segment_duration',
                        'key_frames_count',
                        'transition_types',
                        'voice_actor_segments',
                        'voice_actors_used'
                    ]
                ]
            ]);

        $statistics = $response->json('data.statistics');
        $this->assertEquals(2, $statistics['total_segments']);
        $this->assertEquals(1, $statistics['key_frames_count']);
        $this->assertEquals(2, $statistics['voice_actor_segments']);
    }
}
