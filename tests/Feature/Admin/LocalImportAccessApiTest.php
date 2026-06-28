<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\LocalImportAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocalImportAccessApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_requires_server_secret_configured(): void
    {
        config(['local_import.bootstrap_secret' => null]);

        $response = $this->postJson('/api/admin/local-import/bootstrap', [], [
            'X-Local-Import-Bootstrap' => 'anything',
        ]);

        $response->assertStatus(503)->assertJson([
            'success' => false,
            'error' => 'BOOTSTRAP_DISABLED',
        ]);
    }

    public function test_bootstrap_rejects_invalid_secret(): void
    {
        config(['local_import.bootstrap_secret' => 'correct-secret']);

        $response = $this->postJson('/api/admin/local-import/bootstrap', [], [
            'X-Local-Import-Bootstrap' => 'wrong-secret',
        ]);

        $response->assertStatus(403)->assertJson([
            'success' => false,
            'error' => 'BOOTSTRAP_FORBIDDEN',
        ]);
    }

    public function test_bootstrap_issues_token_for_super_admin(): void
    {
        config(['local_import.bootstrap_secret' => 'correct-secret']);

        $super = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/admin/local-import/bootstrap', [], [
            'X-Local-Import-Bootstrap' => 'correct-secret',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $super->id)
            ->assertJsonStructure([
                'data' => ['token', 'api_base_url', 'local_env'],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame(1, $super->tokens()->count());
    }

    public function test_verify_accepts_issued_token(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $issued = app(LocalImportAccessService::class)->issueToken(userId: $super->id);
        $token = $issued['plain_text_token'];

        $response = $this->getJson('/api/admin/local-import/verify', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.authenticated', true)
            ->assertJsonPath('data.user_id', $super->id);
    }

    public function test_super_admin_can_rotate_token_via_admin_route(): void
    {
        $super = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        Sanctum::actingAs($super);

        $response = $this->postJson('/api/admin/local-import/token');

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);
    }
}
