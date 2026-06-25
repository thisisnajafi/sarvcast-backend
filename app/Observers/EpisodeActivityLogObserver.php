<?php

namespace App\Observers;

use App\Models\Episode;
use App\Services\ActivityLogService;

class EpisodeActivityLogObserver
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function created(Episode $episode): void
    {
        $this->activityLog->recordModelChange($episode, 'created');
    }

    public function updated(Episode $episode): void
    {
        $this->activityLog->recordModelChange($episode, 'updated');
    }

    public function deleted(Episode $episode): void
    {
        $this->activityLog->recordModelChange($episode, 'deleted');
    }
}
