<?php

namespace App\Services;

use App\Exceptions\SchoolPanelException;
use App\Helpers\JalaliHelper;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\SchoolPremiumGrant;
use App\Models\SchoolPremiumWindow;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SchoolPremiumGrantService
{
    public const PAYMENT_METHOD = 'school_grant';

    public const BILLING_PLATFORM = 'school_manager';

    public const GRANT_SOURCE_SCHOOL_PANEL = 'school_panel';

    public function __construct(private readonly InAppNotificationService $notifications) {}

    public static function subscriptionTypeForMonths(int $months): string
    {
        return match ($months) {
            1 => '1month',
            3 => '3months',
            6 => '6months',
            12 => '1year',
            default => 'school_'.$months.'m',
        };
    }

    public function closeOpenWindow(SchoolManagerProfile $profile, string $status): int
    {
        return SchoolPremiumWindow::query()
            ->where('school_manager_profile_id', $profile->id)
            ->where('status', SchoolPremiumWindow::STATUS_OPEN)
            ->update([
                'status' => $status,
                'active_profile_id' => null,
                'closed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array{duration_months: int, seat_limit: int, expires_at: string, notes?: string|null}  $input
     */
    public function replaceWindow(SchoolManagerProfile $profile, User $actor, array $input): SchoolPremiumWindow
    {
        $this->assertProfileWritable($profile);

        return DB::transaction(function () use ($profile, $actor, $input) {
            $locked = SchoolManagerProfile::query()->whereKey($profile->id)->lockForUpdate()->first();
            $this->assertProfileWritable($locked);
            $this->closeOpenWindow($locked, SchoolPremiumWindow::STATUS_REVOKED);

            return $this->insertWindow($locked, $actor, $input);
        });
    }

    /**
     * @param  array{duration_months?: int, seat_limit?: int, expires_at?: string, notes?: string|null}  $input
     */
    public function adjustWindow(SchoolManagerProfile $profile, array $input): SchoolPremiumWindow
    {
        $this->assertProfileWritable($profile);

        return DB::transaction(function () use ($profile, $input) {
            $window = SchoolPremiumWindow::query()
                ->where('active_profile_id', $profile->id)
                ->where('status', SchoolPremiumWindow::STATUS_OPEN)
                ->lockForUpdate()
                ->first();

            if (! $window) {
                throw new SchoolPanelException('window_closed', 'پنجرهٔ باز پریمیوم برای این پنل وجود ندارد.', 422);
            }

            if (array_key_exists('seat_limit', $input)) {
                $limit = (int) $input['seat_limit'];
                $used = $window->grants()->count();
                if ($limit < $used) {
                    throw new SchoolPanelException(
                        'seats_below_used',
                        'سقف صندلی نمی‌تواند از تعداد اعطاهای همین پنجره کمتر باشد.',
                        422,
                    );
                }
                $window->seat_limit = $limit;
            }

            if (array_key_exists('duration_months', $input)) {
                $window->duration_months = (int) $input['duration_months'];
            }

            if (array_key_exists('notes', $input)) {
                $window->notes = $input['notes'];
            }

            if (array_key_exists('expires_at', $input)) {
                $expires = Carbon::parse($input['expires_at']);
                $window->expires_at = $expires;
                if ($expires->lessThanOrEqualTo(now())) {
                    $window->status = SchoolPremiumWindow::STATUS_EXPIRED;
                    $window->active_profile_id = null;
                    $window->closed_at = now();
                }
            }

            $window->save();

            return $window->fresh();
        });
    }

    /**
     * @return array{grant: array<string, mixed>, subscription: array<string, mixed>, premium_window: array<string, mixed>|null}
     */
    public function grant(SchoolManagerProfile $profile, User $actor, int $userId, ?int $planId = null): array
    {
        return DB::transaction(function () use ($profile, $actor, $userId, $planId) {
            $locked = SchoolManagerProfile::query()->whereKey($profile->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== SchoolManagerProfile::STATUS_ACTIVE) {
                throw new SchoolPanelException('panel_inactive', 'پنل مدرسه فعال نیست.', 403);
            }

            if ($locked->premiumGrantPeriodEnded()) {
                throw new SchoolPanelException(
                    'grant_period_ended',
                    'مهلت اهدای پریمیوم رایگان برای این پنل به پایان رسیده است.',
                    422,
                );
            }

            $plan = $this->resolveGrantPlan($locked, $planId);

            $membership = SchoolMembership::query()
                ->where('school_manager_profile_id', $locked->id)
                ->where('user_id', $userId)
                ->active()
                ->first();

            if (! $membership) {
                throw new SchoolPanelException('not_found', 'این کاربر در پنل شما نیست.', 404);
            }

            $window = SchoolPremiumWindow::query()
                ->where('active_profile_id', $locked->id)
                ->where('status', SchoolPremiumWindow::STATUS_OPEN)
                ->lockForUpdate()
                ->first();

            if (! $window || ! $window->expires_at || $window->expires_at->lessThanOrEqualTo(now())) {
                throw new SchoolPanelException(
                    'window_closed',
                    'پنجرهٔ پریمیوم رایگان بسته است یا هنوز تنظیم نشده.',
                    422,
                );
            }

            if ($window->grants()->where('user_id', $userId)->exists()) {
                throw new SchoolPanelException(
                    'already_granted',
                    'در این پنجره قبلاً به این کاربر پریمیوم رایگان داده شده است.',
                    409,
                );
            }

            $used = $window->grants()->count();
            if ($used >= (int) $window->seat_limit) {
                throw new SchoolPanelException('seats_full', 'ظرفیت اعطای رایگان این پنجره پر شده است.', 422);
            }

            $member = User::query()->find($userId);
            if (! $member) {
                throw new SchoolPanelException('not_found', 'این کاربر در پنل شما نیست.', 404);
            }

            $months = $plan ? $this->planMonths($plan) : (int) $window->duration_months;

            [$subscription, $endsAt, $previousEnd] = $this->writeSubscription(
                $member,
                $months,
                $locked->premium_grant_expires_at?->copy(),
            );

            try {
                $grant = SchoolPremiumGrant::query()->create([
                    'school_premium_window_id' => $window->id,
                    'school_manager_profile_id' => $locked->id,
                    'school_membership_id' => $membership->id,
                    'user_id' => $member->id,
                    'subscription_id' => $subscription->id,
                    'duration_months' => $months,
                    'premium_ends_at' => $endsAt,
                    'previous_end_date' => $previousEnd,
                    'status' => SchoolPremiumGrant::STATUS_ACTIVE,
                    'granted_by' => $actor->id,
                    'grant_source' => self::GRANT_SOURCE_SCHOOL_PANEL,
                    'source_profile_id' => $locked->id,
                    'source_manager_user_id' => $actor->id,
                ]);
            } catch (QueryException) {
                throw new SchoolPanelException(
                    'already_granted',
                    'در این پنجره قبلاً به این کاربر پریمیوم رایگان داده شده است.',
                    409,
                );
            }

            $metadata = $subscription->subscription_metadata ?? [];
            $metadata['source'] = 'school_grant';
            $metadata['grant_id'] = $grant->id;
            if ($plan) {
                $metadata['plan_id'] = $plan->id;
                $metadata['plan_name'] = $plan->name;
            }
            $subscription->subscription_metadata = $metadata;
            $subscription->save();

            $notification = $this->notifications->createNotification(
                (int) $member->id,
                'subscription',
                'اشتراک رایگان برای شما فعال شد',
                $this->grantMessage($months, $endsAt),
                [
                    'category' => 'subscription',
                    'priority' => 'high',
                    'is_important' => true,
                    'subscription_id' => $subscription->id,
                    'data' => [
                        'grant_id' => $grant->id,
                        'duration_months' => $months,
                        'plan_id' => $plan?->id,
                        'premium_ends_at' => $endsAt->toIso8601String(),
                    ],
                ],
            );

            $grant->notification_id = $notification->id;
            $grant->save();

            $locked->unsetRelation('latestWindow');

            return [
                'grant' => $this->grantPayload($grant->fresh(['user', 'window'])),
                'subscription' => [
                    'id' => $subscription->id,
                    'type' => $subscription->type,
                    'status' => $subscription->status,
                    'end_date' => $subscription->end_date?->toIso8601String(),
                    'payment_method' => $subscription->payment_method,
                    'grants_access' => $subscription->fresh()->grantsAccess(),
                ],
                'premium_window' => $this->windowPayload($locked->fresh()),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function revoke(SchoolPremiumGrant $grant, User $actor): array
    {
        return DB::transaction(function () use ($grant, $actor) {
            $locked = SchoolPremiumGrant::query()->whereKey($grant->id)->lockForUpdate()->first();
            if (! $locked) {
                throw new SchoolPanelException('not_found', 'این اعطا پیدا نشد.', 404);
            }
            if ($locked->status === SchoolPremiumGrant::STATUS_REVOKED) {
                throw new SchoolPanelException('already_revoked', 'این اعطا قبلاً لغو شده است.', 422);
            }

            $this->revokeLockedGrant($locked, $actor);

            return $this->grantPayload($locked->fresh(['user', 'window']));
        });
    }

    public function revokeActiveForMember(SchoolManagerProfile $profile, User $member, User $actor): int
    {
        return DB::transaction(function () use ($profile, $member, $actor): int {
            $grants = SchoolPremiumGrant::query()
                ->where('school_manager_profile_id', $profile->id)
                ->where('user_id', $member->id)
                ->where('grant_source', self::GRANT_SOURCE_SCHOOL_PANEL)
                ->where('status', SchoolPremiumGrant::STATUS_ACTIVE)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            foreach ($grants as $grant) {
                $this->revokeLockedGrant($grant, $actor);
            }

            return $grants->count();
        });
    }

    /**
     * @return array{windows: int, grants: int}
     */
    public function closeExpired(): array
    {
        $windows = SchoolPremiumWindow::query()
            ->where('status', SchoolPremiumWindow::STATUS_OPEN)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => SchoolPremiumWindow::STATUS_EXPIRED,
                'closed_at' => now(),
                'active_profile_id' => null,
                'updated_at' => now(),
            ]);

        $grants = SchoolPremiumGrant::query()
            ->where('status', SchoolPremiumGrant::STATUS_ACTIVE)
            ->where('premium_ends_at', '<=', now())
            ->update([
                'status' => SchoolPremiumGrant::STATUS_EXPIRED,
                'updated_at' => now(),
            ]);

        return ['windows' => $windows, 'grants' => $grants];
    }

    public function history(SchoolManagerProfile $profile, int $perPage): LengthAwarePaginator
    {
        return SchoolPremiumGrant::query()
            ->where('school_manager_profile_id', $profile->id)
            ->with(['user:id,first_name,last_name,phone_number', 'window:id,status,expires_at'])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn (SchoolPremiumGrant $grant) => $this->grantPayload($grant));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function windowPayload(SchoolManagerProfile $profile): ?array
    {
        $window = $profile->relationLoaded('latestWindow')
            ? $profile->getRelation('latestWindow')
            : $profile->latestWindow()->withCount('grants')->first();

        return $window instanceof SchoolPremiumWindow ? $this->presentWindow($window) : null;
    }

    public function activeGrantCount(SchoolManagerProfile $profile): int
    {
        $attributes = $profile->getAttributes();
        if (array_key_exists('active_grant_count', $attributes)) {
            return (int) $attributes['active_grant_count'];
        }

        return $profile->grants()->where('status', SchoolPremiumGrant::STATUS_ACTIVE)->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function grantPayload(SchoolPremiumGrant $grant): array
    {
        $grant->loadMissing(['user:id,first_name,last_name,phone_number', 'window:id,status,expires_at']);
        $user = $grant->user;

        return [
            'id' => $grant->id,
            'user_id' => $grant->user_id,
            'first_name' => $user?->first_name,
            'last_name' => $user?->last_name,
            'phone_number' => $user?->phone_number,
            'duration_months' => (int) $grant->duration_months,
            'premium_ends_at' => $grant->premium_ends_at?->toIso8601String(),
            'previous_end_date' => $grant->previous_end_date?->toIso8601String(),
            'status' => $grant->status,
            'notification_id' => $grant->notification_id,
            'subscription_id' => $grant->subscription_id,
            'window_id' => $grant->school_premium_window_id,
            'window_status' => $grant->window?->status,
            'granted_at' => $grant->created_at?->toIso8601String(),
            'revoked_at' => $grant->revoked_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{duration_months: int, seat_limit: int, expires_at: string, notes?: string|null}  $input
     */
    private function insertWindow(SchoolManagerProfile $profile, User $actor, array $input): SchoolPremiumWindow
    {
        $expires = Carbon::parse($input['expires_at']);
        if ($expires->lessThanOrEqualTo(now())) {
            throw new SchoolPanelException('expires_in_past', 'تاریخ پایان پنجره باید در آینده باشد.', 422);
        }

        return SchoolPremiumWindow::query()->create([
            'school_manager_profile_id' => $profile->id,
            'duration_months' => (int) $input['duration_months'],
            'seat_limit' => (int) $input['seat_limit'],
            'expires_at' => $expires,
            'status' => SchoolPremiumWindow::STATUS_OPEN,
            'active_profile_id' => $profile->id,
            'granted_by' => $actor->id,
            'notes' => $input['notes'] ?? null,
        ]);
    }

    /**
     * @return array{0: Subscription, 1: Carbon, 2: Carbon|null}
     */
    private function writeSubscription(User $member, int $months, ?Carbon $policyLimit): array
    {
        $existing = Subscription::query()
            ->where('user_id', $member->id)
            ->where(function ($query) {
                $query->where('end_date', '>', now())
                    ->orWhere(function ($open) {
                        $open->whereNull('end_date')->whereIn('status', ['active', 'trial']);
                    });
            })
            ->orderByDesc('end_date')
            ->lockForUpdate()
            ->first();

        if ($existing && $existing->grantsAccess()) {
            $previousEnd = $existing->end_date ? Carbon::parse($existing->end_date)->copy() : null;
            $anchor = $previousEnd && $previousEnd->greaterThan(now()) ? $previousEnd->copy() : now();
            $endsAt = $anchor->copy()->addMonths($months);
            $this->assertEndDateWithinPolicy($endsAt, $policyLimit);
            $existing->end_date = $endsAt;
            $existing->status = 'active';
            $existing->auto_renew = false;
            $existing->save();

            return [$existing, $endsAt, $previousEnd];
        }

        $start = now();
        $endsAt = $start->copy()->addMonths($months);
        $this->assertEndDateWithinPolicy($endsAt, $policyLimit);
        $subscription = Subscription::query()->create([
            'user_id' => $member->id,
            'type' => self::subscriptionTypeForMonths($months),
            'status' => 'active',
            'start_date' => $start,
            'end_date' => $endsAt,
            'price' => 0,
            'currency' => 'IRR',
            'auto_renew' => false,
            'payment_method' => self::PAYMENT_METHOD,
            'billing_platform' => self::BILLING_PLATFORM,
            'subscription_metadata' => ['source' => 'school_grant'],
        ]);

        return [$subscription, $endsAt, null];
    }

    private function revokeLockedGrant(SchoolPremiumGrant $grant, User $actor): void
    {
        $subscription = $grant->subscription_id
            ? Subscription::query()->whereKey($grant->subscription_id)->lockForUpdate()->first()
            : null;

        if ($subscription && $this->sameMoment($subscription->end_date, $grant->premium_ends_at)) {
            $this->rollBackSubscription($subscription, $grant->previous_end_date);
        }

        $grant->status = SchoolPremiumGrant::STATUS_REVOKED;
        $grant->revoked_at = now();
        $grant->revoked_by = $actor->id;
        $grant->save();
    }

    private function assertEndDateWithinPolicy(Carbon $endsAt, ?Carbon $policyLimit): void
    {
        if (! $policyLimit || ! $endsAt->greaterThan($policyLimit)) {
            return;
        }

        throw new SchoolPanelException(
            'grant_end_exceeds_limit',
            'تاریخ پایان پریمیوم از سقف تعیین‌شدهٔ مدیر کل عبور می‌کند. حداکثر تا '.$this->formatPolicyLimit($policyLimit).' مجاز است.',
            422,
        );
    }

    private function formatPolicyLimit(Carbon $policyLimit): string
    {
        return JalaliHelper::formatForDisplay($policyLimit, 'Y/m/d');
    }

    private function rollBackSubscription(Subscription $subscription, mixed $previousEnd): void
    {
        if ($previousEnd) {
            $restore = Carbon::parse($previousEnd);
            $subscription->end_date = $restore;
            $subscription->status = $restore->greaterThan(now()) ? 'active' : 'expired';
            $subscription->save();

            return;
        }

        if ($subscription->payment_method !== self::PAYMENT_METHOD) {
            $subscription->end_date = null;
            $subscription->status = 'active';
            $subscription->save();

            return;
        }

        $subscription->end_date = now();
        $subscription->status = 'expired';
        $subscription->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentWindow(SchoolPremiumWindow $window): array
    {
        $attributes = $window->getAttributes();
        $used = array_key_exists('grants_count', $attributes)
            ? (int) $attributes['grants_count']
            : $window->grants()->count();
        $limit = (int) $window->seat_limit;
        $open = $window->status === SchoolPremiumWindow::STATUS_OPEN
            && $window->expires_at
            && $window->expires_at->isFuture();

        return [
            'id' => $window->id,
            'duration_months' => (int) $window->duration_months,
            'seat_limit' => $limit,
            'seats_used' => $used,
            'seats_remaining' => max(0, $limit - $used),
            'expires_at' => $window->expires_at?->toIso8601String(),
            'status' => $window->status,
            'accepting_grants' => $open && $used < $limit,
            'notes' => $window->notes,
        ];
    }

    /**
     * Enforce the super-admin plan whitelist. Without a whitelist the window duration applies.
     */
    private function resolveGrantPlan(SchoolManagerProfile $profile, ?int $planId): ?SubscriptionPlan
    {
        $allowed = $profile->allowedGrantPlanIds();

        if ($allowed === []) {
            if ($planId !== null) {
                throw new SchoolPanelException(
                    'plan_not_allowed',
                    'برای این پنل طرحی جهت اهدای رایگان تعیین نشده است.',
                    422,
                );
            }

            return null;
        }

        if ($planId === null) {
            if (count($allowed) > 1) {
                throw new SchoolPanelException('plan_required', 'طرح اشتراک را برای اهدا انتخاب کنید.', 422);
            }
            $planId = $allowed[0];
        }

        if (! in_array($planId, $allowed, true)) {
            throw new SchoolPanelException(
                'plan_not_allowed',
                'اهدای رایگان این طرح برای پنل شما مجاز نیست.',
                422,
            );
        }

        $plan = SubscriptionPlan::query()->find($planId);
        if (! $plan) {
            throw new SchoolPanelException('plan_not_allowed', 'طرح انتخاب‌شده یافت نشد.', 422);
        }

        return $plan;
    }

    private function planMonths(SubscriptionPlan $plan): int
    {
        return max(1, (int) round(((int) $plan->duration_days) / 30));
    }

    private function grantMessage(int $months, Carbon $endsAt): string
    {
        $date = JalaliHelper::formatForDisplay($endsAt, 'Y/m/d');

        return "اشتراک رایگان {$months} ماهه تا تاریخ {$date} برای شما فعال شد.";
    }

    private function assertProfileWritable(?SchoolManagerProfile $profile): void
    {
        if (! $profile || $profile->status === SchoolManagerProfile::STATUS_DELETED) {
            throw new SchoolPanelException('deleted', 'این پنل بسته شده است.', 422);
        }
    }

    private function sameMoment(mixed $left, mixed $right): bool
    {
        if (! $left || ! $right) {
            return false;
        }

        return Carbon::parse($left)->format('Y-m-d H:i:s') === Carbon::parse($right)->format('Y-m-d H:i:s');
    }
}
