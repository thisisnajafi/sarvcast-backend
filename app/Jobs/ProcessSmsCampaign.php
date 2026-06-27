<?php

namespace App\Jobs;

use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Services\SmsCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSmsCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public int $campaignId,
    ) {}

    public function handle(SmsCampaignService $campaignService): void
    {
        $campaign = SmsCampaign::query()->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        if ($campaign->isCancelled()) {
            return;
        }

        if (! in_array($campaign->status, [SmsCampaign::STATUS_QUEUED, SmsCampaign::STATUS_PROCESSING], true)) {
            Log::warning('ProcessSmsCampaign skipped due to unexpected status', [
                'campaign_id' => $campaign->id,
                'status' => $campaign->status,
            ]);

            return;
        }

        try {
            $campaignService->buildRecipients($campaign);

            $campaign->refresh();

            if ($campaign->isCancelled()) {
                return;
            }

            $delayMs = (int) config('sms.campaign.send_delay_ms', 200);
            $index = 0;

            $campaign->recipients()
                ->where('status', SmsCampaignRecipient::STATUS_PENDING)
                ->orderBy('id')
                ->pluck('id')
                ->each(function (int $recipientId) use (&$index, $delayMs) {
                    $job = SendCampaignSms::dispatch($recipientId)
                        ->onQueue(config('sms.campaign.queue', 'sms'));

                    if ($delayMs > 0 && $index > 0) {
                        $job->delay(now()->addMilliseconds($index * $delayMs));
                    }

                    $index++;
                });

            if ($index === 0) {
                $campaign->update([
                    'status' => SmsCampaign::STATUS_FAILED,
                    'completed_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('ProcessSmsCampaign failed', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
            ]);

            $campaign->update([
                'status' => SmsCampaign::STATUS_FAILED,
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
