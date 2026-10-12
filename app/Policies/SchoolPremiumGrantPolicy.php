<?php

namespace App\Policies;

use App\Models\SchoolManagerProfile;
use App\Models\SchoolPremiumGrant;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchoolPremiumGrantPolicy
{
    public function viewAny(User $user, SchoolManagerProfile $profile): Response
    {
        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        return $user->isSchoolManager() && (int) $user->id === (int) $profile->user_id
            ? Response::allow()
            : Response::deny('تاریخچهٔ اعطای این پنل مجاز نیست.');
    }

    public function create(User $user, SchoolManagerProfile $profile): Response
    {
        $ownsPanel = $user->isSchoolManager()
            && (int) $profile->user_id === (int) $user->id
            && $profile->status === SchoolManagerProfile::STATUS_ACTIVE;

        if (! $ownsPanel || ! $user->hasPermission('school_panel.premium.grant')) {
            return Response::deny('اعطای پریمیوم رایگان فقط برای مدیر همین پنل مجاز است.');
        }

        return Response::allow();
    }

    public function revoke(User $user, SchoolPremiumGrant $grant): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('فقط مدیر کل می‌تواند پریمیوم رایگان را لغو کند.');
    }
}
