<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\SchoolPanelException;
use App\Http\Controllers\Concerns\RespondsWithSchoolPanel;
use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\SchoolPremiumGrant;
use App\Models\SchoolPremiumWindow;
use App\Services\SchoolManagerService;
use App\Services\SchoolPremiumGrantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolManagerAdminController extends Controller
{
    use RespondsWithSchoolPanel;

    public function __construct(
        private readonly SchoolManagerService $schoolManagers,
        private readonly SchoolPremiumGrantService $premiumGrants,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('viewAny', SchoolManagerProfile::class)) {
            return $denied;
        }

        $query = $this->profileQuery();

        $status = $request->string('status')->toString();
        if ($status !== '') {
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', SchoolManagerProfile::STATUS_DELETED);
        }

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function ($inner) use ($search) {
                $inner->where('label', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($users) use ($search) {
                        $users->where('phone_number', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%');
                    });
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginator = $query->orderByDesc('id')->paginate($perPage);
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (SchoolManagerProfile $profile) => $this->schoolManagers->profilePayload($profile)),
        );

        return AdminApiResponse::paginated($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('create', SchoolManagerProfile::class)) {
            return $denied;
        }

        $data = $request->validate([
            'phone_number' => ['required', 'string', 'regex:'.SchoolManagerService::PHONE_PATTERN],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'member_limit' => ['required', 'integer', 'min:0', 'max:100000'],
            'label' => ['nullable', 'string', 'max:120'],
        ], [
            'phone_number.required' => 'شماره موبایل را وارد کنید.',
            'phone_number.regex' => 'شماره باید کامل و به شکل 09xxxxxxxxx باشد.',
            'member_limit.required' => 'سقف اعضا را وارد کنید.',
            'member_limit.min' => 'سقف اعضا نمی‌تواند منفی باشد.',
        ]);

        try {
            $profile = $this->schoolManagers->createOrPromote($request->user(), $data);
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success(
            $this->schoolManagers->profilePayload($profile),
            'مدیر مدرسه ذخیره شد.',
            201,
        );
    }

    public function show(SchoolManagerProfile $profile): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('view', $profile)) {
            return $denied;
        }

        $this->loadProfileCounts($profile);

        return AdminApiResponse::success($this->schoolManagers->profilePayload($profile));
    }

    public function update(Request $request, SchoolManagerProfile $profile): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('update', $profile)) {
            return $denied;
        }

        $data = $request->validate([
            'member_limit' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000'],
            'label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', 'in:active,suspended'],
            'premium_grant_expires_at' => ['sometimes', 'nullable', 'date'],
            'allowed_grant_plan_ids' => ['sometimes', 'nullable', 'array'],
            'allowed_grant_plan_ids.*' => ['integer', 'exists:subscription_plans,id'],
        ], [
            'member_limit.min' => 'سقف اعضا نمی‌تواند منفی باشد.',
            'status.in' => 'وضعیت باید فعال یا معلق باشد.',
        ]);

        try {
            $profile = $this->schoolManagers->updateProfile($profile, $data);
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success(
            $this->schoolManagers->profilePayload($profile),
            'مدیر مدرسه به‌روزرسانی شد.',
        );
    }

    public function destroy(Request $request, SchoolManagerProfile $profile): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('delete', $profile)) {
            return $denied;
        }

        try {
            $this->schoolManagers->closeProfile($profile, $request->user());
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::okMessage('پنل مدیر مدرسه بسته شد.');
    }

    public function members(Request $request, SchoolManagerProfile $profile): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('viewAny', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        return $this->memberPage($request, $profile);
    }

    public function premiumOverview(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('viewAny', SchoolManagerProfile::class)) {
            return $denied;
        }

        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $paginator = $this->profileQuery()->orderByDesc('id')->paginate($perPage);
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (SchoolManagerProfile $profile) => $this->schoolManagers->profilePayload($profile)),
        );

        return AdminApiResponse::paginated($paginator);
    }

    public function storeWindow(Request $request, SchoolManagerProfile $profile): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('update', [SchoolPremiumWindow::class, $profile])) {
            return $denied;
        }

        try {
            $this->premiumGrants->replaceWindow($profile, $request->user(), $this->validateWindow($request, true));
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success(
            $this->premiumGrants->windowPayload($profile->fresh()),
            'پنجرهٔ پریمیوم رایگان ذخیره شد.',
            201,
        );
    }

    public function updateWindow(Request $request, SchoolManagerProfile $profile): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('update', [SchoolPremiumWindow::class, $profile])) {
            return $denied;
        }

        $data = $this->validateWindow($request, false);
        if ($data === []) {
            return response()->json([
                'success' => false,
                'message' => 'چیزی برای به‌روزرسانی ارسال نشده است.',
                'error' => 'nothing_to_update',
            ], 422);
        }

        try {
            $this->premiumGrants->adjustWindow($profile, $data);
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success(
            $this->premiumGrants->windowPayload($profile->fresh()),
            'پنجرهٔ پریمیوم رایگان به‌روزرسانی شد.',
        );
    }

    public function regrantWindow(Request $request, SchoolManagerProfile $profile): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('update', [SchoolPremiumWindow::class, $profile])) {
            return $denied;
        }

        try {
            $this->premiumGrants->replaceWindow($profile, $request->user(), $this->validateWindow($request, true));
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success(
            $this->premiumGrants->windowPayload($profile->fresh()),
            'پنجرهٔ تازهٔ پریمیوم رایگان باز شد.',
            201,
        );
    }

    public function grants(Request $request, SchoolManagerProfile $profile): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('viewAny', [SchoolPremiumGrant::class, $profile])) {
            return $denied;
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        return AdminApiResponse::paginated($this->premiumGrants->history($profile, $perPage));
    }

    public function revokeGrant(Request $request, SchoolPremiumGrant $grant): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('revoke', $grant)) {
            return $denied;
        }

        try {
            $payload = $this->premiumGrants->revoke($grant, $request->user());
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success($payload, 'پریمیوم رایگان لغو شد.');
    }

    private function memberPage(Request $request, SchoolManagerProfile $profile): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginator = SchoolMembership::query()
            ->where('school_manager_profile_id', $profile->id)
            ->active()
            ->with('user:id,first_name,last_name,phone_number,status')
            ->orderByDesc('id')
            ->paginate($perPage);

        $payloads = $this->schoolManagers->memberPayloads($paginator->getCollection());
        $paginator->setCollection(collect($payloads));

        return AdminApiResponse::paginated($paginator);
    }

    private function profileQuery()
    {
        return SchoolManagerProfile::query()
            ->with('user:id,first_name,last_name,phone_number,status,role')
            ->with(['latestWindow' => function ($windows) {
                $windows->withCount('grants');
            }])
            ->withCount([
                'memberships as active_member_count' => function ($memberships) {
                    $memberships->active();
                },
                'grants as active_grant_count' => function ($grants) {
                    $grants->where('status', SchoolPremiumGrant::STATUS_ACTIVE);
                },
            ]);
    }

    private function loadProfileCounts(SchoolManagerProfile $profile): void
    {
        $profile->load(['latestWindow' => function ($windows) {
            $windows->withCount('grants');
        }]);
        $profile->loadCount([
            'memberships as active_member_count' => function ($memberships) {
                $memberships->active();
            },
            'grants as active_grant_count' => function ($grants) {
                $grants->where('status', SchoolPremiumGrant::STATUS_ACTIVE);
            },
        ]);
    }

    /**
     * @return array{duration_months?: int, seat_limit?: int, expires_at?: string, notes?: string|null}
     */
    private function validateWindow(Request $request, bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';
        $expires = $required
            ? [$presence, 'date', 'after:now']
            : ['sometimes', 'date'];

        $data = $request->validate([
            'duration_months' => [$presence, 'integer', 'between:1,12'],
            'seat_limit' => [$presence, 'integer', 'min:0', 'max:100000'],
            'expires_at' => $expires,
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'duration_months.required' => 'مدت اشتراک رایگان را به ماه وارد کنید.',
            'duration_months.between' => 'مدت باید بین ۱ تا ۱۲ ماه باشد.',
            'seat_limit.required' => 'تعداد صندلی رایگان را وارد کنید.',
            'seat_limit.min' => 'تعداد صندلی نمی‌تواند منفی باشد.',
            'expires_at.required' => 'تاریخ پایان پنجره را وارد کنید.',
            'expires_at.after' => 'تاریخ پایان پنجره باید در آینده باشد.',
            'expires_at.date' => 'تاریخ پایان پنجره معتبر نیست.',
        ]);

        if (! $required && array_key_exists('notes', $data) && $data['notes'] === '') {
            $data['notes'] = null;
        }

        return $data;
    }
}
