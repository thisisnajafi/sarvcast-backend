<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Episode;
use App\Models\Person;
use App\Models\EpisodeVoiceActor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminVoiceActorInterfaceTest extends TestCase
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

    public function test_admin_voice_actor_routes_exist()
    {
        $this->actingAs($this->admin);
        
        // Test that routes are defined and accessible
        $this->assertTrue(route('admin.episodes.voice-actors.index', $this->episode) !== null);
        $this->assertTrue(route('admin.episodes.voice-actors.create', $this->episode) !== null);
        $this->assertTrue(route('admin.api.voice-actors.data', $this->episode) !== null);
        $this->assertTrue(route('admin.api.voice-actors.statistics', $this->episode) !== null);
        $this->assertTrue(route('admin.api.voice-actors.available-people') !== null);
    }

    public function test_admin_voice_actor_controller_methods_exist()
    {
        $controller = new \App\Http\Controllers\Admin\EpisodeVoiceActorController(
            new \App\Services\EpisodeVoiceActorService()
        );
        
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'store'));
        $this->assertTrue(method_exists($controller, 'edit'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'destroy'));
        $this->assertTrue(method_exists($controller, 'bulkAction'));
        $this->assertTrue(method_exists($controller, 'getVoiceActorsData'));
        $this->assertTrue(method_exists($controller, 'getVoiceActorStatistics'));
        $this->assertTrue(method_exists($controller, 'validateTimeRange'));
        $this->assertTrue(method_exists($controller, 'getAvailablePeople'));
    }

    public function test_episode_voice_actor_model_relationships()
    {
        $episode = new Episode();
        $this->assertTrue(method_exists($episode, 'voiceActors'));
        $this->assertTrue(method_exists($episode, 'primaryVoiceActor'));
        $this->assertTrue(method_exists($episode, 'hasMultipleVoiceActors'));
        $this->assertTrue(method_exists($episode, 'getVoiceActorCount'));
    }

    public function test_episode_voice_actor_model_methods()
    {
        $voiceActor = new EpisodeVoiceActor();
        $this->assertTrue(method_exists($voiceActor, 'episode'));
        $this->assertTrue(method_exists($voiceActor, 'person'));
        $this->assertTrue(method_exists($voiceActor, 'toApiResponse'));
        $this->assertTrue(method_exists($voiceActor, 'scopeInTimeRange'));
    }

    public function test_episode_voice_actor_service_methods()
    {
        $service = new \App\Services\EpisodeVoiceActorService();
        
        $this->assertTrue(method_exists($service, 'getVoiceActorsForEpisode'));
        $this->assertTrue(method_exists($service, 'addVoiceActor'));
        $this->assertTrue(method_exists($service, 'updateVoiceActor'));
        $this->assertTrue(method_exists($service, 'deleteVoiceActor'));
        $this->assertTrue(method_exists($service, 'getVoiceActorForTime'));
        $this->assertTrue(method_exists($service, 'getVoiceActorsAtTime'));
        $this->assertTrue(method_exists($service, 'getVoiceActorsByRole'));
        $this->assertTrue(method_exists($service, 'getVoiceActorStatistics'));
        $this->assertTrue(method_exists($service, 'validateVoiceActorTimeRange'));
        $this->assertTrue(method_exists($service, 'getAvailablePeopleForEpisode'));
    }

    public function test_admin_views_exist()
    {
        $this->assertTrue(file_exists(resource_path('views/admin/episodes/voice-actors/index.blade.php')));
        $this->assertTrue(file_exists(resource_path('views/admin/episodes/voice-actors/create.blade.php')));
        $this->assertTrue(file_exists(resource_path('views/admin/episodes/voice-actors/edit.blade.php')));
        $this->assertTrue(file_exists(resource_path('views/admin/episodes/voice-actors/partials/form.blade.php')));
        $this->assertTrue(file_exists(resource_path('views/admin/episodes/voice-actors/partials/timeline.blade.php')));
        $this->assertTrue(file_exists(resource_path('views/admin/episodes/voice-actors/partials/statistics.blade.php')));
    }

    public function test_admin_assets_exist()
    {
        $this->assertTrue(file_exists(public_path('js/voice-actor-management.js')));
        $this->assertTrue(file_exists(public_path('css/voice-actor-management.css')));
    }

    public function test_admin_navigation_includes_voice_actors()
    {
        $layoutContent = file_get_contents(resource_path('views/admin/layouts/app.blade.php'));
        
        $this->assertStringContainsString('مدیریت صداپیشگان', $layoutContent);
        $this->assertStringContainsString('voice-actor-management.js', $layoutContent);
        $this->assertStringContainsString('voice-actor-management.css', $layoutContent);
    }

    public function test_episode_show_view_includes_voice_actors()
    {
        $showViewContent = file_get_contents(resource_path('views/admin/episodes/show.blade.php'));
        
        $this->assertStringContainsString('صداپیشگان', $showViewContent);
        $this->assertStringContainsString('مدیریت صداپیشگان', $showViewContent);
        $this->assertStringContainsString('voice-actors.index', $showViewContent);
    }

    public function test_voice_actor_management_views_content()
    {
        $indexView = file_get_contents(resource_path('views/admin/episodes/voice-actors/index.blade.php'));
        $createView = file_get_contents(resource_path('views/admin/episodes/voice-actors/create.blade.php'));
        $editView = file_get_contents(resource_path('views/admin/episodes/voice-actors/edit.blade.php'));
        
        // Check index view
        $this->assertStringContainsString('صداپیشگان اپیزود', $indexView);
        $this->assertStringContainsString('data-episode-id', $indexView);
        $this->assertStringContainsString('voice-actor-management', $indexView);
        
        // Check create view
        $this->assertStringContainsString('افزودن صداپیشه جدید', $createView);
        $this->assertStringContainsString('person_id', $createView);
        $this->assertStringContainsString('role', $createView);
        $this->assertStringContainsString('start_time', $createView);
        $this->assertStringContainsString('end_time', $createView);
        
        // Check edit view
        $this->assertStringContainsString('ویرایش صداپیشه', $editView);
        $this->assertStringContainsString('voiceActor', $editView);
    }

    public function test_api_routes_are_defined()
    {
        $apiRoutes = file_get_contents(base_path('routes/api.php'));
        
        $this->assertStringContainsString('EpisodeVoiceActorController', $apiRoutes);
        $this->assertStringContainsString('voice-actors', $apiRoutes);
        $this->assertStringContainsString('getVoiceActors', $apiRoutes);
        $this->assertStringContainsString('addVoiceActor', $apiRoutes);
        $this->assertStringContainsString('updateVoiceActor', $apiRoutes);
        $this->assertStringContainsString('deleteVoiceActor', $apiRoutes);
    }

    public function test_web_routes_are_defined()
    {
        $webRoutes = file_get_contents(base_path('routes/web.php'));
        
        $this->assertStringContainsString('episodes.voice-actors.', $webRoutes);
        $this->assertStringContainsString('EpisodeVoiceActorController', $webRoutes);
        $this->assertStringContainsString('voice-actors.index', $webRoutes);
        $this->assertStringContainsString('voice-actors.create', $webRoutes);
        $this->assertStringContainsString('voice-actors.store', $webRoutes);
        $this->assertStringContainsString('voice-actors.edit', $webRoutes);
        $this->assertStringContainsString('voice-actors.update', $webRoutes);
        $this->assertStringContainsString('voice-actors.destroy', $webRoutes);
    }
}
