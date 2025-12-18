<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Episode;
use App\Models\Person;
use App\Models\EpisodeVoiceActor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminVoiceActorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $episode;
    protected $person;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->episode = Episode::factory()->create(['duration' => 300]);
        $this->person = Person::factory()->create();
    }

    /** @test */
    public function admin_can_view_voice_actor_management_page()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.episodes.voice-actors.index', $this->episode));

        $response->assertStatus(200);
        $response->assertViewIs('admin.episodes.voice-actors.index');
        $response->assertViewHas('episode', $this->episode);
    }

    /** @test */
    public function admin_can_view_create_voice_actor_page()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.episodes.voice-actors.create', $this->episode));

        $response->assertStatus(200);
        $response->assertViewIs('admin.episodes.voice-actors.create');
        $response->assertViewHas('episode', $this->episode);
        $response->assertViewHas('people');
    }

    /** @test */
    public function admin_can_create_voice_actor()
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

        $response = $this->actingAs($this->admin)
            ->post(route('admin.episodes.voice-actors.store', $this->episode), $voiceActorData);

        $response->assertRedirect(route('admin.episodes.voice-actors.index', $this->episode));
        
        $this->assertDatabaseHas('episode_voice_actors', [
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'role' => 'narrator',
            'character_name' => 'راوی',
            'is_primary' => true
        ]);
    }

    /** @test */
    public function admin_can_view_edit_voice_actor_page()
    {
        $voiceActor = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.episodes.voice-actors.edit', [$this->episode, $voiceActor]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.episodes.voice-actors.edit');
        $response->assertViewHas('episode', $this->episode);
        $response->assertViewHas('voiceActor', $voiceActor);
        $response->assertViewHas('people');
    }

    /** @test */
    public function admin_can_update_voice_actor()
    {
        $voiceActor = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'role' => 'narrator'
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

        $response = $this->actingAs($this->admin)
            ->put(route('admin.episodes.voice-actors.update', [$this->episode, $voiceActor]), $updateData);

        $response->assertRedirect(route('admin.episodes.voice-actors.index', $this->episode));
        
        $this->assertDatabaseHas('episode_voice_actors', [
            'id' => $voiceActor->id,
            'role' => 'character',
            'character_name' => 'شاهزاده',
            'is_primary' => false
        ]);
    }

    /** @test */
    public function admin_can_delete_voice_actor()
    {
        $voiceActor = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.episodes.voice-actors.destroy', [$this->episode, $voiceActor]));

        $response->assertRedirect(route('admin.episodes.voice-actors.index', $this->episode));
        
        $this->assertDatabaseMissing('episode_voice_actors', [
            'id' => $voiceActor->id
        ]);
    }

    /** @test */
    public function admin_can_get_voice_actors_data_via_api()
    {
        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.api.voice-actors.data', $this->episode));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
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
    public function admin_can_get_voice_actor_statistics_via_api()
    {
        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'role' => 'narrator',
            'is_primary' => true
        ]);

        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'role' => 'character',
            'is_primary' => false
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.api.voice-actors.statistics', $this->episode));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_voice_actors',
                'total_duration',
                'roles',
                'primary_voice_actor',
                'voice_actor_timeline'
            ]
        ]);
    }

    /** @test */
    public function admin_can_perform_bulk_actions()
    {
        $voiceActor1 = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'is_primary' => false
        ]);

        $voiceActor2 = EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id,
            'is_primary' => false
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.episodes.voice-actors.bulk-action', $this->episode), [
                'action' => 'update_primary',
                'voice_actor_ids' => [$voiceActor1->id]
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('episode_voice_actors', [
            'id' => $voiceActor1->id,
            'is_primary' => true
        ]);
    }

    /** @test */
    public function admin_can_validate_time_range()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.api.voice-actors.validate-time-range', $this->episode), [
                'start_time' => 0,
                'end_time' => 120,
                'person_id' => $this->person->id
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function admin_can_get_available_people()
    {
        Person::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.api.voice-actors.available-people'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'image_url',
                    'roles',
                    'is_verified'
                ]
            ]
        ]);
    }

    /** @test */
    public function voice_actor_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.episodes.voice-actors.store', $this->episode), []);

        $response->assertSessionHasErrors(['person_id', 'role', 'start_time', 'end_time']);
    }

    /** @test */
    public function voice_actor_creation_validates_time_range()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.episodes.voice-actors.store', $this->episode), [
                'person_id' => $this->person->id,
                'role' => 'narrator',
                'start_time' => 120,
                'end_time' => 60, // Invalid: end time before start time
            ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function episode_show_page_displays_voice_actors()
    {
        EpisodeVoiceActor::factory()->create([
            'episode_id' => $this->episode->id,
            'person_id' => $this->person->id
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.episodes.show', $this->episode));

        $response->assertStatus(200);
        $response->assertSee('صداپیشگان');
        $response->assertSee($this->person->name);
    }
}
