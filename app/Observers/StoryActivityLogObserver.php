<?php

namespace App\Observers;

use App\Models\Story;
use App\Services\ActivityLogService;

class StoryActivityLogObserver
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function created(Story $story): void
    {
        $this->activityLog->recordModelChange($story, 'created');
    }

    public function updated(Story $story): void
    {
        if ($story->wasChanged('status') && $story->status === 'published') {
            $this->activityLog->recordModelChange($story, 'published');

            return;
        }

        $this->activityLog->recordModelChange($story, 'updated');
    }

    public function deleted(Story $story): void
    {
        $this->activityLog->recordModelChange($story, 'deleted');
    }
}
