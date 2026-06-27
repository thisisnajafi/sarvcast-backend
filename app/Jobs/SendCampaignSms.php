<?php

namespace App\Jobs;

use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Models\User;
use App\Services\SmsCampaignService;
use App\Services\SmsParameterResolver;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendCampaignSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public int $recipientId,
    ) {}

    public function handle(
        SmsService $smsService,
        SmsParameterResolver $parameterResolver,
        SmsCampaignService $campaignService,
    ): void {
        $recipient = SmsCampaignRecipient::query()
            ->with(['campaign.template'])
            ->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        $campaign = $recipient->campaign;

        if (! $campaign || $campaign->isCancelled()) {
            return;
        }

        if ($recipient->status !== SmsCampaignRecipient::STATUS_PENDING) {
            return;
        }

        $template = $campaign->template;

        if (! $template || ! $template->is_active) {
            $this->markFailed($recipient, $campaign, 'قالب پیامک در دسترس نیست.');

            return;
        }

        $user = $recipient->user_id ? User::find($recipient->user_id) : null;
        $parameters = is_array($recipient->resolved_parameters)
            ? $recipient->resolved_parameters
            : [];

        if ($parameters === [] && $user) {
            $overrides = $campaign->audience_filters['static_parameter_overrides'] ?? [];
            $parameters = app(SmsParameterResolver::class)->resolve(
                $user,
                $template->parameters ?? [],
                $overrides
            );
        }

        $preview = $parameterResolver->renderPreview($template->preview_text, $parameters);

        try {
            $result = $smsService->sendSmsWithTemplate(
                $recipient->phone_number,
                $template->melipayamak_body_id,
                $parameters,
                [
                    'sms_template_id' => $template->id,
                    'sms_campaign_id' => $campaign->id,
                    'user_id' => $recipient->user_id,
                    'preview_text' => $preview,
                    'message' => $preview,
                ]
            );

            if ($result['success'] ?? false) {
                DB::transaction(function () use ($recipient, $campaign, $result) {
                    $recipient->update([
                        'status' => SmsCampaignRecipient::STATUS_SENT,
                        'sms_log_id' => $result['sms_log_id'] ?? null,
                        'sent_at' => now(),
                    ]);

                    $campaign->increment('sent_count');
                });
            } else {
                $this->markFailed($recipient, $campaign, $result['error'] ?? 'ارسال ناموفق بود.');
            }
        } catch (\Throwable $e) {
            Log::error('SendCampaignSms failed', [
                'recipient_id' => $recipient->id,
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            $this->markFailed($recipient, $campaign, $e->getMessage());

            throw $e;
        } finally {
            $campaignService->markCampaignCompletedIfDone($campaign->fresh());
        }
    }

    private function markFailed(SmsCampaignRecipient $recipient, SmsCampaign $campaign, string $error): void
    {
        DB::transaction(function () use ($recipient, $campaign, $error) {
            $recipient->update([
                'status' => SmsCampaignRecipient::STATUS_FAILED,
                'error_message' => $error,
            ]);

            $campaign->increment('failed_count');
        });
    }
}
