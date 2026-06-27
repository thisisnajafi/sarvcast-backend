<?php

namespace App\Services;

use App\Exceptions\InvalidSmsAudienceException;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SmsAudienceBuilder
{
    public const TYPE_ALL = 'all';

    public const TYPE_PREMIUM = 'premium';

    public const TYPE_NON_PREMIUM = 'non_premium';

    public const TYPE_ROLE_COLUMN = 'role_column';

    public const TYPE_RBAC_ROLE = 'rbac_role';

    public const TYPE_SPECIFIC_USERS = 'specific_users';

    public const TYPE_MANUAL_PHONES = 'manual_phones';

    /** @var array<int, string> */
    public const VALID_TYPES = [
        self::TYPE_ALL,
        self::TYPE_PREMIUM,
        self::TYPE_NON_PREMIUM,
        self::TYPE_ROLE_COLUMN,
        self::TYPE_RBAC_ROLE,
        self::TYPE_SPECIFIC_USERS,
        self::TYPE_MANUAL_PHONES,
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function buildQuery(string $audienceType, array $filters = []): Builder
    {
        if (! in_array($audienceType, self::VALID_TYPES, true)) {
            throw new InvalidSmsAudienceException("Invalid audience type: {$audienceType}");
        }

        if ($audienceType === self::TYPE_MANUAL_PHONES) {
            throw new InvalidSmsAudienceException('Manual phone audiences do not use a user query.');
        }

        $query = match ($audienceType) {
            self::TYPE_ALL => User::active()->whereNotNull('phone_number'),
            self::TYPE_PREMIUM => User::active()
                ->whereNotNull('phone_number')
                ->whereHas('subscriptions', fn ($q) => $q->active()),
            self::TYPE_NON_PREMIUM => User::active()
                ->whereNotNull('phone_number')
                ->whereDoesntHave('subscriptions', fn ($q) => $q->active()),
            self::TYPE_ROLE_COLUMN => User::active()
                ->whereNotNull('phone_number')
                ->where('role', $filters['role'] ?? ''),
            self::TYPE_RBAC_ROLE => User::active()
                ->whereNotNull('phone_number')
                ->whereHas('roles', function ($q) use ($filters) {
                    $roleIds = $filters['role_ids'] ?? $filters['rbac_role_ids'] ?? [];
                    $q->whereIn('roles.id', (array) $roleIds);
                }),
            self::TYPE_SPECIFIC_USERS => User::active()
                ->whereNotNull('phone_number')
                ->whereIn('id', (array) ($filters['user_ids'] ?? [])),
            default => throw new InvalidSmsAudienceException("Unsupported audience type: {$audienceType}"),
        };

        $query = $this->applyExclusions($query, $filters);

        if ($filters['exclude_admins'] ?? true) {
            $query->whereNotIn('role', [
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN,
                User::ROLE_VOICE_ACTOR,
            ]);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyExclusions(Builder $query, array $filters): Builder
    {
        $excludeUserIds = $filters['exclude_user_ids'] ?? [];

        if (count($excludeUserIds) > 0) {
            $query->whereNotIn('id', $excludeUserIds);
        }

        if ($filters['exclude_premium'] ?? false) {
            $query->whereDoesntHave('subscriptions', fn ($q) => $q->active());
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    public function resolveManualPhones(array $filters): array
    {
        $phones = $filters['phone_numbers'] ?? [];

        return collect($phones)
            ->map(fn ($phone) => $this->normalizePhone((string) $phone))
            ->filter(fn (string $phone) => $this->isValidIranianMobile($phone))
            ->unique()
            ->values()
            ->all();
    }

    public function isValidIranianMobile(string $phone): bool
    {
        return (bool) preg_match('/^09\d{9}$/', $phone);
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone) ?? $phone;

        if (str_starts_with($phone, '+98')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '98') && strlen($phone) === 12) {
            $phone = '0'.substr($phone, 2);
        }

        return $phone;
    }
}
