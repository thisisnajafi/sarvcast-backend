<?php

namespace App\Observers;

use App\Models\MediaAsset;
use App\Services\ActivityLogService;

class MediaAssetActivityLogObserver
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function created(MediaAsset $mediaAsset): void
    {
        $this->activityLog->recordModelChange($mediaAsset, 'uploaded');
    }

    public function updated(MediaAsset $mediaAsset): void
    {
        $action = $mediaAsset->wasChanged(['original_name', 'title']) ? 'renamed' : 'updated';
        $this->activityLog->recordModelChange($mediaAsset, $action);
    }

    public function deleted(MediaAsset $mediaAsset): void
    {
        $this->activityLog->recordModelChange($mediaAsset, 'deleted');
    }
}
