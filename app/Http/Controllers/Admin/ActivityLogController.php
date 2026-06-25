<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RequiresAuditPermission;
use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    use RequiresAuditPermission;

    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function index(Request $request)
    {
        if ($response = $this->ensureAuditView()) {
            return $response;
        }

        $validated = $this->validateFilters($request);
        $perPage = min((int) ($validated['per_page'] ?? 25), 100);

        $paginator = $this->buildFilteredQuery($validated)
            ->with('actor')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (ActivityLog $log) => $log->toApiArray());

        return AdminApiResponse::paginated($paginator);
    }

    public function show(ActivityLog $activityLog)
    {
        if ($response = $this->ensureAuditView()) {
            return $response;
        }

        $activityLog->load('actor');

        return AdminApiResponse::success($activityLog->toApiArray());
    }

    public function stats(Request $request)
    {
        if ($response = $this->ensureAuditView()) {
            return $response;
        }

        $validated = $request->validate([
            'channel' => ['nullable', 'string', Rule::in(config('activity_log.channels', []))],
        ]);

        $base = ActivityLog::query();
        if (! empty($validated['channel'])) {
            $base->where('channel', $validated['channel']);
        }

        $since24h = now()->subDay();
        $since7d = now()->subDays(7);

        return AdminApiResponse::success([
            'last_24h' => (clone $base)->where('occurred_at', '>=', $since24h)->count(),
            'last_7d' => (clone $base)->where('occurred_at', '>=', $since7d)->count(),
            'by_channel' => ActivityLog::query()
                ->selectRaw('channel, COUNT(*) as total')
                ->where('occurred_at', '>=', $since7d)
                ->groupBy('channel')
                ->pluck('total', 'channel'),
            'top_actions_7d' => ActivityLog::query()
                ->selectRaw('action, COUNT(*) as total')
                ->where('occurred_at', '>=', $since7d)
                ->when(! empty($validated['channel']), fn ($q) => $q->where('channel', $validated['channel']))
                ->groupBy('action')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'action'),
            'security_alerts' => $this->activityLog->evaluateSecurityAlerts(),
            'failed_logins_24h' => ActivityLog::query()
                ->where('channel', ActivityLog::CHANNEL_SECURITY)
                ->where('action', 'login_failed')
                ->where('occurred_at', '>=', $since24h)
                ->count(),
        ]);
    }

    public function export(Request $request)
    {
        if ($response = $this->ensureAuditExport()) {
            return $response;
        }

        $validated = $this->validateFilters($request);
        $maxRows = (int) config('activity_log.export_max_rows', 10000);

        $logs = $this->buildFilteredQuery($validated)
            ->with('actor')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($maxRows)
            ->get();

        $filename = 'activity_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, [
                'ID',
                'Channel',
                'Action',
                'Status',
                'Actor ID',
                'Actor Name',
                'Subject Type',
                'Subject ID',
                'Subject Label',
                'Description',
                'IP',
                'Request ID',
                'Occurred At',
            ]);

            foreach ($logs as $log) {
                $actorName = $log->actor
                    ? trim(($log->actor->first_name ?? '') . ' ' . ($log->actor->last_name ?? ''))
                    : '';
                fputcsv($file, [
                    $log->id,
                    $log->channel,
                    $log->action,
                    $log->status,
                    $log->actor_user_id,
                    $actorName !== '' ? $actorName : ($log->actor->phone_number ?? ''),
                    $log->subject_type,
                    $log->subject_id,
                    $log->subject_label,
                    $log->description,
                    $log->ip_address,
                    $log->request_id,
                    $log->occurred_at?->toIso8601String(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'channel' => ['nullable', 'string', Rule::in(config('activity_log.channels', []))],
            'actor_user_id' => 'nullable|integer|exists:users,id',
            'subject_type' => 'nullable|string|max:64',
            'subject_id' => 'nullable|string|max:64',
            'action' => 'nullable|string|max:64',
            'status' => 'nullable|in:success,failed',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'search' => 'nullable|string|max:200',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function buildFilteredQuery(array $validated): Builder
    {
        $query = ActivityLog::query();

        if (! empty($validated['channel'])) {
            $query->where('channel', $validated['channel']);
        }

        if (! empty($validated['actor_user_id'])) {
            $query->where('actor_user_id', $validated['actor_user_id']);
        }

        if (! empty($validated['subject_type'])) {
            $query->where('subject_type', $validated['subject_type']);
        }

        if (! empty($validated['subject_id'])) {
            $query->where('subject_id', $validated['subject_id']);
        }

        if (! empty($validated['action'])) {
            $actions = array_map('trim', explode(',', (string) $validated['action']));
            $query->whereIn('action', $actions);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['from'])) {
            $query->where('occurred_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->where('occurred_at', '<=', $validated['to']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_label', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
