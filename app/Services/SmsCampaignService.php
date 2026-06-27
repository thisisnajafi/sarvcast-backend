<?php

namespace App\Services;

use App\Exceptions\InvalidSmsAudienceException;
use App\Jobs\ProcessSmsCampaign;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SmsCampaignService
{
    public function __construct(
        private readonly SmsAudienceBuilder $audienceBuilder,
        private readonly SmsParameterResolver $parameterResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): SmsCampaign
    {
        $validated = $this->validateCampaignData($data);

        return SmsCampaign::create([
            ...$validated,
            'status' => SmsCampaign::STATUS_DRAFT,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SmsCampaign $campaign, array $data): SmsCampaign
    {
        $this->assertDraft($campaign, 'فقط کمپین‌های پیش‌نویس قابل ویرایش هستند.');

        $merged = array_merge([
            'name' => $campaign->name,
            'sms_template_id' => $campaign->sms_template_id,
            'audience_type' => $campaign->audience_type,
            'audience_filters' => $campaign->audience_filters,
            'scheduled_at' => $campaign->scheduled_at,
            'notes' => $campaign->notes,
        ], $data);

        $validated = $this->validateCampaignData($merged, $campaign);

        $campaign->update($validated);

        return $campaign->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(SmsCampaign $campaign): array
    {
        $campaign->loadMissing('template');
        $this->assertTemplateActive($campaign);

        $resolved = $this->resolveAudience($campaign);
        /** @var Collection<int, array<string, mixed>> $recipients */
        $recipients = $resolved['recipients'];

        $sampleUsers = $recipients->take((int) config('sms.campaign.preview_sample_size', 5))
            ->map(function (array $row) use ($campaign) {
                $previewMessage = $this->parameterResolver->renderPreview(
                    $campaign->template->preview_text,
                    $row['resolved_parameters']
                );

                return [
                    'id' => $row['user_id'],
                    'full_name' => $row['user']?->full_name,
                    'phone_number' => $this->maskPhoneNumber($row['phone_number']),
                    'preview_message' => $previewMessage,
                ];
            })
            ->values()
            ->all();

        return [
            'total_recipients' => $recipients->count(),
            'sample_users' => $sampleUsers,
            'skipped_no_phone' => $resolved['skipped_no_phone'],
            'excluded_count' => $resolved['excluded_count'],
        ];
    }

    public function dispatch(SmsCampaign $campaign, User $actor): SmsCampaign
    {
        $this->assertDraft($campaign, 'فقط کمپین پیش‌نویس قابل ارسال است.');
        $campaign->loadMissing('template');
        $this->assertTemplateActive($campaign);
        $this->assertDailyDispatchLimit($actor);

        $preview = $this->preview($campaign);
        $totalRecipients = (int) $preview['total_recipients'];

        if ($totalRecipients === 0) {
            throw ValidationException::withMessages([
                'audience_type' => ['هیچ گیرنده معتبری برای این کمپین یافت نشد.'],
            ]);
        }

        $maxRecipients = (int) config('sms.campaign.max_recipients', 10000);
        if ($totalRecipients > $maxRecipients) {
            throw ValidationException::withMessages([
                'audience_type' => ["حداکثر {$maxRecipients} گیرنده در هر کمپین مجاز است."],
            ]);
        }

        $campaign->update([
            'status' => SmsCampaign::STATUS_QUEUED,
            'total_recipients' => $totalRecipients,
            'sent_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
            'started_at' => null,
            'completed_at' => null,
        ]);

        $job = ProcessSmsCampaign::dispatch($campaign->id)
            ->onQueue(config('sms.campaign.queue', 'sms'));

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            $job->delay($campaign->scheduled_at);
        }

        return $campaign->fresh(['template', 'creator']);
    }

    public function cancel(SmsCampaign $campaign): SmsCampaign
    {
        if (! $campaign->isCancellable()) {
            throw ValidationException::withMessages([
                'status' => ['فقط کمپین‌های در صف یا در حال ارسال قابل لغو هستند.'],
            ]);
        }

        DB::transaction(function () use ($campaign) {
            $skipped = $campaign->recipients()
                ->where('status', SmsCampaignRecipient::STATUS_PENDING)
                ->update([
                    'status' => SmsCampaignRecipient::STATUS_SKIPPED,
                    'error_message' => 'کمپین لغو شد.',
                ]);

            $campaign->update([
                'status' => SmsCampaign::STATUS_CANCELLED,
                'skipped_count' => $campaign->skipped_count + $skipped,
                'completed_at' => now(),
            ]);
        });

        return $campaign->fresh(['template', 'creator']);
    }

    public function buildRecipients(SmsCampaign $campaign): void
    {
        $campaign->loadMissing('template');
        $resolved = $this->resolveAudience($campaign);

        DB::transaction(function () use ($campaign, $resolved) {
            $campaign->recipients()->delete();

            /** @var Collection<int, array<string, mixed>> $recipients */
            $recipients = $resolved['recipients'];

            $campaign->update([
                'status' => SmsCampaign::STATUS_PROCESSING,
                'started_at' => now(),
                'total_recipients' => $recipients->count(),
                'skipped_count' => $resolved['skipped_no_phone'],
            ]);

            $recipients->chunk((int) config('sms.campaign.chunk_size', 50))->each(function (Collection $chunk) use ($campaign) {
                $rows = $chunk->map(fn (array $row) => [
                    'sms_campaign_id' => $campaign->id,
                    'user_id' => $row['user_id'],
                    'phone_number' => $row['phone_number'],
                    'resolved_parameters' => json_encode($row['resolved_parameters']),
                    'status' => SmsCampaignRecipient::STATUS_PENDING,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                SmsCampaignRecipient::insert($rows);
            });
        });
    }

    public function markCampaignCompletedIfDone(SmsCampaign $campaign): void
    {
        $campaign->refresh();

        if (! $campaign->isProcessing()) {
            return;
        }

        $pendingCount = $campaign->recipients()
            ->where('status', SmsCampaignRecipient::STATUS_PENDING)
            ->count();

        if ($pendingCount > 0) {
            return;
        }

        $failedCount = $campaign->recipients()
            ->where('status', SmsCampaignRecipient::STATUS_FAILED)
            ->count();

        $campaign->update([
            'status' => $failedCount === $campaign->total_recipients
                ? SmsCampaign::STATUS_FAILED
                : SmsCampaign::STATUS_COMPLETED,
            'failed_count' => $failedCount,
            'sent_count' => $campaign->recipients()->where('status', SmsCampaignRecipient::STATUS_SENT)->count(),
            'completed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateCampaignData(array $data, ?SmsCampaign $existing = null): array
    {
        $audienceType = (string) ($data['audience_type'] ?? $existing?->audience_type ?? '');

        if (! in_array($audienceType, SmsAudienceBuilder::VALID_TYPES, true)) {
            throw ValidationException::withMessages([
                'audience_type' => ['نوع مخاطب نامعتبر است.'],
            ]);
        }

        $templateId = (int) ($data['sms_template_id'] ?? $existing?->sms_template_id ?? 0);
        $template = SmsTemplate::find($templateId);

        if (! $template) {
            throw ValidationException::withMessages([
                'sms_template_id' => ['قالب پیامک یافت نشد.'],
            ]);
        }

        $filters = $data['audience_filters'] ?? $existing?->audience_filters ?? [];
        $this->validateAudienceFilters($audienceType, is_array($filters) ? $filters : []);

        return [
            'name' => (string) ($data['name'] ?? $existing?->name),
            'sms_template_id' => $template->id,
            'audience_type' => $audienceType,
            'audience_filters' => is_array($filters) ? $filters : [],
            'scheduled_at' => $data['scheduled_at'] ?? $existing?->scheduled_at,
            'notes' => $data['notes'] ?? $existing?->notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function validateAudienceFilters(string $audienceType, array $filters): void
    {
        try {
            if ($audienceType === SmsAudienceBuilder::TYPE_MANUAL_PHONES) {
                if (count($this->audienceBuilder->resolveManualPhones($filters)) === 0) {
                    throw ValidationException::withMessages([
                        'audience_filters.phone_numbers' => ['حداقل یک شماره موبایل معتبر وارد کنید.'],
                    ]);
                }

                return;
            }

            if ($audienceType === SmsAudienceBuilder::TYPE_ROLE_COLUMN && empty($filters['role'])) {
                throw ValidationException::withMessages([
                    'audience_filters.role' => ['انتخاب نقش کاربری الزامی است.'],
                ]);
            }

            if ($audienceType === SmsAudienceBuilder::TYPE_RBAC_ROLE) {
                $roleIds = $filters['role_ids'] ?? $filters['rbac_role_ids'] ?? [];
                if (count((array) $roleIds) === 0) {
                    throw ValidationException::withMessages([
                        'audience_filters.role_ids' => ['انتخاب حداقل یک نقش الزامی است.'],
                    ]);
                }
            }

            if ($audienceType === SmsAudienceBuilder::TYPE_SPECIFIC_USERS && count((array) ($filters['user_ids'] ?? [])) === 0) {
                throw ValidationException::withMessages([
                    'audience_filters.user_ids' => ['انتخاب حداقل یک کاربر الزامی است.'],
                ]);
            }

            $this->audienceBuilder->buildQuery($audienceType, $filters);
        } catch (InvalidSmsAudienceException $e) {
            throw ValidationException::withMessages([
                'audience_type' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * @return array{recipients: Collection<int, array<string, mixed>>, skipped_no_phone: int, excluded_count: int}
     */
    private function resolveAudience(SmsCampaign $campaign): array
    {
        $campaign->loadMissing('template');
        $template = $campaign->template;
        $filters = $campaign->audience_filters ?? [];
        $overrides = $filters['static_parameter_overrides'] ?? [];

        if ($campaign->audience_type === SmsAudienceBuilder::TYPE_MANUAL_PHONES) {
            $phones = $this->audienceBuilder->resolveManualPhones($filters);

            return [
                'recipients' => collect($phones)->map(fn (string $phone) => [
                    'user_id' => null,
                    'phone_number' => $phone,
                    'resolved_parameters' => $this->parameterResolver->resolve(null, $template->parameters ?? [], $overrides),
                    'user' => null,
                ]),
                'skipped_no_phone' => 0,
                'excluded_count' => count($filters['exclude_user_ids'] ?? []),
            ];
        }

        $query = $this->audienceBuilder->buildQuery($campaign->audience_type, $filters);
        $users = $query->get(['id', 'first_name', 'last_name', 'phone_number']);

        $recipients = collect();
        $skippedNoPhone = 0;

        foreach ($users as $user) {
            $phone = $this->audienceBuilder->normalizePhone((string) $user->phone_number);

            if (! $this->audienceBuilder->isValidIranianMobile($phone)) {
                $skippedNoPhone++;
                continue;
            }

            $recipients->push([
                'user_id' => $user->id,
                'phone_number' => $phone,
                'resolved_parameters' => $this->parameterResolver->resolve($user, $template->parameters ?? [], $overrides),
                'user' => $user,
            ]);
        }

        return [
            'recipients' => $recipients,
            'skipped_no_phone' => $skippedNoPhone,
            'excluded_count' => count($filters['exclude_user_ids'] ?? []),
        ];
    }

    private function assertDraft(SmsCampaign $campaign, string $message): void
    {
        if (! $campaign->isDraft()) {
            throw ValidationException::withMessages([
                'status' => [$message],
            ]);
        }
    }

    private function assertTemplateActive(SmsCampaign $campaign): void
    {
        if (! $campaign->template?->is_active) {
            throw ValidationException::withMessages([
                'sms_template_id' => ['قالب پیامک انتخاب‌شده غیرفعال است.'],
            ]);
        }
    }

    private function assertDailyDispatchLimit(User $actor): void
    {
        $limit = (int) config('sms.campaign.daily_dispatch_limit', 3);

        if ($limit <= 0 || $actor->isSuperAdmin()) {
            return;
        }

        $todayCount = SmsCampaign::query()
            ->where('created_by', $actor->id)
            ->whereDate('updated_at', today())
            ->whereNotIn('status', [SmsCampaign::STATUS_DRAFT])
            ->count();

        if ($todayCount >= $limit) {
            throw ValidationException::withMessages([
                'dispatch' => ["حداکثر {$limit} کمپین در روز قابل ارسال است."],
            ]);
        }
    }

    private function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }

        return substr($phone, 0, 4).'***'.substr($phone, -4);
    }
}
