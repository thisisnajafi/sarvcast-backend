<?php

namespace App\Services;

use App\Events\NewUserRegistrationEvent;
use App\Exceptions\SchoolPanelException;
use App\Support\NewUserNotificationLogger;
use App\Models\Payment;
use App\Models\PlayHistory;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\SchoolPremiumWindow;
use Carbon\Carbon;
use App\Helpers\JalaliHelper;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\SchoolAccessCatalog;
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
                    NewUserNotificationLogger::warning('registration_early_return', [
                        'source' => 'school_manager.create_or_promote',
                        'reason' => 'name_required',
                    ]);
                    throw new SchoolPanelException(
                        'name_required',
                        'برای شمارهٔ جدید، نام و نام خانوادگی لازم است.',
                        422,
                    );
                }

                NewUserNotificationLogger::debug('user_record_creating', [
                    'source' => 'school_manager.create_or_promote',
                    'role' => User::ROLE_SCHOOL_MANAGER,
                    'event' => NewUserRegistrationEvent::class,
                    'queue_connection' => (string) config('queue.default'),
                ]);
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
                NewUserNotificationLogger::debug('user_record_created', [
                    'source' => 'school_manager.create_or_promote',
                    'new_user_id' => (int) $user->id,
                    'role' => $user->role,
                    'event' => NewUserRegistrationEvent::class,
                ]);
            } else {
                NewUserNotificationLogger::warning('registration_early_return', [
                    'source' => 'school_manager.create_or_promote',
                    'reason' => 'existing_user_promoted',
                    'existing_user_id' => (int) $user->id,
                ]);
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

    /**
     * A parent or basic user who was given the school-manager role or panel
     * permissions from the user form becomes a real school manager. The same
     * phone stays on the same account so the app login still works.
     *
     * A closed panel is not reopened here. That stays on the school-managers screen.
     */
    public function repairLoginAssignment(User $user): User
    {
        if ($user->role === User::ROLE_SCHOOL_MANAGER) {
            $this->ensureActiveProfile($user, null, false);

            return $user->fresh() ?? $user;
        }

        if (! in_array($user->role, [User::ROLE_PARENT, User::ROLE_BASIC], true)) {
            return $user;
        }

        $profile = $user->schoolManagerProfile;
        if ($profile && $profile->status === SchoolManagerProfile::STATUS_DELETED) {
            return $user;
        }

        if (! $this->hasPostedOrStoredSchoolAssignment($user, [], [])) {
            return $user;
        }

        $user->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);
        $this->ensureActiveProfile($user, null, false);

        return $user->fresh() ?? $user;
    }

    /**
     * @param  list<int>  $roleIds
     * @param  list<int>  $permissionIds
     * @param  array{member_limit?: int, label?: string|null}  $profileInput
     */
    public function assignFromUserForm(
        User $user,
        ?User $actor,
        array $roleIds,
        array $permissionIds,
        array $profileInput = [],
    ): void {
        $promoting = $user->role !== User::ROLE_SCHOOL_MANAGER
            && in_array($user->role, User::promotableSchoolManagerRoles(), true)
            && $this->hasPostedOrStoredSchoolAssignment($user, $roleIds, $permissionIds);

        if ($promoting && $actor instanceof User && $actor->isSuperAdmin()) {
            $user->applyLegacyRoleChange(User::ROLE_SCHOOL_MANAGER);
        }

        if ($user->fresh()?->role === User::ROLE_SCHOOL_MANAGER) {
            $this->ensureActiveProfile($user, $actor, true, $profileInput);
        }
    }

    /**
     * @param  array{member_limit?: int, label?: string|null}  $profileInput
     */
    public function ensureActiveProfile(
        User $user,
        ?User $actor,
        bool $reopenDeleted = false,
        array $profileInput = [],
    ): SchoolManagerProfile {
        $profile = SchoolManagerProfile::query()->where('user_id', $user->id)->first();
        $hasLimit = array_key_exists('member_limit', $profileInput);
        $hasLabel = array_key_exists('label', $profileInput);
        $policy = $this->grantPolicyAttributes($profileInput);

        if (! $profile) {
            return SchoolManagerProfile::query()->create(array_merge([
                'user_id' => $user->id,
                'member_limit' => $hasLimit ? (int) $profileInput['member_limit'] : 0,
                'label' => $hasLabel ? $this->nullableLabel($profileInput['label']) : null,
                'status' => SchoolManagerProfile::STATUS_ACTIVE,
                'created_by' => $actor?->id,
            ], $policy));
        }

        $attributes = [];
        if ($reopenDeleted && $profile->status === SchoolManagerProfile::STATUS_DELETED) {
            $attributes['status'] = SchoolManagerProfile::STATUS_ACTIVE;
        }
        if ($hasLimit) {
            $attributes['member_limit'] = (int) $profileInput['member_limit'];
        }
        if ($hasLabel) {
            $attributes['label'] = $this->nullableLabel($profileInput['label']);
        }
        $attributes = array_merge($attributes, $policy);
        if ($attributes !== []) {
            $profile->update($attributes);
        }

        return $profile->fresh() ?? $profile;
    }

    /**
     * @param  list<int>  $roleIds
     * @param  list<int>  $permissionIds
     */
    public function hasPostedOrStoredSchoolAssignment(User $user, array $roleIds, array $permissionIds): bool
    {
        $schoolRoleId = Role::query()->where('name', User::ROLE_SCHOOL_MANAGER)->value('id');
        if ($schoolRoleId && in_array((int) $schoolRoleId, $roleIds, true)) {
            return true;
        }

        $panelIds = Permission::query()
            ->whereIn('name', SchoolAccessCatalog::panelPermissionNames())
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($permissionIds !== [] && array_intersect($permissionIds, $panelIds) !== []) {
            return true;
        }

        if ($user->roles()->where('name', User::ROLE_SCHOOL_MANAGER)->exists()) {
            return true;
        }

        $names = SchoolAccessCatalog::panelPermissionNames();

        return $user->directPermissions()->whereIn('name', $names)->exists()
            || $user->permissions()->whereIn('name', $names)->exists();
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
        $attributes = array_merge($attributes, $this->grantPolicyAttributes($input));

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

            app(SchoolPremiumGrantService::class)->closeOpenWindow($profile, SchoolPremiumWindow::STATUS_REVOKED);

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
        DB::transaction(function () use ($profile, $actor, $member): void {
            $membership = SchoolMembership::query()
                ->where('school_manager_profile_id', $profile->id)
                ->where('user_id', $member->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw new SchoolPanelException('not_found', 'این کاربر در پنل شما نیست.', 404);
            }

            app(SchoolPremiumGrantService::class)->revokeActiveForMember($profile, $member, $actor);

            $membership->update([
                'active_user_id' => null,
                'detached_at' => now(),
                'detached_by' => $actor->id,
            ]);
        });
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
            'active_grant_count' => app(SchoolPremiumGrantService::class)->activeGrantCount($profile),
            'premium_window' => app(SchoolPremiumGrantService::class)->windowPayload($profile),
            'premium_grant_policy' => $this->grantPolicyPayload($profile),
        ];
    }

    /**
     * Super-admin rules for free premium grants: deadline + allowed plans.
     *
     * @return array<string, mixed>
     */
    public function grantPolicyPayload(SchoolManagerProfile $profile): array
    {
        $expiresAt = $profile->premium_grant_expires_at;
        $planIds = $profile->allowedGrantPlanIds();
        $plans = $planIds === [] ? collect() : SubscriptionPlan::query()
            ->whereIn('id', $planIds)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'duration_days']);

        return [
            'expires_at' => $expiresAt?->toIso8601String(),
            'expires_at_jalali' => $expiresAt ? JalaliHelper::formatForDisplay($expiresAt, 'Y/m/d') : null,
            'seconds_remaining' => $expiresAt ? max(0, now()->diffInSeconds($expiresAt, false)) : null,
            'days_remaining' => $expiresAt ? max(0, (int) ceil(now()->diffInSeconds($expiresAt, false) / 86400)) : null,
            'ended' => $profile->premiumGrantPeriodEnded(),
            'allowed_plan_ids' => $planIds,
            'allowed_plans' => $plans->map(static fn (SubscriptionPlan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'duration_days' => (int) $plan->duration_days,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function grantPolicyAttributes(array $input): array
    {
        $attributes = [];
        if (array_key_exists('premium_grant_expires_at', $input)) {
            $value = $input['premium_grant_expires_at'];
            $attributes['premium_grant_expires_at'] = $value === null || $value === '' ? null : Carbon::parse($value);
        }
        if (array_key_exists('allowed_grant_plan_ids', $input)) {
            $ids = collect((array) ($input['allowed_grant_plan_ids'] ?? []))
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
            $attributes['allowed_grant_plan_ids'] = $ids === [] ? null : $ids;
        }

        return $attributes;
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

        $listenCounts = $this->listenCounts(
            $models->pluck('user_id')->filter()->map(static fn ($id): int => (int) $id)->all(),
        );

        return $models->map(function (SchoolMembership $membership) use ($subscriptions, $listenCounts): array {
            $user = $membership->user;
            $listens = $listenCounts[(int) $membership->user_id] ?? $this->emptyListenCounts();

            return [
                'membership_id' => $membership->id,
                'user_id' => $membership->user_id,
                'first_name' => $user?->first_name,
                'last_name' => $user?->last_name,
                'phone_number' => $user?->phone_number,
                'status' => $user?->status,
                'attached_at' => $membership->created_at?->toIso8601String(),
                'subscription' => $subscriptions[$membership->user_id] ?? null,
                'listen_count' => $listens['play_count'],
                'distinct_stories' => $listens['distinct_stories'],
                'completed_count' => $listens['completed_count'],
            ];
        })->values()->all();
    }

    /**
     * Listening totals for this manager's current members, plus one row per member.
     *
     * @return array{totals: array{play_count: int, distinct_stories: int, completed_count: int}, members: list<array<string, mixed>>}
     */
    public function cohortActivity(SchoolManagerProfile $profile): array
    {
        $memberships = SchoolMembership::query()
            ->where('school_manager_profile_id', $profile->id)
            ->active()
            ->with('user:id,first_name,last_name')
            ->orderByDesc('id')
            ->get();

        $userIds = $memberships->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();
        $counts = $this->listenCounts($userIds);

        $members = $memberships->map(function (SchoolMembership $membership) use ($counts): array {
            $row = $counts[(int) $membership->user_id] ?? $this->emptyListenCounts();

            return [
                'user_id' => (int) $membership->user_id,
                'first_name' => $membership->user?->first_name,
                'last_name' => $membership->user?->last_name,
                'play_count' => $row['play_count'],
                'distinct_stories' => $row['distinct_stories'],
                'completed_count' => $row['completed_count'],
            ];
        })->values()->all();

        return [
            'totals' => $this->listenTotals($userIds),
            'members' => $members,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function memberActivity(SchoolManagerProfile $profile, int $userId, int $perPage): array
    {
        $member = $this->activeMember($profile, $userId);
        $plays = PlayHistory::query()
            ->where('user_id', $member->id)
            ->with(['story:id,title', 'episode:id,title'])
            ->orderByDesc('played_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return [
            'user' => $this->memberIdentity($member),
            'totals' => $this->listenTotals([(int) $member->id]),
            'plays' => collect($plays->items())->map(function (PlayHistory $play): array {
                return [
                    'id' => $play->id,
                    'story_id' => $play->story_id,
                    'story_title' => $play->story?->title,
                    'episode_id' => $play->episode_id,
                    'episode_title' => $play->episode?->title,
                    'played_at' => $play->played_at?->toIso8601String(),
                    'duration_played' => (int) $play->duration_played,
                    'completed' => (bool) $play->completed,
                ];
            })->values()->all(),
            'meta' => [
                'page' => $plays->currentPage(),
                'perPage' => $plays->perPage(),
                'total' => $plays->total(),
                'lastPage' => $plays->lastPage(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function memberSubscriptions(SchoolManagerProfile $profile, int $userId): array
    {
        $member = $this->activeMember($profile, $userId);

        $subscriptions = Subscription::query()
            ->where('user_id', $member->id)
            ->orderByDesc('id')
            ->get();

        $payments = Payment::query()
            ->where('user_id', $member->id)
            ->orderByDesc('id')
            ->get();

        return [
            'user' => $this->memberIdentity($member),
            'subscriptions' => $subscriptions->map(function (Subscription $subscription): array {
                return [
                    'id' => $subscription->id,
                    'type' => $subscription->type,
                    'status' => $subscription->status,
                    'start_date' => $subscription->start_date?->toIso8601String(),
                    'end_date' => $subscription->end_date?->toIso8601String(),
                    'price' => (int) $subscription->price,
                    'currency' => $subscription->currency,
                    'auto_renew' => (bool) $subscription->auto_renew,
                    'payment_method' => $subscription->payment_method,
                    'grants_access' => $subscription->grantsAccess(),
                ];
            })->values()->all(),
            'payments' => $payments->map(function (Payment $payment): array {
                $metadata = $payment->payment_metadata ?? [];

                return [
                    'id' => $payment->id,
                    'subscription_id' => $payment->subscription_id,
                    'amount' => (int) $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'created_at' => $payment->created_at?->toIso8601String(),
                    'source' => $metadata['source'] ?? null,
                    'payer_user_id' => isset($metadata['payer_user_id']) ? (int) $metadata['payer_user_id'] : null,
                ];
            })->values()->all(),
        ];
    }

    public function activeMember(SchoolManagerProfile $profile, int $userId): User
    {
        $membership = SchoolMembership::query()
            ->where('school_manager_profile_id', $profile->id)
            ->where('user_id', $userId)
            ->active()
            ->first();

        $member = $membership?->user;
        if (! $member) {
            throw new SchoolPanelException('not_found', 'این کاربر در پنل شما نیست.', 404);
        }

        return $member;
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

    /**
     * @param  list<int>  $userIds
     * @return array<int, array{play_count: int, distinct_stories: int, completed_count: int}>
     */
    private function listenCounts(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = PlayHistory::query()
            ->whereIn('user_id', $userIds)
            ->select('user_id')
            ->selectRaw('COUNT(*) as play_count')
            ->selectRaw('COUNT(DISTINCT story_id) as distinct_stories')
            ->selectRaw('SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count')
            ->groupBy('user_id')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row->user_id] = [
                'play_count' => (int) $row->play_count,
                'distinct_stories' => (int) $row->distinct_stories,
                'completed_count' => (int) $row->completed_count,
            ];
        }

        return $counts;
    }

    /**
     * @param  list<int>  $userIds
     * @return array{play_count: int, distinct_stories: int, completed_count: int}
     */
    private function listenTotals(array $userIds): array
    {
        if ($userIds === []) {
            return $this->emptyListenCounts();
        }

        $row = PlayHistory::query()
            ->whereIn('user_id', $userIds)
            ->selectRaw('COUNT(*) as play_count')
            ->selectRaw('COUNT(DISTINCT story_id) as distinct_stories')
            ->selectRaw('SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count')
            ->first();

        return [
            'play_count' => (int) ($row->play_count ?? 0),
            'distinct_stories' => (int) ($row->distinct_stories ?? 0),
            'completed_count' => (int) ($row->completed_count ?? 0),
        ];
    }

    /**
     * @return array{play_count: int, distinct_stories: int, completed_count: int}
     */
    private function emptyListenCounts(): array
    {
        return [
            'play_count' => 0,
            'distinct_stories' => 0,
            'completed_count' => 0,
        ];
    }

    /**
     * @return array{id: int, first_name: string|null, last_name: string|null, phone_number: string|null, status: string|null}
     */
    private function memberIdentity(User $member): array
    {
        return [
            'id' => (int) $member->id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'phone_number' => $member->phone_number,
            'status' => $member->status,
        ];
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
