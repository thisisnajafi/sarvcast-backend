<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ActivityLogService;

class UserActivityLogObserver
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function created(User $user): void
    {
        $this->activityLog->recordModelChange($user, 'created');
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('status') && in_array($user->status, ['banned', 'suspended', 'inactive'], true)) {
            $this->activityLog->recordModelChange($user, 'banned');

            return;
        }

        if ($user->wasChanged('role')) {
            $this->activityLog->recordModelChange($user, 'role_assigned');

            return;
        }

        $this->activityLog->recordModelChange($user, 'updated');
    }

    public function deleted(User $user): void
    {
        $this->activityLog->recordModelChange($user, 'deleted');
    }
}
