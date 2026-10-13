<?php

namespace App\Services;

use App\Models\SchoolLicense;
use App\Models\SchoolPartnership;
use App\Models\StudentLicense;
use App\Models\TeacherAccount;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Central entitlement for partnership school / teacher student licenses.
 * Distinct from school_manager product grants (SchoolPremiumGrantService).
 */
class SchoolLicenseEntitlementService
{
    /**
     * @return array{
     *     has_premium: bool,
     *     source: string|null,
     *     reason: string|null,
     *     expires_at: string|null,
     *     school_partnership_id: int|null,
     *     license_id: int|null,
     *     teacher_account_id: int|null
     * }
     */
    public function resolveForUser(int $userId): array
    {
        $empty = [
            'has_premium' => false,
            'source' => null,
            'reason' => null,
            'expires_at' => null,
            'school_partnership_id' => null,
            'license_id' => null,
            'teacher_account_id' => null,
        ];

        if (! User::query()->whereKey($userId)->exists()) {
            return $empty;
        }

        $school = $this->resolveSchoolLicensePremium($userId);
        if ($school['has_premium']) {
            return $school;
        }

        $student = $this->resolveStudentLicensePremium($userId);
        if ($student['has_premium']) {
            return $student;
        }

        return $empty;
    }

    public function userHasLicensePremium(int $userId): bool
    {
        return $this->resolveForUser($userId)['has_premium'];
    }

    /**
     * Dashboard payload for a school partnership detail page.
     *
     * @return array{
     *     member_limit: int,
     *     active_license_count: int,
     *     premium_seat_count: int,
     *     over_limit: bool,
     *     licenses: list<array<string, mixed>>
     * }
     */
    public function summarizePartnership(SchoolPartnership $school): array
    {
        $limit = max(0, (int) $school->max_student_capacity);
        $licenses = SchoolLicense::query()
            ->where('school_partnership_id', $school->id)
            ->with('user:id,first_name,last_name,phone_number')
            ->orderBy('id')
            ->get();

        $activeInDate = $licenses->filter(fn (SchoolLicense $license) => $this->licenseWithinDates($license));
        $seatIds = $this->seatHoldingLicenseIds($school->id, $limit);

        $rows = $licenses->map(function (SchoolLicense $license) use ($seatIds, $school) {
            $withinDates = $this->licenseWithinDates($license);
            $grantsPremium = $withinDates
                && $license->status === 'active'
                && (bool) $license->is_activated
                && $seatIds->contains($license->id)
                && $this->partnershipAllowsPremium($school);

            return [
                'id' => $license->id,
                'user_id' => $license->user_id,
                'user_name' => trim(($license->user->first_name ?? '').' '.($license->user->last_name ?? '')),
                'phone_number' => $license->user->phone_number ?? null,
                'license_type' => $license->license_type,
                'status' => $license->status,
                'is_activated' => (bool) $license->is_activated,
                'start_date' => optional($license->start_date)->toDateString(),
                'end_date' => optional($license->end_date)->toDateString(),
                'within_dates' => $withinDates,
                'grants_premium' => $grantsPremium,
                'denied_reason' => $grantsPremium ? null : $this->licenseDeniedReason($license, $school, $seatIds),
            ];
        })->values()->all();

        $premiumSeatCount = collect($rows)->where('grants_premium', true)->count();

        return [
            'member_limit' => $limit,
            'active_license_count' => $activeInDate->where('status', 'active')->where('is_activated', true)->count(),
            'premium_seat_count' => $premiumSeatCount,
            'over_limit' => $limit > 0 && $activeInDate->where('status', 'active')->where('is_activated', true)->count() > $limit,
            'licenses' => $rows,
        ];
    }

    /**
     * @return array{
     *     has_premium: bool,
     *     source: string|null,
     *     reason: string|null,
     *     expires_at: string|null,
     *     school_partnership_id: int|null,
     *     license_id: int|null,
     *     teacher_account_id: int|null
     * }
     */
    private function resolveSchoolLicensePremium(int $userId): array
    {
        $licenses = SchoolLicense::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('is_activated', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->with('schoolPartnership')
            ->orderBy('id')
            ->get();

        foreach ($licenses as $license) {
            $school = $license->schoolPartnership;
            if (! $school || ! $this->partnershipAllowsPremium($school)) {
                continue;
            }

            $limit = max(0, (int) $school->max_student_capacity);
            if ($limit <= 0) {
                continue;
            }

            $seatIds = $this->seatHoldingLicenseIds($school->id, $limit);
            if (! $seatIds->contains($license->id)) {
                continue;
            }

            return [
                'has_premium' => true,
                'source' => 'school_license',
                'reason' => 'school_license',
                'expires_at' => optional($license->end_date)->toDateString(),
                'school_partnership_id' => $school->id,
                'license_id' => $license->id,
                'teacher_account_id' => null,
            ];
        }

        return [
            'has_premium' => false,
            'source' => null,
            'reason' => null,
            'expires_at' => null,
            'school_partnership_id' => null,
            'license_id' => null,
            'teacher_account_id' => null,
        ];
    }

    /**
     * @return array{
     *     has_premium: bool,
     *     source: string|null,
     *     reason: string|null,
     *     expires_at: string|null,
     *     school_partnership_id: int|null,
     *     license_id: int|null,
     *     teacher_account_id: int|null
     * }
     */
    private function resolveStudentLicensePremium(int $userId): array
    {
        $licenses = StudentLicense::query()
            ->where('student_user_id', $userId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->with('teacherAccount')
            ->orderBy('id')
            ->get();

        foreach ($licenses as $license) {
            $teacher = $license->teacherAccount;
            if (! $teacher || ! $teacher->isActive()) {
                continue;
            }

            $limit = max(0, (int) $teacher->max_student_licenses);
            if ($limit <= 0) {
                continue;
            }

            $seatIds = $this->teacherSeatHoldingLicenseIds($teacher->id, $limit);
            if (! $seatIds->contains($license->id)) {
                continue;
            }

            return [
                'has_premium' => true,
                'source' => 'student_license',
                'reason' => 'student_license',
                'expires_at' => optional($license->end_date)->toDateString(),
                'school_partnership_id' => null,
                'license_id' => $license->id,
                'teacher_account_id' => $teacher->id,
            ];
        }

        return [
            'has_premium' => false,
            'source' => null,
            'reason' => null,
            'expires_at' => null,
            'school_partnership_id' => null,
            'license_id' => null,
            'teacher_account_id' => null,
        ];
    }

    private function partnershipAllowsPremium(SchoolPartnership $school): bool
    {
        if ($school->status !== 'active') {
            return false;
        }

        if ($school->partnership_start_date && $school->partnership_start_date->gt(now()->startOfDay())) {
            return false;
        }

        if ($school->partnership_end_date && $school->partnership_end_date->lt(now()->startOfDay())) {
            return false;
        }

        return true;
    }

    private function licenseWithinDates(SchoolLicense $license): bool
    {
        if (! $license->start_date || ! $license->end_date) {
            return false;
        }

        $today = now()->startOfDay();

        return $license->start_date->lte($today) && $license->end_date->gte($today);
    }

    /**
     * First N active in-date licenses (by id) hold premium seats.
     *
     * @return Collection<int, int>
     */
    private function seatHoldingLicenseIds(int $schoolPartnershipId, int $limit): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        return SchoolLicense::query()
            ->where('school_partnership_id', $schoolPartnershipId)
            ->where('status', 'active')
            ->where('is_activated', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');
    }

    /**
     * @return Collection<int, int>
     */
    private function teacherSeatHoldingLicenseIds(int $teacherAccountId, int $limit): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        return StudentLicense::query()
            ->where('teacher_account_id', $teacherAccountId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $seatIds
     */
    private function licenseDeniedReason(SchoolLicense $license, SchoolPartnership $school, Collection $seatIds): string
    {
        if ($license->status === 'cancelled') {
            return 'removed';
        }
        if ($license->status === 'expired' || ($license->end_date && $license->end_date->lt(now()->startOfDay()))) {
            return 'expired';
        }
        if (! $license->is_activated || $license->status !== 'active') {
            return 'inactive';
        }
        if ($license->start_date && $license->start_date->gt(now()->startOfDay())) {
            return 'not_started';
        }
        if (! $this->partnershipAllowsPremium($school)) {
            return 'school_inactive';
        }
        if (! $seatIds->contains($license->id)) {
            return 'over_limit';
        }

        return 'unknown';
    }
}
