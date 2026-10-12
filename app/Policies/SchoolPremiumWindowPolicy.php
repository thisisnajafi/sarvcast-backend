<?php

namespace App\Policies;

use App\Models\SchoolManagerProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchoolPremiumWindowPolicy
{
    public function view(User $user, SchoolManagerProfile $profile): Response
    {
        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        if ($user->isSchoolManager()
            && (int) $user->id === (int) $profile->user_id
            && $profile->status === SchoolManagerProfile::STATUS_ACTIVE) {
            return Response::allow();
        }

        return Response::deny('مشاهدهٔ پنجرهٔ پریمیوم این پنل مجاز نیست.');
    }

    public function update(User $user, SchoolManagerProfile $profile): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('فقط مدیر کل می‌تواند پنجرهٔ پریمیوم رایگان را تنظیم کند.');
    }
}
