<?php

namespace Tests\Feature\Api;

use App\Models\Story;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserResume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicVoiceActorResumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_omits_unpublished_and_non_directory_users(): void
    {
        $publicVa = User::factory()->voiceActor()->create(['first_name' => 'آوا']);
        UserResume::factory()->public()->create(['user_id' => $publicVa->id]);

        $draftVa = User::factory()->voiceActor()->create(['first_name' => 'پیش‌نویس']);
        UserResume::factory()->create(['user_id' => $draftVa->id, 'is_public' => false]);

        $head = User::factory()->headWriter()->create(['first_name' => 'سرپرست']);
        UserResume::factory()->public()->create([
            'user_id' => $head->id,
            'show_in_talent_directory' => false,
        ]);

        $headListed = User::factory()->headWriter()->create(['first_name' => 'نمایشی']);
        UserResume::factory()->public()->create([
            'user_id' => $headListed->id,
            'show_in_talent_directory' => true,
        ]);

        $inactive = User::factory()->voiceActor()->create([
            'first_name' => 'غیرفعال',
            'status' => User::STATUS_INACTIVE,
        ]);
        UserResume::factory()->public()->create(['user_id' => $inactive->id]);

        $response = $this->getJson('/api/v1/public/voice-actors')->assertOk()->assertJsonPath('success', true);
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($publicVa->id));
        $this->assertTrue($ids->contains($headListed->id));
        $this->assertFalse($ids->contains($draftVa->id));
        $this->assertFalse($ids->contains($head->id));
        $this->assertFalse($ids->contains($inactive->id));
        $this->assertStringNotContainsString('phone', strtolower(json_encode($response->json('data'))));
    }

    public function test_public_show_never_contains_phone_number(): void
    {
        $va = User::factory()->voiceActor()->create([
            'phone_number' => '09139999999',
            'first_name' => 'صدا',
        ]);
        UserResume::factory()->public()->create([
            'user_id' => $va->id,
            'headline' => 'راوی',
            'about' => 'درباره من',
        ]);

        $response = $this->getJson('/api/v1/public/voice-actors/'.$va->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.headline', 'راوی');

        $json = json_encode($response->json());
        $this->assertStringNotContainsString('09139999999', (string) $json);
        $this->assertArrayNotHasKey('phone_number', $response->json('data.user'));
    }

    public function test_public_show_404_when_resume_not_public(): void
    {
        $va = User::factory()->voiceActor()->create();
        UserResume::factory()->create(['user_id' => $va->id, 'is_public' => false]);

        $this->getJson('/api/v1/public/voice-actors/'.$va->id)->assertNotFound();
    }

    public function test_guest_cannot_put_me_resume(): void
    {
        $this->putJson('/api/v1/me/resume', ['headline' => 'x'])->assertUnauthorized();
    }

    public function test_parent_cannot_put_me_resume(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        Sanctum::actingAs($parent);

        $this->putJson('/api/v1/me/resume', ['headline' => 'x'])->assertForbidden();
    }

    public function test_voice_actor_can_save_own_resume(): void
    {
        $va = User::factory()->voiceActor()->create();
        Sanctum::actingAs($va);

        $this->putJson('/api/v1/me/resume', [
            'headline' => 'صداپیشه کودک',
            'years_of_experience' => 8,
            'about' => 'متن درباره',
            'specialties' => ['راوی'],
            'is_public' => true,
            'show_in_talent_directory' => true,
            'demo_url' => 'https://www.aparat.com/v/demo',
        ])->assertOk()->assertJsonPath('data.resume.headline', 'صداپیشه کودک');

        $this->assertDatabaseHas('user_resumes', [
            'user_id' => $va->id,
            'headline' => 'صداپیشه کودک',
            'is_public' => true,
            'show_in_talent_directory' => false,
        ]);
    }

    public function test_voice_actor_cannot_edit_another_resume_via_admin(): void
    {
        $va = User::factory()->voiceActor()->create();
        $other = User::factory()->voiceActor()->create();
        Sanctum::actingAs($va);

        $this->putJson('/api/admin/resumes/'.$other->id, [
            'headline' => 'هک',
        ])->assertForbidden();
    }

    public function test_head_writer_cannot_view_other_resume(): void
    {
        $head = User::factory()->headWriter()->create();
        $va = User::factory()->voiceActor()->create();
        UserResume::factory()->create(['user_id' => $va->id]);
        Sanctum::actingAs($head);

        $this->getJson('/api/admin/resumes/'.$va->id)->assertForbidden();
        $this->getJson('/api/admin/resumes')->assertForbidden();
    }

    public function test_head_writer_can_get_and_update_own_resume(): void
    {
        $head = User::factory()->headWriter()->create();
        Sanctum::actingAs($head);

        $this->getJson('/api/admin/resumes/me')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->putJson('/api/admin/resumes/me', [
            'headline' => 'سرپرست نویسندگان',
            'years_of_experience' => 12,
            'is_public' => true,
        ])->assertOk()->assertJsonPath('data.resume.headline', 'سرپرست نویسندگان');
    }

    public function test_full_admin_can_update_other_resume_including_directory_flag(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $head = User::factory()->headWriter()->create();
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/resumes/'.$head->id, [
            'headline' => 'منتشر در سایت',
            'is_public' => true,
            'show_in_talent_directory' => true,
        ])->assertOk()->assertJsonPath('data.resume.show_in_talent_directory', true);

        $this->assertTrue(
            collect($this->getJson('/api/v1/public/voice-actors')->json('data'))
                ->pluck('id')
                ->contains($head->id)
        );
    }

    public function test_public_works_are_published_only(): void
    {
        $va = User::factory()->voiceActor()->create();
        UserResume::factory()->public()->create(['user_id' => $va->id]);
        $this->makeStory(['author_id' => $va->id, 'status' => 'draft', 'title' => 'پیش‌نویس']);
        $published = $this->makeStory(['author_id' => $va->id, 'status' => 'published', 'title' => 'منتشر']);

        $data = $this->getJson('/api/v1/users/'.$va->id.'/stories')
            ->assertOk()
            ->json('data');

        $ids = collect($data['stories_as_author'])->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
        $this->assertNotNull($data['user']['resume']);
    }

    public function test_get_user_stories_still_works_for_parent_without_resume(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT, 'bio' => 'والد']);

        $this->getJson('/api/v1/users/'.$parent->id.'/stories')
            ->assertOk()
            ->assertJsonPath('data.user.bio', 'والد')
            ->assertJsonPath('data.user.resume', null);
    }

    public function test_public_team_members_omit_phone_number(): void
    {
        $user = User::factory()->create([
            'phone_number' => '09131234567',
            'first_name' => 'تیم',
        ]);
        TeamMember::query()->create([
            'user_id' => $user->id,
            'display_title' => 'کارگردان',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $response = $this->getJson('/api/v1/public/team-members')->assertOk();
        $row = collect($response->json('data'))->firstWhere('id', $user->id);
        $this->assertNotNull($row);
        $this->assertArrayNotHasKey('phone_number', $row);
        $this->assertStringNotContainsString('09131234567', (string) json_encode($response->json()));
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
            'title' => 'داستان تست',
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
