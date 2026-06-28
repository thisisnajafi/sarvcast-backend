<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class LocalImportAccessService
{
    /**
     * @return array{user: User, plain_text_token: string, token_name: string, abilities: array<int, string>}
     */
    public function issueToken(?int $userId = null, ?string $phone = null, bool $revokeExisting = true): array
    {
        $user = $this->resolveSuperAdmin($userId, $phone);
        $tokenName = (string) config('local_import.token_name', 'local-old-stories-import');
        $abilities = config('local_import.token_abilities', ['local-import:bootstrap']);

        if ($revokeExisting) {
            $user->tokens()->where('name', $tokenName)->delete();
        }

        $accessToken = $user->createToken($tokenName, $abilities);

        return [
            'user' => $user,
            'plain_text_token' => $accessToken->plainTextToken,
            'token_name' => $tokenName,
            'abilities' => $abilities,
        ];
    }

    public function validateBootstrapSecret(?string $provided): bool
    {
        $expected = config('local_import.bootstrap_secret');

        if (! is_string($expected) || $expected === '') {
            return false;
        }

        if (! is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }

    public function bootstrapEnabled(): bool
    {
        $secret = config('local_import.bootstrap_secret');

        return is_string($secret) && $secret !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyPayload(User $user): array
    {
        $token = $user->currentAccessToken();
        $baseUrl = rtrim((string) config('app.url'), '/') . '/api/admin';

        return [
            'authenticated' => true,
            'user_id' => $user->id,
            'role' => $user->role,
            'token_name' => $token?->name,
            'abilities' => $token?->abilities ?? [],
            'api_base_url' => $baseUrl,
            'endpoints' => [
                'verify' => 'GET /api/admin/local-import/verify',
                'story_editor_list' => 'GET /api/admin/story-editor/stories',
                'story_import' => 'POST /api/admin/story-editor/stories/{storySlug}/import',
                'episode_import' => 'POST /api/admin/story-editor/stories/{storySlug}/episodes/{episodeSlug}/import',
                'import_old_remote' => 'POST /api/admin/local-import/stories/import-old (phase C step 2)',
            ],
        ];
    }

    public function resolveSuperAdmin(?int $userId = null, ?string $phone = null): User
    {
        if ($userId !== null) {
            $user = User::query()->find($userId);
            if ($user !== null && $this->isEligibleIssuer($user)) {
                return $user;
            }

            throw new \RuntimeException("User #{$userId} is not an active super_admin.");
        }

        if (is_string($phone) && $phone !== '') {
            $user = User::query()->where('phone_number', $phone)->first();
            if ($user !== null && $this->isEligibleIssuer($user)) {
                return $user;
            }

            throw new \RuntimeException("No active super_admin found for phone {$phone}.");
        }

        $configuredId = config('local_import.super_admin_user_id');
        if (is_numeric($configuredId)) {
            return $this->resolveSuperAdmin((int) $configuredId, null);
        }

        $configuredPhone = config('local_import.super_admin_phone');
        if (is_string($configuredPhone) && $configuredPhone !== '') {
            return $this->resolveSuperAdmin(null, $configuredPhone);
        }

        $user = User::query()
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->whereIn('status', User::loginAllowedStatuses())
            ->orderBy('id')
            ->first();

        if ($user === null) {
            throw new \RuntimeException(
                'No active super_admin user found. Set LOCAL_IMPORT_SUPER_ADMIN_PHONE or pass --phone to the artisan command.'
            );
        }

        return $user;
    }

    public function generateBootstrapSecret(): string
    {
        return Str::random(64);
    }

    private function isEligibleIssuer(User $user): bool
    {
        return $user->isSuperAdmin()
            && in_array($user->status ?? null, User::loginAllowedStatuses(), true);
    }
}
