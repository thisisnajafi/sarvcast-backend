<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthPushTokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_unlinks_device_push_token_for_current_session(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('device-session')->plainTextToken;
        $accessTokenId = $this->tokenIdFromPlainText($plainTextToken);

        $this->insertDeviceRow(
            userId: (int) $user->id,
            deviceId: 'android-device-1',
            fcmToken: 'fcm-token-1',
            accessTokenId: $accessTokenId,
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainTextToken,
        ])->postJson('/api/v1/auth/logout', [
            'device_id' => 'android-device-1',
            'fcm_token' => 'fcm-token-1',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => 'android-device-1',
            'fcm_token' => null,
            'personal_access_token_id' => null,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $accessTokenId,
        ]);
    }

    public function test_sender_skips_tokens_unlinked_by_logout(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('device-session')->plainTextToken;
        $accessTokenId = $this->tokenIdFromPlainText($plainTextToken);

        $this->insertDeviceRow(
            userId: (int) $user->id,
            deviceId: 'android-device-2',
            fcmToken: 'fcm-token-2',
            accessTokenId: $accessTokenId,
        );

        $notificationService = app(NotificationService::class);
        $this->assertSame(['fcm-token-2'], $this->resolvedFcmTokens($notificationService, $user->fresh()));

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainTextToken,
        ])->postJson('/api/v1/auth/logout', [
            'device_id' => 'android-device-2',
            'fcm_token' => 'fcm-token-2',
        ])->assertOk();

        $this->assertSame([], $this->resolvedFcmTokens($notificationService, $user->fresh()));
    }

    public function test_login_flow_relinks_push_token_when_device_is_registered(): void
    {
        $phone = '09111111111';
        $user = User::factory()->create([
            'phone_number' => $phone,
        ]);
        Cache::put("otp_{$phone}_login", '123456', 300);

        $this->insertDeviceRow(
            userId: (int) $user->id,
            deviceId: 'android-device-3',
            fcmToken: null,
            accessTokenId: null,
        );

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'phone_number' => $phone,
            'verification_code' => '123456',
        ])->assertOk();

        $plainTextToken = (string) $loginResponse->json('data.token');
        $accessTokenId = $this->tokenIdFromPlainText($plainTextToken);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainTextToken,
        ])->postJson('/api/v1/mobile/device/register', [
            'device_id' => 'android-device-3',
            'device_type' => 'android',
            'device_model' => 'Pixel',
            'os_version' => '14',
            'app_version' => '1.2.3',
            'fcm_token' => 'fcm-token-3',
        ])->assertOk();

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => 'android-device-3',
            'fcm_token' => 'fcm-token-3',
            'personal_access_token_id' => $accessTokenId,
        ]);
    }

    public function test_registering_same_token_for_new_user_detaches_previous_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstUserToken = $firstUser->createToken('device-session')->plainTextToken;
        $firstAccessTokenId = $this->tokenIdFromPlainText($firstUserToken);
        $secondUserToken = $secondUser->createToken('device-session')->plainTextToken;
        $secondAccessTokenId = $this->tokenIdFromPlainText($secondUserToken);

        $this->insertDeviceRow(
            userId: (int) $firstUser->id,
            deviceId: 'android-device-4-a',
            fcmToken: 'shared-fcm-token',
            accessTokenId: $firstAccessTokenId,
        );

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $secondUserToken,
        ])->postJson('/api/v1/mobile/device/register', [
            'device_id' => 'android-device-4-b',
            'device_type' => 'android',
            'fcm_token' => 'shared-fcm-token',
        ])->assertOk();

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $firstUser->id,
            'device_id' => 'android-device-4-a',
            'fcm_token' => null,
            'personal_access_token_id' => null,
        ]);
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $secondUser->id,
            'device_id' => 'android-device-4-b',
            'fcm_token' => 'shared-fcm-token',
            'personal_access_token_id' => $secondAccessTokenId,
        ]);
    }

    private function tokenIdFromPlainText(string $plainTextToken): int
    {
        return (int) explode('|', $plainTextToken, 2)[0];
    }

    private function insertDeviceRow(int $userId, string $deviceId, ?string $fcmToken, ?int $accessTokenId): void
    {
        DB::table('user_devices')->insert([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'device_type' => 'android',
            'device_model' => 'test-device',
            'os_version' => '1.0',
            'app_version' => '1.0.0',
            'fcm_token' => $fcmToken,
            'personal_access_token_id' => $accessTokenId,
            'last_active' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolvedFcmTokens(NotificationService $service, User $user): array
    {
        $resolver = new \ReflectionMethod($service, 'getUserFcmTokens');
        $resolver->setAccessible(true);

        $tokens = $resolver->invoke($service, $user);
        return is_array($tokens) ? $tokens : [];
    }
}
