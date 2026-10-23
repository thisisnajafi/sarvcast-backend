<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\SchoolPanelException;
use App\Http\Controllers\Concerns\RespondsWithSchoolPanel;
use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Models\SchoolPremiumGrant;
use App\Services\SchoolDashboardService;
use App\Services\SchoolManagerService;
use App\Services\SchoolPremiumGrantService;
use App\Services\SchoolSubscriptionCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolPanelController extends Controller
{
    use RespondsWithSchoolPanel;

    public function __construct(
        private readonly SchoolManagerService $schoolManagers,
        private readonly SchoolSubscriptionCheckoutService $checkout,
        private readonly SchoolPremiumGrantService $premiumGrants,
        private readonly SchoolDashboardService $dashboard,
    ) {}

    public function me(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('view', $profile)) {
            return $denied;
        }

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

        return AdminApiResponse::success($this->schoolManagers->profilePayload($profile));
    }

    public function lookup(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewAny', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        $data = $request->validate([
            'phone_number' => ['required', 'string', 'regex:'.SchoolManagerService::PHONE_PATTERN],
        ], [
            'phone_number.required' => 'شماره موبایل را کامل وارد کنید.',
            'phone_number.regex' => 'شماره باید کامل و به شکل 09xxxxxxxxx باشد.',
        ]);

        try {
            $payload = $this->schoolManagers->lookup($profile, $data['phone_number']);
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success($payload, 'کاربر پیدا شد.');
    }

    public function members(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewAny', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $paginator = SchoolMembership::query()
            ->where('school_manager_profile_id', $profile->id)
            ->active()
            ->with('user:id,first_name,last_name,phone_number,status')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->setCollection(collect($this->schoolManagers->memberPayloads($paginator->getCollection())));

        return AdminApiResponse::paginated($paginator);
    }

    public function storeMember(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('add', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ], [
            'user_id.required' => 'ابتدا کاربر را با شماره پیدا کنید.',
        ]);

        try {
            $membership = $this->schoolManagers->attach($profile, $request->user(), (int) $data['user_id']);
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        $membership->load('user:id,first_name,last_name,phone_number,status');
        $payload = $this->schoolManagers->memberPayloads(collect([$membership]))[0] ?? null;

        return AdminApiResponse::success($payload, 'کاربر به پنل اضافه شد.', 201);
    }

    public function destroyMember(Request $request, User $user): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('remove', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        try {
            $this->schoolManagers->detach($profile, $request->user(), $user);
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::okMessage('کاربر از پنل خارج شد.');
    }

    public function storePremiumGrant(Request $request, int $user): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('create', [SchoolPremiumGrant::class, $profile])) {
            return $denied;
        }

        $data = $request->validate([
            'plan_id' => ['nullable', 'integer'],
        ]);

        try {
            $payload = $this->premiumGrants->grant(
                $profile,
                $request->user(),
                $user,
                isset($data['plan_id']) ? (int) $data['plan_id'] : null,
            );
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success($payload, 'اشتراک رایگان فعال شد.', 201);
    }

    public function dashboardStats(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewDashboard', $profile)) {
            return $denied;
        }

        return AdminApiResponse::success($this->dashboard->stats($profile), 'آمار داشبورد مدرسه بارگذاری شد.');
    }

    public function dashboardCharts(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewDashboard', $profile)) {
            return $denied;
        }

        return AdminApiResponse::success($this->dashboard->charts($profile), 'نمودار داشبورد مدرسه بارگذاری شد.');
    }

    public function dashboardOnlineUsers(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewDashboard', $profile)) {
            return $denied;
        }

        $count = $this->dashboard->onlineCount($profile);

        return response()->json([
            'success' => true,
            'message' => 'تعداد کاربران آنلاین بارگذاری شد.',
            'count' => $count,
            'data' => [
                'count' => $count,
                'window_minutes' => SchoolDashboardService::ONLINE_WINDOW_MINUTES,
            ],
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewActivity', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        return AdminApiResponse::success($this->schoolManagers->cohortActivity($profile));
    }

    public function plans(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewSubscriptions', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        return AdminApiResponse::success($this->checkout->plans());
    }

    public function memberActivity(Request $request, int $user): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewActivity', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        try {
            $payload = $this->schoolManagers->memberActivity($profile, $user, $perPage);
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success($payload);
    }

    public function memberSubscriptions(Request $request, int $user): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('viewSubscriptions', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        try {
            $payload = $this->schoolManagers->memberSubscriptions($profile, $user);
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        return AdminApiResponse::success($payload);
    }

    public function storeSubscription(Request $request, int $user): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('purchase', [SchoolMembership::class, $profile])) {
            return $denied;
        }

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ], [
            'plan_id.required' => 'پلن اشتراک را انتخاب کنید.',
            'plan_id.exists' => 'پلن اشتراک یافت نشد.',
            'coupon_code.max' => 'کد کوپن نمی‌تواند بیشتر از ۵۰ کاراکتر باشد.',
        ]);

        try {
            $payload = $this->checkout->start(
                $profile,
                $request->user(),
                $user,
                (int) $data['plan_id'],
                $data['coupon_code'] ?? null,
            );
        } catch (SchoolPanelException $exception) {
            return $this->schoolError($exception);
        }

        $status = ($payload['reused'] ?? false) ? 200 : 201;

        return AdminApiResponse::success(
            $payload,
            $status === 201
                ? 'اشتراک ایجاد شد. لطفاً پرداخت را تکمیل کنید.'
                : 'پرداخت قبلی هنوز باز است. همان لینک را ادامه دهید.',
            $status,
        );
    }

    private function profile(Request $request): SchoolManagerProfile
    {
        $profile = $request->attributes->get('schoolManagerProfile');
        if (! $profile instanceof SchoolManagerProfile) {
            abort(403, 'پنل مدرسه فعال نیست.');
        }

        return $profile;
    }
}
