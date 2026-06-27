<?php

namespace Tests\Feature\Admin;

use App\Models\SmsTemplate;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SmsParameterResolver;
use App\Services\SmsAudienceBuilder;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AdminSmsTemplatesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'melipayamak.username' => 'test-user',
            'melipayamak.password' => 'test-pass',
            'services.melipayamk.sender' => '50002710008883',
        ]);
    }

    private function createAdminUser(array $overrides = []): User
    {
        static $counter = 0;
        $counter++;

        return User::create(array_merge([
            'phone_number' => '0913'.str_pad((string) $counter, 7, '0', STR_PAD_LEFT),
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'role' => 'super_admin',
            'status' => 'active',
            'password' => bcrypt('password'),
        ], $overrides));
    }

    public function test_sms_templates_index_returns_canonical_pagination(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        SmsTemplate::create($this->sampleTemplateAttributes());

        $response = $this->getJson('/api/admin/sms-templates?page=1&per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['page', 'perPage', 'total', 'lastPage'],
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_super_admin_can_create_sms_template(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $response = $this->postJson('/api/admin/sms-templates', [
            'name' => 'یادآوری اشتراک',
            'melipayamak_body_id' => 380001,
            'preview_text' => 'سلام {0} {1}، اشتراک شما فعال است. مانجی',
            'category' => 'marketing',
            'is_active' => true,
            'parameters' => [
                ['index' => 0, 'label' => 'نام', 'source' => 'user.first_name', 'fallback' => 'کاربر'],
                ['index' => 1, 'label' => 'نام خانوادگی', 'source' => 'user.last_name', 'fallback' => ''],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'یادآوری اشتراک')
            ->assertJsonPath('data.melipayamak_body_id', 380001);

        $this->assertDatabaseHas('sms_templates', [
            'name' => 'یادآوری اشتراک',
            'melipayamak_body_id' => 380001,
        ]);
    }

    public function test_create_template_rejects_mismatched_parameter_count(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $response = $this->postJson('/api/admin/sms-templates', [
            'name' => 'نامعتبر',
            'melipayamak_body_id' => 123,
            'preview_text' => 'سلام {0} {1} {2}',
            'parameters' => [
                ['index' => 0, 'label' => 'نام', 'source' => 'user.first_name', 'fallback' => 'کاربر'],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_super_admin_can_update_and_delete_template(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $template = SmsTemplate::create($this->sampleTemplateAttributes());

        $this->putJson("/api/admin/sms-templates/{$template->id}", [
            'name' => 'نام جدید',
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.name', 'نام جدید');

        $this->deleteJson("/api/admin/sms-templates/{$template->id}")
            ->assertOk();

        $this->assertDatabaseMissing('sms_templates', ['id' => $template->id]);
    }

    public function test_test_send_uses_sms_service_and_writes_log(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $template = SmsTemplate::create($this->sampleTemplateAttributes());

        $mock = Mockery::mock(SmsService::class);
        $mock->shouldReceive('sendSmsWithTemplate')
            ->once()
            ->with(
                '09123456789',
                380001,
                ['علی', 'احمدی'],
                Mockery::on(fn (array $context) => $context['sms_template_id'] === $template->id)
            )
            ->andReturn([
                'success' => true,
                'message_id' => 'msg-123',
                'sms_log_id' => 99,
            ]);

        $this->app->instance(SmsService::class, $mock);

        $response = $this->postJson("/api/admin/sms-templates/{$template->id}/test-send", [
            'phone_number' => '09123456789',
            'parameter_overrides' => [
                '0' => 'علی',
                '1' => 'احمدی',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message_id', 'msg-123');
    }

    public function test_test_send_resolves_parameters_from_selected_user(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $template = SmsTemplate::create($this->sampleTemplateAttributes());

        $targetUser = User::create([
            'phone_number' => '09125551234',
            'first_name' => 'Reza',
            'last_name' => 'Ahmadi',
            'role' => 'parent',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $mock = Mockery::mock(SmsService::class);
        $mock->shouldReceive('sendSmsWithTemplate')
            ->once()
            ->with(
                '09125551234',
                380001,
                ['Reza', 'Ahmadi'],
                Mockery::on(fn (array $context) => $context['sms_template_id'] === $template->id
                    && $context['user_id'] === $targetUser->id)
            )
            ->andReturn([
                'success' => true,
                'message_id' => 'msg-456',
                'sms_log_id' => 100,
            ]);

        $this->app->instance(SmsService::class, $mock);

        $response = $this->postJson("/api/admin/sms-templates/{$template->id}/test-send", [
            'user_id' => $targetUser->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $targetUser->id)
            ->assertJsonPath('data.phone_number', '09125551234')
            ->assertJsonPath('data.preview_message', 'سلام Reza Ahmadi');
    }

    public function test_test_send_rejects_user_without_phone_number(): void
    {
        Sanctum::actingAs($this->createAdminUser());

        $template = SmsTemplate::create($this->sampleTemplateAttributes());

        $targetUser = User::create([
            'phone_number' => null,
            'first_name' => 'No',
            'last_name' => 'Phone',
            'role' => 'parent',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $this->postJson("/api/admin/sms-templates/{$template->id}/test-send", [
            'user_id' => $targetUser->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_parameter_resolver_resolves_user_and_subscription_fields(): void
    {
        $user = User::create([
            'phone_number' => '09121111111',
            'first_name' => 'Sara',
            'last_name' => 'Karimi',
            'role' => 'parent',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'type' => '1year',
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'price' => 100000,
        ]);

        $resolver = app(SmsParameterResolver::class);

        $values = $resolver->resolve($user, [
            ['index' => 0, 'label' => 'نام', 'source' => 'user.first_name', 'fallback' => ''],
            ['index' => 1, 'label' => 'نام خانوادگی', 'source' => 'user.last_name', 'fallback' => ''],
            ['index' => 2, 'label' => 'نوع', 'source' => 'subscription.type_label', 'fallback' => '—'],
        ]);

        $this->assertSame('Sara', $values[0]);
        $this->assertSame('Karimi', $values[1]);
        $this->assertSame('یک ساله', $values[2]);

        $preview = $resolver->renderPreview('سلام {0} {1} - {2}', $values);
        $this->assertSame('سلام Sara Karimi - یک ساله', $preview);
    }

    public function test_audience_builder_filters_premium_and_non_premium_users(): void
    {
        $premiumUser = User::create([
            'phone_number' => '09120000001',
            'first_name' => 'Premium',
            'last_name' => 'User',
            'role' => 'parent',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        $freeUser = User::create([
            'phone_number' => '09120000002',
            'first_name' => 'Free',
            'last_name' => 'User',
            'role' => 'parent',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        Subscription::create([
            'user_id' => $premiumUser->id,
            'type' => '1month',
            'status' => 'active',
            'start_date' => now()->subWeek(),
            'end_date' => now()->addMonth(),
            'price' => 50000,
        ]);

        $builder = app(SmsAudienceBuilder::class);

        $premiumIds = $builder->buildQuery(SmsAudienceBuilder::TYPE_PREMIUM, ['exclude_admins' => false])
            ->pluck('id')
            ->all();

        $freeIds = $builder->buildQuery(SmsAudienceBuilder::TYPE_NON_PREMIUM, ['exclude_admins' => false])
            ->pluck('id')
            ->all();

        $this->assertContains($premiumUser->id, $premiumIds);
        $this->assertNotContains($freeUser->id, $premiumIds);
        $this->assertContains($freeUser->id, $freeIds);
        $this->assertNotContains($premiumUser->id, $freeIds);
    }

    public function test_template_service_validates_parameter_indices(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(SmsTemplateService::class)->validateTemplateData([
            'name' => 'Test',
            'melipayamak_body_id' => 100,
            'preview_text' => 'Hello {0}',
            'parameters' => [
                ['index' => 1, 'label' => 'A', 'source' => 'static', 'static_value' => 'X'],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleTemplateAttributes(): array
    {
        return [
            'name' => 'قالب تست',
            'slug' => 'ghaleb-test-380001',
            'melipayamak_body_id' => 380001,
            'preview_text' => 'سلام {0} {1}',
            'parameters' => [
                ['index' => 0, 'label' => 'نام', 'source' => 'user.first_name', 'fallback' => 'کاربر'],
                ['index' => 1, 'label' => 'نام خانوادگی', 'source' => 'user.last_name', 'fallback' => ''],
            ],
            'category' => 'marketing',
            'is_active' => true,
        ];
    }
}
