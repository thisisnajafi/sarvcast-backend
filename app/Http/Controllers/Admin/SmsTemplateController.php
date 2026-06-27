<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SmsTemplate;
use App\Services\SmsAudienceBuilder;
use App\Services\SmsParameterResolver;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SmsTemplateController extends Controller
{
    public function __construct(
        private readonly SmsTemplateService $templateService,
        private readonly SmsParameterResolver $parameterResolver,
        private readonly SmsAudienceBuilder $audienceBuilder,
        private readonly SmsService $smsService,
    ) {}

    public function apiIndex(Request $request): JsonResponse
    {
        $query = SmsTemplate::query()->with(['creator:id,first_name,last_name']);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('preview_text', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSort = ['name', 'melipayamak_body_id', 'category', 'is_active', 'created_at'];

        if (in_array($sortBy, $allowedSort, true)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $page = max((int) $request->input('page', 1), 1);

        return AdminApiResponse::paginated(
            $query->paginate($perPage, ['*'], 'page', $page)
        );
    }

    public function apiStore(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'melipayamak_body_id' => 'required|integer|min:1',
            'preview_text' => 'required|string|max:2000',
            'parameters' => 'required|array|min:1',
            'parameters.*.index' => 'required|integer|min:0',
            'parameters.*.label' => 'required|string|max:100',
            'parameters.*.source' => 'required|string|max:100',
            'parameters.*.fallback' => 'nullable|string|max:255',
            'parameters.*.static_value' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:marketing,transactional,system',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'sometimes|boolean',
        ]);

        $template = $this->templateService->create($request->all(), auth('sanctum')->user());

        return AdminApiResponse::success($template->load('creator:id,first_name,last_name'), 'قالب پیامک با موفقیت ایجاد شد.', 201);
    }

    public function apiShow(SmsTemplate $smsTemplate): JsonResponse
    {
        return AdminApiResponse::success(
            $smsTemplate->load(['creator:id,first_name,last_name', 'updater:id,first_name,last_name'])
                ->loadCount('campaigns')
        );
    }

    public function apiUpdate(Request $request, SmsTemplate $smsTemplate): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'melipayamak_body_id' => 'sometimes|required|integer|min:1',
            'preview_text' => 'sometimes|required|string|max:2000',
            'parameters' => 'sometimes|required|array|min:1',
            'parameters.*.index' => 'required|integer|min:0',
            'parameters.*.label' => 'required|string|max:100',
            'parameters.*.source' => 'required|string|max:100',
            'parameters.*.fallback' => 'nullable|string|max:255',
            'parameters.*.static_value' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:marketing,transactional,system',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'sometimes|boolean',
        ]);

        $template = $this->templateService->update(
            $smsTemplate,
            $request->only(['name', 'melipayamak_body_id', 'preview_text', 'parameters', 'category', 'description', 'is_active']),
            auth('sanctum')->user()
        );

        return AdminApiResponse::success($template->load('updater:id,first_name,last_name'), 'قالب پیامک با موفقیت به‌روزرسانی شد.');
    }

    public function apiDestroy(SmsTemplate $smsTemplate): JsonResponse
    {
        if ($smsTemplate->campaigns()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'قالبی که در کمپین‌ها استفاده شده قابل حذف نیست.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $smsTemplate->delete();

        return AdminApiResponse::okMessage('قالب پیامک با موفقیت حذف شد.');
    }

    public function apiBulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'sms_template_ids' => 'nullable|array',
            'sms_template_ids.*' => 'integer|exists:sms_templates,id',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'integer|exists:sms_templates,id',
        ]);

        $ids = $validated['sms_template_ids'] ?? $validated['selected_items'] ?? [];

        if (count($ids) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ قالبی انتخاب نشده است.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($ids as $id) {
            $template = SmsTemplate::find($id);
            if (! $template) {
                $failureCount++;
                continue;
            }

            try {
                match ($validated['action']) {
                    'activate' => $template->update(['is_active' => true]),
                    'deactivate' => $template->update(['is_active' => false]),
                    'delete' => $this->deleteTemplateOrFail($template),
                };
                $successCount++;
            } catch (ValidationException) {
                $failureCount++;
            }
        }

        return AdminApiResponse::success([
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ], 'عملیات گروهی انجام شد.');
    }

    public function apiExport(Request $request)
    {
        $query = SmsTemplate::query();

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $filename = 'sms-templates-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'name', 'slug', 'melipayamak_body_id', 'category', 'is_active', 'parameter_count', 'created_at']);

            $query->clone()->orderBy('id')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->slug,
                        $row->melipayamak_body_id,
                        $row->category,
                        $row->is_active ? '1' : '0',
                        $row->parameter_count,
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function apiStatistics(): JsonResponse
    {
        return AdminApiResponse::success([
            'total_templates' => SmsTemplate::count(),
            'active_templates' => SmsTemplate::where('is_active', true)->count(),
            'inactive_templates' => SmsTemplate::where('is_active', false)->count(),
            'by_category' => SmsTemplate::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
        ]);
    }

    public function apiTestSend(Request $request, SmsTemplate $smsTemplate): JsonResponse
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:15',
            'parameter_overrides' => 'nullable|array',
        ]);

        $phone = $this->audienceBuilder->normalizePhone($validated['phone_number']);

        if (! $this->audienceBuilder->isValidIranianMobile($phone)) {
            throw ValidationException::withMessages([
                'phone_number' => ['شماره موبایل معتبر نیست.'],
            ]);
        }

        $overrides = $validated['parameter_overrides'] ?? [];
        $parameters = $this->parameterResolver->resolve(null, $smsTemplate->parameters ?? [], $overrides);
        $preview = $this->parameterResolver->renderPreview($smsTemplate->preview_text, $parameters);

        try {
            $result = $this->smsService->sendSmsWithTemplate(
                $phone,
                $smsTemplate->melipayamak_body_id,
                $parameters,
                [
                    'sms_template_id' => $smsTemplate->id,
                    'preview_text' => $preview,
                    'message' => $preview,
                ]
            );

            if (! ($result['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ارسال پیامک تست ناموفق بود.',
                    'error' => $result['error'] ?? 'SEND_FAILED',
                    'data' => [
                        'preview_message' => $preview,
                        'parameters' => $parameters,
                    ],
                ], 502);
            }

            return AdminApiResponse::success([
                'preview_message' => $preview,
                'parameters' => $parameters,
                'message_id' => $result['message_id'] ?? null,
                'sms_log_id' => $result['sms_log_id'] ?? null,
            ], 'پیامک تست با موفقیت ارسال شد.');
        } catch (\Throwable $e) {
            Log::error('SMS template test send failed', [
                'template_id' => $smsTemplate->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک تست.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    private function deleteTemplateOrFail(SmsTemplate $template): void
    {
        if ($template->campaigns()->exists()) {
            throw ValidationException::withMessages([
                'id' => ['قالبی که در کمپین‌ها استفاده شده قابل حذف نیست.'],
            ]);
        }

        $template->delete();
    }
}
