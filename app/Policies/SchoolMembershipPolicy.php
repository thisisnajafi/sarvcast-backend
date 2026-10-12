<?php

namespace App\Policies;

use App\Models\SchoolManagerProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchoolMembershipPolicy
{
    public function viewAny(User $user, SchoolManagerProfile $profile): Response
    {
        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        return $this->ownsActivePanel($user, $profile)
            ? Response::allow()
            : Response::deny('مشاهدهٔ اعضای این پنل مجاز نیست.');
    }

    public function add(User $user, SchoolManagerProfile $profile): Response
    {
        return $this->ownsActivePanel($user, $profile)
            ? Response::allow()
            : Response::deny('افزودن عضو فقط برای مدیر همین پنل مجاز است.');
    }

    public function remove(User $user, SchoolManagerProfile $profile): Response
    {
        return $this->ownsActivePanel($user, $profile)
            ? Response::allow()
            : Response::deny('حذف عضو فقط برای مدیر همین پنل مجاز است.');
    }

    private function ownsActivePanel(User $user, SchoolManagerProfile $profile): bool
    {
        return $user->isSchoolManager()
            && (int) $profile->user_id === (int) $user->id
            && $profile->status === SchoolManagerProfile::STATUS_ACTIVE;
    }
}
