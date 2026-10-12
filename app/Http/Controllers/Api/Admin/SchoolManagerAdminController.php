<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\SchoolPanelException;
use App\Http\Controllers\Concerns\RespondsWithSchoolPanel;
use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Services\SchoolManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolManagerAdminController extends Controller
{
    use RespondsWithSchoolPanel;

    public function __construct(private readonly SchoolManagerService $schoolManagers) {}

    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessSchool('viewAny', SchoolManagerProfile::class)) {
            return $denied;
        }

        $query = SchoolManagerProfile::query()
            ->with('user:id,first_name,last_name,phone_number,status,role')
            ->withCount(['memberships as active_member_count' => function ($memberships) {
                $memberships->active();
            }]);

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

        $profile->loadCount(['memberships as active_member_count' => function ($memberships) {
            $memberships->active();
        }]);

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
}
