<?php

namespace Tests\Feature\Admin;

use App\Models\SchoolPartnership;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolPartnershipCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_rejects_school_address_and_accepts_location(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/schools', [
            'school_name' => 'دبستان آزمایشی',
            'school_address' => 'Wrong field',
        ])->assertStatus(422);

        $created = $this->postJson('/api/admin/schools', [
            'school_name' => 'دبستان آزمایشی',
            'location' => 'تهران، منطقه ۳',
            'status' => 'pending',
        ])->assertCreated()
            ->assertJsonPath('data.school_name', 'دبستان آزمایشی')
            ->assertJsonPath('data.location', 'تهران، منطقه ۳');

        $id = $created->json('data.id');
        $this->assertDatabaseHas('school_partnerships', [
            'id' => $id,
            'school_name' => 'دبستان آزمایشی',
            'location' => 'تهران، منطقه ۳',
        ]);
    }

    public function test_admin_can_update_and_delete_school_partnership(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $school = SchoolPartnership::create([
            'school_name' => 'مدرسه اول',
            'school_type' => 'public',
            'school_level' => 'elementary',
            'location' => 'اصفهان',
            'contact_person' => 'Ali',
            'contact_email' => 'a@test.com',
            'contact_phone' => '09120000000',
            'student_count' => 10,
            'teacher_count' => 2,
            'partnership_model' => 'pilot',
            'partnership_start_date' => now()->toDateString(),
            'partnership_end_date' => now()->addYear()->toDateString(),
            'status' => 'pending',
        ]);

        $this->putJson("/api/admin/schools/{$school->id}", [
            'location' => 'شیراز',
            'max_student_capacity' => 120,
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.location', 'شیراز')
            ->assertJsonPath('data.max_student_capacity', 120);

        $this->deleteJson("/api/admin/schools/{$school->id}")
            ->assertOk();

        $this->assertDatabaseMissing('school_partnerships', ['id' => $school->id]);
    }

    public function test_basic_user_cannot_create_school_partnership(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/admin/schools', [
            'school_name' => 'نامعتبر',
        ])->assertForbidden();
    }
}
