<?php

namespace App\Policies;

use App\Models\SchoolManagerProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchoolManagerProfilePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('فقط مدیر کل می‌تواند مدیران مدرسه را ببیند.');
    }

    public function view(User $user, SchoolManagerProfile $profile): Response
    {
        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        if ($user->isSchoolManager() && (int) $user->id === (int) $profile->user_id) {
            return Response::allow();
        }

        return Response::deny('دسترسی به این پنل مجاز نیست.');
    }

    public function create(User $user): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('فقط مدیر کل می‌تواند مدیر مدرسه بسازد.');
    }

    public function update(User $user, SchoolManagerProfile $profile): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('فقط مدیر کل می‌تواند سقف اعضا و وضعیت پنل را تغییر دهد.');
    }

    public function delete(User $user, SchoolManagerProfile $profile): Response
    {
        return $user->isSuperAdmin()
            ? Response::allow()
            : Response::deny('فقط مدیر کل می‌تواند پنل مدرسه را ببندد.');
    }
}
