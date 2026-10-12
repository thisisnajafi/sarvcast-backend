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

    public function viewActivity(User $user, SchoolManagerProfile $profile): Response
    {
        return $this->allows($user, $profile, 'school_panel.activity.view', 'مشاهدهٔ فعالیت این پنل مجاز نیست.');
    }

    public function viewSubscriptions(User $user, SchoolManagerProfile $profile): Response
    {
        return $this->allows($user, $profile, 'school_panel.subscriptions.view', 'مشاهدهٔ اشتراک این پنل مجاز نیست.');
    }

    public function purchase(User $user, SchoolManagerProfile $profile): Response
    {
        return $this->allows($user, $profile, 'school_panel.subscriptions.purchase', 'خرید اشتراک فقط برای مدیر همین پنل مجاز است.');
    }

    private function allows(User $user, SchoolManagerProfile $profile, string $permission, string $message): Response
    {
        if (! $this->ownsActivePanel($user, $profile)) {
            return Response::deny($message);
        }

        return $user->hasPermission($permission)
            ? Response::allow()
            : Response::deny($message);
    }

    private function ownsActivePanel(User $user, SchoolManagerProfile $profile): bool
    {
        return $user->isSchoolManager()
            && (int) $profile->user_id === (int) $user->id
            && $profile->status === SchoolManagerProfile::STATUS_ACTIVE;
    }
}
