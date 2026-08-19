<?php

namespace Tests\Feature\Api;

use App\Models\Character;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoiceActorPanelCharactersTest extends TestCase
{
    use RefreshDatabase;

    public function test_story_details_only_return_characters_with_a_voice_actor(): void
    {
        $voiceActor = User::factory()->voiceActor()->create(['first_name' => 'آوا']);
        $story = $this->makeStory(['narrator_id' => $voiceActor->id]);

        Character::query()->create([
            'story_id' => $story->id,
            'name' => 'با صداپیشه',
            'voice_actor_id' => $voiceActor->id,
        ]);
        Character::query()->create([
            'story_id' => $story->id,
            'name' => 'بدون صداپیشه',
            'voice_actor_id' => null,
        ]);

        Sanctum::actingAs($voiceActor);

        $names = collect(
            $this->getJson('/api/v1/voice-actor-panel/stories/'.$story->id)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->json('data.characters')
        )->pluck('name');

        $this->assertTrue($names->contains('با صداپیشه'));
        $this->assertFalse($names->contains('بدون صداپیشه'));
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function makeStory(array $attrs = []): Story
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category '.uniqid(),
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Story::query()->create(array_merge([
            'title' => 'داستان پنل صداپیشه',
            'description' => 'توضیحات داستان تست',
            'image_url' => 'https://example.com/cover.webp',
            'category_id' => $categoryId,
            'age_group' => '7+',
            'language' => 'fa',
            'duration' => 10,
            'status' => 'draft',
        ], $attrs));
    }
}
