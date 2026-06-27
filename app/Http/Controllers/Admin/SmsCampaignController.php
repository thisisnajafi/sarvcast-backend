<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SmsCampaign;
use App\Services\SmsAudienceBuilder;
use App\Services\SmsCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsCampaignController extends Controller
{
    public function __construct(
        private readonly SmsCampaignService $campaignService,
    ) {}

    public function apiIndex(Request $request): JsonResponse
    {
        $query = SmsCampaign::query()
            ->with(['template:id,name,melipayamak_body_id', 'creator:id,first_name,last_name']);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('audience_type')) {
            $query->where('audience_type', $request->input('audience_type'));
        }

        if ($request->filled('sms_template_id')) {
            $query->where('sms_template_id', (int) $request->input('sms_template_id'));
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSort = ['name', 'status', 'total_recipients', 'sent_count', 'created_at', 'started_at'];

        if (in_array($sortBy, $allowedSort, true)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $page = max((int) $request->input('page', 1), 1);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $paginator->getCollection()->transform(fn (SmsCampaign $campaign) => $this->formatCampaign($campaign));

        return AdminApiResponse::paginated($paginator);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sms_template_id' => 'required|integer|exists:sms_templates,id',
            'audience_type' => 'required|string|in:'.implode(',', SmsAudienceBuilder::VALID_TYPES),
            'audience_filters' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:2000',
        ]);

        $campaign = $this->campaignService->create($request->all(), auth('sanctum')->user());

        return AdminApiResponse::success(
            $this->formatCampaign($campaign->load(['template', 'creator'])),
            'کمپین پیامک با موفقیت ایجاد شد.',
            201
        );
    }

    public function apiShow(SmsCampaign $smsCampaign): JsonResponse
    {
        return AdminApiResponse::success(
            $this->formatCampaign(
                $smsCampaign->load(['template', 'creator'])
                    ->loadCount([
                        'recipients as pending_recipients_count' => fn ($q) => $q->where('status', 'pending'),
                        'recipients as sent_recipients_count' => fn ($q) => $q->where('status', 'sent'),
                        'recipients as failed_recipients_count' => fn ($q) => $q->where('status', 'failed'),
                    ])
            )
        );
    }

    public function apiUpdate(Request $request, SmsCampaign $smsCampaign): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sms_template_id' => 'sometimes|required|integer|exists:sms_templates,id',
            'audience_type' => 'sometimes|required|string|in:'.implode(',', SmsAudienceBuilder::VALID_TYPES),
            'audience_filters' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:2000',
        ]);

        $campaign = $this->campaignService->update($smsCampaign, $request->all());

        return AdminApiResponse::success(
            $this->formatCampaign($campaign->load(['template', 'creator'])),
            'کمپین پیامک با موفقیت به‌روزرسانی شد.'
        );
    }

    public function apiDestroy(SmsCampaign $smsCampaign): JsonResponse
    {
        if (! $smsCampaign->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'فقط کمپین‌های پیش‌نویس قابل حذف هستند.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $smsCampaign->delete();

        return AdminApiResponse::okMessage('کمپین پیامک با موفقیت حذف شد.');
    }

    public function apiPreview(SmsCampaign $smsCampaign): JsonResponse
    {
        return AdminApiResponse::success($this->campaignService->preview($smsCampaign->load('template')));
    }

    public function apiDispatch(SmsCampaign $smsCampaign): JsonResponse
    {
        $campaign = $this->campaignService->dispatch($smsCampaign, auth('sanctum')->user());

        return AdminApiResponse::success(
            $this->formatCampaign($campaign),
            'کمپین در صف ارسال قرار گرفت.'
        );
    }

    public function apiCancel(SmsCampaign $smsCampaign): JsonResponse
    {
        $campaign = $this->campaignService->cancel($smsCampaign);

        return AdminApiResponse::success(
            $this->formatCampaign($campaign),
            'کمپین لغو شد.'
        );
    }

    public function apiRecipients(Request $request, SmsCampaign $smsCampaign): JsonResponse
    {
        $query = $smsCampaign->recipients()->with('user:id,first_name,last_name');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where('phone_number', 'like', "%{$search}%");
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $page = max((int) $request->input('page', 1), 1);

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiStatistics(): JsonResponse
    {
        return AdminApiResponse::success([
            'total_campaigns' => SmsCampaign::count(),
            'draft_campaigns' => SmsCampaign::where('status', SmsCampaign::STATUS_DRAFT)->count(),
            'processing_campaigns' => SmsCampaign::whereIn('status', [
                SmsCampaign::STATUS_QUEUED,
                SmsCampaign::STATUS_PROCESSING,
            ])->count(),
            'completed_campaigns' => SmsCampaign::where('status', SmsCampaign::STATUS_COMPLETED)->count(),
            'failed_campaigns' => SmsCampaign::where('status', SmsCampaign::STATUS_FAILED)->count(),
            'total_sent' => SmsCampaign::sum('sent_count'),
            'total_failed' => SmsCampaign::sum('failed_count'),
            'by_status' => SmsCampaign::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ]);
    }

    public function apiExportRecipients(SmsCampaign $smsCampaign)
    {
        $filename = 'sms-campaign-'.$smsCampaign->id.'-recipients-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($smsCampaign) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [
                'id', 'user_id', 'phone_number', 'status', 'error_message', 'sent_at', 'created_at',
            ]);

            $smsCampaign->recipients()
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->user_id,
                            $row->phone_number,
                            $row->status,
                            $row->error_message,
                            $row->sent_at?->toIso8601String(),
                            $row->created_at?->toIso8601String(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function apiExport(Request $request)
    {
        $query = SmsCampaign::query()->with('template:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $filename = 'sms-campaigns-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [
                'id', 'name', 'template', 'audience_type', 'status',
                'total_recipients', 'sent_count', 'failed_count', 'skipped_count',
                'started_at', 'completed_at', 'created_at',
            ]);

            $query->clone()->orderBy('id')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->template?->name,
                        $row->audience_type,
                        $row->status,
                        $row->total_recipients,
                        $row->sent_count,
                        $row->failed_count,
                        $row->skipped_count,
                        $row->started_at?->toIso8601String(),
                        $row->completed_at?->toIso8601String(),
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatCampaign(SmsCampaign $campaign): array
    {
        $data = $campaign->toArray();
        $data['progress_percent'] = $campaign->progress_percent;

        return $data;
    }
}
