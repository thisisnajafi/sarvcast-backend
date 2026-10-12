<?php

namespace App\Services;

use App\Exceptions\SchoolPanelException;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SchoolManagerService
{
    public const PHONE_PATTERN = '/^09[0-9]{9}$/';

    public function createOrPromote(User $actor, array $input): SchoolManagerProfile
    {
        return DB::transaction(function () use ($actor, $input) {
            $phone = (string) $input['phone_number'];
            $user = User::query()->where('phone_number', $phone)->lockForUpdate()->first();

            if (! $user) {
                $first = trim((string) ($input['first_name'] ?? ''));
                $last = trim((string) ($input['last_name'] ?? ''));
                if ($first === '' || $last === '') {
                    throw new SchoolPanelException(
                        'name_required',
                        'برای شمارهٔ جدید، نام و نام خانوادگی لازم است.',
                        422,
                    );
                }

                $user = User::query()->create([
                    'phone_number' => $phone,
                    'first_name' => $first,
                    'last_name' => $last,
                    'role' => User::ROLE_SCHOOL_MANAGER,
                    'status' => User::STATUS_ACTIVE,
                    'phone_verified_at' => now(),
                    'onboarding_completed' => true,
                    'requires_2fa' => true,
                ]);
            } else {
                $this->assertPromotable($user);
                $this->fillNamesIfPresent($user, $input);
            }

            $profile = SchoolManagerProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($profile && $profile->status !== SchoolManagerProfile::STATUS_DELETED) {
                throw new SchoolPanelException(
                    'already_manager',
                    'این کاربر همین حالا مدیر مدرسه است. سقف اعضا را از صفحهٔ همان مدیر تغییر دهید.',
                    409,
                );
            }

            $user->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);

            $attributes = [
                'member_limit' => (int) $input['member_limit'],
                'label' => $this->nullableLabel($input['label'] ?? null),
                'status' => SchoolManagerProfile::STATUS_ACTIVE,
                'created_by' => $actor->id,
            ];

            if ($profile) {
                $profile->update($attributes);

                return $profile->fresh(['user']);
            }

            return SchoolManagerProfile::query()->create([
                'user_id' => $user->id,
                ...$attributes,
            ])->load('user');
        });
    }

    public function updateProfile(SchoolManagerProfile $profile, array $input): SchoolManagerProfile
    {
        if ($profile->status === SchoolManagerProfile::STATUS_DELETED) {
            throw new SchoolPanelException('deleted', 'این پنل بسته شده است.', 422);
        }

        $attributes = [];
        if (array_key_exists('member_limit', $input)) {
            $attributes['member_limit'] = (int) $input['member_limit'];
        }
        if (array_key_exists('label', $input)) {
            $attributes['label'] = $this->nullableLabel($input['label']);
        }
        if (array_key_exists('status', $input)) {
            $attributes['status'] = $input['status'];
        }

        if ($attributes !== []) {
            $profile->update($attributes);
        }

        return $profile->fresh(['user']);
    }

    public function closeProfile(SchoolManagerProfile $profile, User $actor): void
    {
        if ($profile->status === SchoolManagerProfile::STATUS_DELETED) {
            throw new SchoolPanelException('deleted', 'این پنل قبلاً بسته شده است.', 422);
        }

        DB::transaction(function () use ($profile, $actor) {
            SchoolMembership::query()
                ->where('school_manager_profile_id', $profile->id)
                ->whereNull('detached_at')
                ->update([
                    'active_user_id' => null,
                    'detached_at' => now(),
                    'detached_by' => $actor->id,
                    'updated_at' => now(),
                ]);

            $profile->update(['status' => SchoolManagerProfile::STATUS_DELETED]);

            $manager = $profile->user;
            if ($manager && $manager->role === User::ROLE_SCHOOL_MANAGER) {
                $manager->applyLegacyRoleChange(User::ROLE_BASIC);
                $manager->tokens()->delete();
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(SchoolManagerProfile $profile, string $phone): array
    {
        $user = User::query()->where('phone_number', $phone)->first();
        if (! $user) {
            throw new SchoolPanelException('not_found', 'این شماره در مانجی ثبت نشده است.', 404);
        }

        return $this->lookupPayload($profile, $user, $this->evaluate($profile, $user));
    }

    public function attach(SchoolManagerProfile $profile, User $actor, int $userId): SchoolMembership
    {
        return DB::transaction(function () use ($profile, $actor, $userId) {
            $lockedProfile = SchoolManagerProfile::query()->whereKey($profile->id)->lockForUpdate()->first();
            if (! $lockedProfile || $lockedProfile->status !== SchoolManagerProfile::STATUS_ACTIVE) {
                throw new SchoolPanelException('panel_inactive', 'پنل مدرسه فعال نیست.', 403);
            }

            $member = User::query()->whereKey($userId)->lockForUpdate()->first();
            if (! $member) {
                throw new SchoolPanelException('not_found', 'کاربر پیدا نشد.', 404);
            }

            $decision = $this->evaluate($lockedProfile, $member, true);
            if (! $decision['can_add']) {
                $reason = $decision['block_reason'];
                $status = in_array($reason, ['already_in_panel', 'managed_elsewhere'], true) ? 409 : 422;
                throw new SchoolPanelException($reason, $this->messageFor($reason), $status);
            }

            try {
                return SchoolMembership::query()->create([
                    'school_manager_profile_id' => $lockedProfile->id,
                    'user_id' => $member->id,
                    'active_user_id' => $member->id,
                    'attached_by' => $actor->id,
                ]);
            } catch (QueryException) {
                throw new SchoolPanelException(
                    'managed_elsewhere',
                    $this->messageFor('managed_elsewhere'),
                    409,
                );
            }
        });
    }

    public function detach(SchoolManagerProfile $profile, User $actor, User $member): void
    {
        $membership = SchoolMembership::query()
            ->where('school_manager_profile_id', $profile->id)
            ->where('user_id', $member->id)
            ->active()
            ->first();

        if (! $membership) {
            throw new SchoolPanelException('not_found', 'این کاربر در پنل شما نیست.', 404);
        }

        $membership->update([
            'active_user_id' => null,
            'detached_at' => now(),
            'detached_by' => $actor->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function profilePayload(SchoolManagerProfile $profile): array
    {
        $profile->loadMissing('user:id,first_name,last_name,phone_number,status,role');
        $user = $profile->user;
        $activeCount = $profile->active_member_count ?? $profile->activeMemberships()->count();

        return [
            'id' => $profile->id,
            'label' => $profile->label,
            'member_limit' => (int) $profile->member_limit,
            'active_member_count' => (int) $activeCount,
            'status' => $profile->status,
            'manager' => $user ? [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone_number' => $user->phone_number,
                'status' => $user->status,
            ] : null,
            'created_at' => $profile->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  iterable<int, SchoolMembership>  $memberships
     * @return list<array<string, mixed>>
     */
    public function memberPayloads(iterable $memberships): array
    {
        $models = new EloquentCollection($memberships instanceof EloquentCollection ? $memberships->all() : iterator_to_array($memberships));
        $models->loadMissing('user:id,first_name,last_name,phone_number,status');
        $subscriptions = $this->subscriptionSummaries(
            $models->pluck('user_id')->filter()->map(static fn ($id): int => (int) $id)->all(),
        );

        return $models->map(function (SchoolMembership $membership) use ($subscriptions): array {
            $user = $membership->user;

            return [
                'membership_id' => $membership->id,
                'user_id' => $membership->user_id,
                'first_name' => $user?->first_name,
                'last_name' => $user?->last_name,
                'phone_number' => $user?->phone_number,
                'status' => $user?->status,
                'attached_at' => $membership->created_at?->toIso8601String(),
                'subscription' => $subscriptions[$membership->user_id] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @return array{can_add: bool, block_reason: string|null}
     */
    public function evaluate(SchoolManagerProfile $profile, User $member, bool $lock = false): array
    {
        if ((int) $member->id === (int) $profile->user_id) {
            return ['can_add' => false, 'block_reason' => 'is_self'];
        }

        if (in_array($member->role, User::exclusiveStaffRoles(), true)) {
            return ['can_add' => false, 'block_reason' => 'not_a_consumer'];
        }

        if (! in_array($member->status, User::loginAllowedStatuses(), true)) {
            return ['can_add' => false, 'block_reason' => 'suspended_user'];
        }

        $existingQuery = SchoolMembership::query()
            ->where('user_id', $member->id)
            ->active();
        if ($lock) {
            $existingQuery->lockForUpdate();
        }
        $existing = $existingQuery->first();

        if ($existing) {
            if ((int) $existing->school_manager_profile_id === (int) $profile->id) {
                return ['can_add' => false, 'block_reason' => 'already_in_panel'];
            }

            return ['can_add' => false, 'block_reason' => 'managed_elsewhere'];
        }

        $countQuery = SchoolMembership::query()
            ->where('school_manager_profile_id', $profile->id)
            ->active();
        if ($lock) {
            $countQuery->lockForUpdate();
        }

        if ((int) $countQuery->count() >= (int) $profile->member_limit) {
            return ['can_add' => false, 'block_reason' => 'limit_reached'];
        }

        return ['can_add' => true, 'block_reason' => null];
    }

    public function messageFor(string $reason): string
    {
        return match ($reason) {
            'already_in_panel' => 'این کاربر همین حالا در پنل شماست.',
            'managed_elsewhere' => 'این کاربر به پنل مدیر دیگری متصل است.',
            'limit_reached' => 'سقف اعضای پنل پر شده است.',
            'not_a_consumer' => 'فقط کاربران عادی را می‌توان به پنل افزود.',
            'suspended_user' => 'حساب این کاربر فعال نیست.',
            'is_self' => 'نمی‌توانید خودتان را به پنل اضافه کنید.',
            default => 'افزودن این کاربر ممکن نیست.',
        };
    }

    /**
     * @param  array{can_add: bool, block_reason: string|null}  $decision
     * @return array<string, mixed>
     */
    private function lookupPayload(SchoolManagerProfile $profile, User $member, array $decision): array
    {
        $subscriptions = $this->subscriptionSummaries([(int) $member->id]);

        return [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'phone_number' => $member->phone_number,
            'status' => $member->status,
            'subscription' => $subscriptions[$member->id] ?? null,
            'can_add' => $decision['can_add'],
            'block_reason' => $decision['block_reason'],
        ];
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, array{type: string, status: string, end_date: string|null}>
     */
    private function subscriptionSummaries(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = Subscription::query()
            ->whereIn('user_id', $userIds)
            ->grantingAccess()
            ->orderByDesc('end_date')
            ->get(['user_id', 'type', 'status', 'end_date']);

        $summaries = [];
        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            if (isset($summaries[$userId])) {
                continue;
            }
            $summaries[$userId] = [
                'type' => (string) $row->type,
                'status' => (string) $row->status,
                'end_date' => $row->end_date?->toIso8601String(),
            ];
        }

        return $summaries;
    }

    private function assertPromotable(User $user): void
    {
        if ($user->role === User::ROLE_SCHOOL_MANAGER) {
            return;
        }

        if (in_array($user->role, User::exclusiveStaffRoles(), true) || $user->role === User::ROLE_CHILD) {
            throw new SchoolPanelException(
                'not_promotable',
                'این شماره متعلق به حسابی است که نمی‌تواند مدیر مدرسه شود.',
                422,
            );
        }

        if (! in_array($user->role, User::promotableSchoolManagerRoles(), true)) {
            throw new SchoolPanelException(
                'not_promotable',
                'فقط کاربران عادی را می‌توان مدیر مدرسه کرد.',
                422,
            );
        }
    }

    private function fillNamesIfPresent(User $user, array $input): void
    {
        $updates = [];
        $first = trim((string) ($input['first_name'] ?? ''));
        $last = trim((string) ($input['last_name'] ?? ''));
        if ($first !== '') {
            $updates['first_name'] = $first;
        }
        if ($last !== '') {
            $updates['last_name'] = $last;
        }
        if ($updates !== []) {
            $user->update($updates);
        }
    }

    private function nullableLabel(mixed $label): ?string
    {
        if ($label === null) {
            return null;
        }
        $trimmed = trim((string) $label);

        return $trimmed === '' ? null : $trimmed;
    }
}
