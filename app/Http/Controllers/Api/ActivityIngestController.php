<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityIngestController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function batch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:64',
            'app_version' => 'nullable|string|max:32',
            'platform' => 'nullable|string|in:android,ios,web',
            'events' => 'required|array|min:1|max:50',
            'events.*.event_uuid' => 'required|uuid',
            'events.*.action' => 'required|string|max:64',
            'events.*.subject_type' => 'nullable|string|max:64',
            'events.*.subject_id' => 'nullable|string|max:64',
            'events.*.subject_label' => 'nullable|string|max:255',
            'events.*.occurred_at' => 'nullable|date',
            'events.*.properties' => 'nullable|array',
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication is required.',
            ], 401);
        }

        $result = $this->activityLog->recordAppBatch(
            $user,
            $validated['events'],
            $request,
            $validated['device_id'],
            $validated['app_version'] ?? null,
            $validated['platform'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Events accepted',
            'data' => $result,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication is required.',
            ], 401);
        }

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $perPage = min((int) ($validated['per_page'] ?? 20), 50);

        $paginator = ActivityLog::query()
            ->where('channel', ActivityLog::CHANNEL_APP)
            ->where('actor_user_id', $user->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (ActivityLog $log) => $log->toApiArray());

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'lastPage' => $paginator->lastPage(),
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
