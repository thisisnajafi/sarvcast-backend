<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Episode;
use App\Models\Person;
use App\Models\EpisodeVoiceActor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EpisodeVoiceActorTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $episode;
    protected $person;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'parent']);
        $this->episode = Episode::factory()->create(['duration' => 300]);
        $this->person = Person::factory()->create();
    }

    /** @test */
    public function can_get_voice_actors_for_episode()
    {
        // Create voice actors
        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'start_time' => 0,
            'end_time' => 120
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/episodes/{$this->episode->id}/voice-actors");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'episode_id',
                    'voice_actors' => [
                        '*' => [
                            'id',
                            'person' => ['id', 'name', 'image_url'],
                            'role',
                            'character_name',
                            'start_time',
                            'end_time',
                            'voice_description',
                            'is_primary',
                            'duration'
                        ]
                    ],
                    'total_duration',
                    'has_multiple_voice_actors',
                    'voice_actor_count'
                ]
            ]);
    }

    /** @test */
    public function can_add_voice_actor_to_episode()
    {
        $voiceActorData = [
            'person_id' => $this->person->id,
            'role' => 'narrator',
            'character_name' => 'راوی',
            'start_time' => 0,
            'end_time' => 120,
            'voice_description' => 'صدای گرم و دوستانه',
            'is_primary' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/episodes/{$this->episode->id}/voice-actors", $voiceActorData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('episode_voice_actors', [
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'role' => 'narrator'
        ]);
    }

    /** @test */
    public function can_get_voice_actor_for_specific_time()
    {
        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'start_time' => 0,
            'end_time' => 120
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/episodes/{$this->episode->id}/voice-actor-for-time?time=60");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'person' => ['id', 'name'],
                    'role',
                    'start_time',
                    'end_time'
                ]
            ]);
    }

    /** @test */
    public function validates_voice_actor_data()
    {
        $invalidData = [
            'person_id' => 999, // Non-existent person
            'role' => '',
            'start_time' => -1, // Invalid time
            'end_time' => 0
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/episodes/{$this->episode->id}/voice-actors", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['person_id', 'role', 'start_time', 'end_time']);
    }

    /** @test */
    public function prevents_overlapping_voice_actors()
    {
        // Create first voice actor
        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'start_time' => 0,
            'end_time' => 120
        ]);

        // Try to create overlapping voice actor
        $overlappingData = [
            'person_id' => $this->person->id,
            'role' => 'character',
            'start_time' => 60, // Overlaps with first voice actor
            'end_time' => 180
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/episodes/{$this->episode->id}/voice-actors", $overlappingData);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function can_update_voice_actor()
    {
        $voiceActor = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'start_time' => 0,
            'end_time' => 120
        ]);

        $updateData = [
            'person_id' => $this->person->id,
            'role' => 'character',
            'character_name' => 'شاهزاده',
            'start_time' => 0,
            'end_time' => 150,
            'voice_description' => 'صدای نازک و کودکانه',
            'is_primary' => false
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/episodes/{$this->episode->id}/voice-actors/{$voiceActor->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('episode_voice_actors', [
            'id' => $voiceActor->id,
            'role' => 'character',
            'character_name' => 'شاهزاده'
        ]);
    }

    /** @test */
    public function can_delete_voice_actor()
    {
        $voiceActor = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/episodes/{$this->episode->id}/voice-actors/{$voiceActor->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('episode_voice_actors', [
            'id' => $voiceActor->id
        ]);
    }

    /** @test */
    public function can_get_voice_actor_statistics()
    {
        // Create multiple voice actors
        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'role' => 'narrator',
            'start_time' => 0,
            'end_time' => 120
        ]);

        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'role' => 'character',
            'start_time' => 120,
            'end_time' => 300
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/episodes/{$this->episode->id}/voice-actor-statistics");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'total_voice_actors',
                    'total_duration',
                    'roles',
                    'primary_voice_actor',
                    'voice_actor_timeline'
                ]
            ]);
    }
}
