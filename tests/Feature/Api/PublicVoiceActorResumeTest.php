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

    public function test_public_listing_includes_voice_actors_without_published_resume(): void
    {
        $publicVa = User::factory()->voiceActor()->create(['first_name' => 'آوا']);
        UserResume::factory()->public()->create(['user_id' => $publicVa->id]);

        $draftVa = User::factory()->voiceActor()->create(['first_name' => 'پیش‌نویس']);
        UserResume::factory()->create(['user_id' => $draftVa->id, 'is_public' => false]);

        $bareVa = User::factory()->voiceActor()->create(['first_name' => 'بدون‌رزومه']);

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
        $draftCard = collect($response->json('data'))->firstWhere('id', $draftVa->id);

        $this->assertTrue($ids->contains($publicVa->id));
        $this->assertTrue($ids->contains($draftVa->id));
        $this->assertTrue($ids->contains($bareVa->id));
        $this->assertTrue($ids->contains($headListed->id));
        $this->assertFalse($ids->contains($head->id));
        $this->assertFalse($ids->contains($inactive->id));
        $this->assertNotNull($draftCard);
        $this->assertNull($draftCard['headline']);
        $this->assertSame([], $draftCard['specialties']);
        $this->assertStringNotContainsString('phone', strtolower(json_encode($response->json('data'))));
    }

    public function test_public_listing_can_require_profile_photo(): void
    {
        $withPhoto = User::factory()->voiceActor()->create([
            'first_name' => 'باعکس',
            'profile_image_url' => 'voice-actors/ava.jpg',
        ]);
        $withoutPhoto = User::factory()->voiceActor()->create([
            'first_name' => 'بی‌عکس',
            'profile_image_url' => null,
        ]);
        $emptyPhoto = User::factory()->voiceActor()->create([
            'first_name' => 'خالی',
            'profile_image_url' => '',
        ]);

        $ids = collect(
            $this->getJson('/api/v1/public/voice-actors?has_photo=1')
                ->assertOk()
                ->assertJsonPath('success', true)
                ->json('data')
        )->pluck('id');

        $this->assertTrue($ids->contains($withPhoto->id));
        $this->assertFalse($ids->contains($withoutPhoto->id));
        $this->assertFalse($ids->contains($emptyPhoto->id));
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

    public function test_public_show_voice_actor_without_public_resume_hides_resume_body(): void
    {
        $va = User::factory()->voiceActor()->create(['first_name' => 'صدا']);
        UserResume::factory()->create([
            'user_id' => $va->id,
            'is_public' => false,
            'headline' => 'مخفی',
            'about' => 'نباید دیده شود',
        ]);

        $response = $this->getJson('/api/v1/public/voice-actors/'.$va->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.first_name', 'صدا')
            ->assertJsonPath('data.user.resume', null)
            ->assertJsonPath('data.user.headline', null);

        $this->assertStringNotContainsString('نباید دیده شود', (string) json_encode($response->json()));
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
        $this->assertSame('متن درباره', $va->fresh()->bio);
    }

    public function test_writer_and_admin_can_save_own_resume(): void
    {
        foreach ([User::factory()->writer(), User::factory()->admin()] as $factory) {
            $user = $factory->create();
            Sanctum::actingAs($user);

            $this->putJson('/api/v1/me/resume', [
                'headline' => 'رزومه تیم',
                'about' => 'یک درباره کامل برای کارت و صفحه رزومه',
                'is_public' => false,
            ])->assertOk()->assertJsonPath('data.resume.headline', 'رزومه تیم');

            $this->assertSame(
                'یک درباره کامل برای کارت و صفحه رزومه',
                $user->fresh()->bio
            );
        }
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

    public function test_owner_can_see_unpublished_resume_on_profile(): void
    {
        $va = User::factory()->voiceActor()->create();
        UserResume::factory()->create([
            'user_id' => $va->id,
            'headline' => 'پیش‌نویس خصوصی',
            'about' => 'متن کامل رزومه پیش‌نویس',
            'is_public' => false,
        ]);

        $guest = $this->getJson('/api/v1/users/'.$va->id.'/stories')->assertOk()->json('data.user');
        $this->assertNull($guest['resume']);
        $this->assertNull($guest['headline']);

        Sanctum::actingAs($va);
        $owner = $this->getJson('/api/v1/users/'.$va->id.'/stories')->assertOk()->json('data.user');
        $this->assertSame('پیش‌نویس خصوصی', $owner['headline']);
        $this->assertSame('متن کامل رزومه پیش‌نویس', $owner['resume']['about']);
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
