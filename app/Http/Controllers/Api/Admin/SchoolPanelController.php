<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\SchoolPanelException;
use App\Http\Controllers\Concerns\RespondsWithSchoolPanel;
use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SchoolManagerProfile;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SchoolManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolPanelController extends Controller
{
    use RespondsWithSchoolPanel;

    public function __construct(private readonly SchoolManagerService $schoolManagers) {}

    public function me(Request $request): JsonResponse
    {
        $profile = $this->profile($request);
        if ($denied = $this->denyUnlessSchool('view', $profile)) {
            return $denied;
        }

        $profile->loadCount(['memberships as active_member_count' => function ($memberships) {
            $memberships->active();
        }]);

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

    private function profile(Request $request): SchoolManagerProfile
    {
        $profile = $request->attributes->get('schoolManagerProfile');
        if (! $profile instanceof SchoolManagerProfile) {
            abort(403, 'پنل مدرسه فعال نیست.');
        }

        return $profile;
    }
}
