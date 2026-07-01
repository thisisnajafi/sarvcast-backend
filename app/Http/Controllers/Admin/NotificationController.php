<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\Notification;
use App\Models\User;
use App\Services\InAppNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function __construct(
        protected InAppNotificationService $inAppNotificationService
    ) {
    }

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        $query = Notification::with(['sender', 'recipient']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereHas('sender', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('recipient', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by recipient
        if ($request->filled('recipient_id')) {
            $query->where('recipient_id', $request->recipient_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => Notification::count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'pending' => Notification::where('status', 'pending')->count(),
            'failed' => Notification::where('status', 'failed')->count(),
            'read' => Notification::where('is_read', true)->count(),
            'unread' => Notification::where('is_read', false)->count(),
            'system' => Notification::where('type', 'system')->count(),
            'user' => Notification::where('type', 'user')->count(),
            'marketing' => Notification::where('type', 'marketing')->count(),
            'urgent' => Notification::where('priority', 'urgent')->count(),
        ];

        $users = User::where('is_active', true)->get();

        return view('admin.notifications.index', compact('notifications', 'stats', 'users'));
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create()
    {
        $users = User::where('is_active', true)->get();
        return view('admin.notifications.create', compact('users'));
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:system,user,marketing,alert',
            'priority' => 'required|in:low,medium,high,urgent',
            'recipient_type' => 'required|in:all,user,specific',
            'recipient_id' => 'nullable|exists:users,id',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'send_push' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $notificationData = [
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'priority' => $request->priority,
                'recipient_type' => $request->recipient_type,
                'recipient_id' => $request->recipient_id,
                'send_email' => $request->has('send_email'),
                'send_sms' => $request->has('send_sms'),
                'send_push' => $request->has('send_push'),
                'scheduled_at' => $request->scheduled_at,
                'expires_at' => $request->expires_at,
                'status' => $request->scheduled_at ? 'pending' : 'sent',
                'sender_id' => auth()->id(),
            ];

            if ($request->recipient_type === 'all') {
                // Create notification for all users
                $users = User::where('is_active', true)->get();
                foreach ($users as $user) {
                    Notification::create(array_merge($notificationData, [
                        'recipient_id' => $user->id,
                    ]));
                }
                } else {
                // Create single notification
                Notification::create($notificationData);
                }

            // Send immediate notifications if not scheduled
            if (!$request->scheduled_at) {
                $this->sendNotifications($notificationData);
        }

            DB::commit();

        return redirect()->route('admin.notifications.index')
                ->with('success', 'اعلان با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در ایجاد اعلان: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification)
    {
        $notification->load(['sender', 'recipient']);

        // Get related notifications
        $relatedNotifications = Notification::where('type', $notification->type)
            ->where('id', '!=', $notification->id)
            ->with(['sender', 'recipient'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.notifications.show', compact('notification', 'relatedNotifications'));
    }

    /**
     * Show the form for editing the specified notification.
     */
    public function edit(Notification $notification)
    {
        $users = User::where('is_active', true)->get();
        return view('admin.notifications.edit', compact('notification', 'users'));
    }

    /**
     * Update the specified notification.
     */
    public function update(Request $request, Notification $notification)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:system,user,marketing,alert',
            'priority' => 'required|in:low,medium,high,urgent',
            'recipient_type' => 'required|in:all,user,specific',
            'recipient_id' => 'nullable|exists:users,id',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'send_push' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:now',
            'status' => 'required|in:pending,sent,failed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $notification->update([
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'priority' => $request->priority,
                'recipient_type' => $request->recipient_type,
                'recipient_id' => $request->recipient_id,
                'send_email' => $request->has('send_email'),
                'send_sms' => $request->has('send_sms'),
                'send_push' => $request->has('send_push'),
                'scheduled_at' => $request->scheduled_at,
                'expires_at' => $request->expires_at,
                'status' => $request->status,
            ]);

        return redirect()->route('admin.notifications.index')
                ->with('success', 'اعلان با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در به‌روزرسانی اعلان: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Notification $notification)
    {
        try {
        $notification->delete();
            return redirect()->route('admin.notifications.index')
                ->with('success', 'اعلان با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف اعلان: ' . $e->getMessage()]);
        }
    }

    /**
     * Send notification immediately.
     */
    public function send(Notification $notification)
    {
        try {
            $this->sendNotifications([
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'priority' => $notification->priority,
                'recipient_id' => $notification->recipient_id,
                'send_email' => $notification->send_email,
                'send_sms' => $notification->send_sms,
                'send_push' => $notification->send_push,
            ]);

            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            return redirect()->back()
                ->with('success', 'اعلان با موفقیت ارسال شد.');

        } catch (\Exception $e) {
            $notification->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return redirect()->back()
                ->withErrors(['error' => 'خطا در ارسال اعلان: ' . $e->getMessage()]);
        }
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        try {
            $notification->update(['is_read' => true, 'read_at' => now()]);
            return redirect()->back()
                ->with('success', 'اعلان به عنوان خوانده شده علامت‌گذاری شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در علامت‌گذاری: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on notifications.
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:send,mark_read,mark_unread,delete',
            'notification_ids' => 'required|array|min:1',
            'notification_ids.*' => 'exists:notifications,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک اعلان را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $notifications = Notification::whereIn('id', $request->notification_ids);

            switch ($request->action) {
                case 'send':
                    foreach ($notifications->get() as $notification) {
                        $this->sendNotifications([
                            'title' => $notification->title,
                            'message' => $notification->message,
                            'type' => $notification->type,
                            'priority' => $notification->priority,
                            'recipient_id' => $notification->recipient_id,
                            'send_email' => $notification->send_email,
                            'send_sms' => $notification->send_sms,
                            'send_push' => $notification->send_push,
                        ]);
                    }
                    $notifications->update(['status' => 'sent', 'sent_at' => now()]);
                    break;

                case 'mark_read':
                    $notifications->update(['is_read' => true, 'read_at' => now()]);
                    break;

                case 'mark_unread':
                    $notifications->update(['is_read' => false, 'read_at' => null]);
                    break;

                case 'delete':
                    $notifications->delete();
                    break;
            }

            DB::commit();

            $actionLabels = [
                'send' => 'ارسال',
                'mark_read' => 'علامت‌گذاری به عنوان خوانده شده',
                'mark_unread' => 'علامت‌گذاری به عنوان خوانده نشده',
                'delete' => 'حذف',
            ];

            return redirect()->back()
                ->with('success', 'عملیات ' . $actionLabels[$request->action] . ' با موفقیت انجام شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در انجام عملیات: ' . $e->getMessage()]);
        }
    }

    /**
     * Get notification statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_notifications' => Notification::count(),
            'sent_notifications' => Notification::where('status', 'sent')->count(),
            'pending_notifications' => Notification::where('status', 'pending')->count(),
            'failed_notifications' => Notification::where('status', 'failed')->count(),
            'read_notifications' => Notification::where('is_read', true)->count(),
            'unread_notifications' => Notification::where('is_read', false)->count(),
            'by_type' => Notification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'by_priority' => Notification::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get(),
            'by_status' => Notification::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'notifications_by_month' => Notification::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_notifications' => Notification::with(['sender', 'recipient'])->orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return view('admin.notifications.statistics', compact('stats'));
    }

    /**
     * Send notifications through various channels.
     */
    private function sendNotifications(array $notificationData): void
    {
        $result = $this->dispatchPushNotifications($notificationData);

        Log::info('Admin push notifications dispatched', $result);
    }

    /**
     * @return array{sent_count: int, failed_count: int, recipient_count: int}
     */
    private function dispatchPushNotifications(array $notificationData): array
    {
        $recipientIds = $this->resolveRecipientUserIds($notificationData);
        if ($recipientIds === []) {
            return ['sent_count' => 0, 'failed_count' => 0, 'recipient_count' => 0];
        }

        $sendPush = (bool) ($notificationData['send_push'] ?? true);
        $type = $this->normalizeNotificationType($notificationData['type'] ?? 'system');
        $category = $notificationData['category'] ?? 'system';
        $pushNavType = $this->mapPushNavigationType($category);
        $sent = 0;
        $failed = 0;

        foreach ($recipientIds as $userId) {
            try {
                $options = [
                    'category' => $category,
                    'priority' => $this->mapAdminPriority($notificationData['priority'] ?? 'normal'),
                    'send_push' => $sendPush,
                    'is_important' => (bool) ($notificationData['is_important'] ?? false),
                    'data' => array_filter([
                        'type' => $pushNavType,
                        'admin_notification' => true,
                    ], fn ($value) => $value !== null && $value !== ''),
                ];

                if (! empty($notificationData['action_type'])) {
                    $options['action_type'] = $notificationData['action_type'];
                }
                if (! empty($notificationData['action_url'])) {
                    $options['action_url'] = $notificationData['action_url'];
                }
                if (! empty($notificationData['action_text'])) {
                    $options['action_text'] = $notificationData['action_text'];
                }
                if (! empty($notificationData['story_id'])) {
                    $options['story_id'] = (int) $notificationData['story_id'];
                    $options['data']['story_id'] = (string) $notificationData['story_id'];
                    $options['data']['type'] = 'story';
                }
                if (! empty($notificationData['episode_id'])) {
                    $options['episode_id'] = (int) $notificationData['episode_id'];
                    $options['data']['episode_id'] = (string) $notificationData['episode_id'];
                    $options['data']['type'] = 'episode';
                }

                $this->inAppNotificationService->createNotification(
                    $userId,
                    $type,
                    $notificationData['title'],
                    $notificationData['message'],
                    $options
                );
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Admin notification send failed', [
                    'user_id' => $userId,
                    'title' => $notificationData['title'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'sent_count' => $sent,
            'failed_count' => $failed,
            'recipient_count' => count($recipientIds),
        ];
    }

    /**
     * @return list<int>
     */
    private function resolveRecipientUserIds(array $notificationData): array
    {
        $recipientType = $notificationData['recipient_mode']
            ?? $notificationData['recipient_type']
            ?? null;

        if ($recipientType === 'all') {
            return User::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if (in_array($recipientType, ['role', 'roles'], true)) {
            $roles = $notificationData['roles'] ?? [];
            if (is_string($roles)) {
                $roles = [$roles];
            }
            if ($roles === [] && ! empty($notificationData['role'])) {
                $roles = [(string) $notificationData['role']];
            }

            if ($roles === []) {
                return [];
            }

            return User::query()
                ->where('is_active', true)
                ->whereIn('role', $roles)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if (in_array($recipientType, ['user', 'users', 'specific'], true)) {
            $userIds = $notificationData['user_ids'] ?? [];
            if (! is_array($userIds)) {
                $userIds = [$userIds];
            }
            if ($userIds === [] && ! empty($notificationData['recipient_id'])) {
                $userIds = [(int) $notificationData['recipient_id']];
            }
            if ($userIds === [] && ! empty($notificationData['user_id'])) {
                $userIds = [(int) $notificationData['user_id']];
            }

            return collect($userIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }

    private function normalizeNotificationType(string $type): string
    {
        if (array_key_exists($type, InAppNotificationService::TYPES)) {
            return $type;
        }

        return $this->mapAdminNotificationType($type);
    }

    private function mapPushNavigationType(string $category): string
    {
        return match ($category) {
            'content' => 'content',
            'subscription' => 'subscription',
            'payment' => 'payment',
            default => 'system',
        };
    }

    private function mapAdminNotificationType(string $type): string
    {
        return match ($type) {
            'alert', 'marketing' => 'warning',
            'user' => 'info',
            default => 'system',
        };
    }

    private function mapAdminPriority(string $priority): string
    {
        return match ($priority) {
            'urgent', 'high' => 'high',
            'low' => 'low',
            default => 'normal',
        };
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = $this->buildNotificationApiListQuery($request);
        $this->applyNotificationListSort($query, $request);

        $perPage = $this->resolveNotificationPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $notifications = $query->with(['sender', 'recipient'])->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($notifications);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildNotificationApiListQuery($request);
        $this->applyNotificationListSort($query, $request);

        $filename = 'notifications-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'title', 'type', 'priority', 'status', 'is_read', 'sender_id', 'recipient_id', 'created_at']);

            $query->clone()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->title,
                        $row->type,
                        $row->priority,
                        $row->status,
                        $row->is_read ? '1' : '0',
                        $row->sender_id,
                        $row->recipient_id,
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:info,warning,error,success',
            'priority' => 'required|in:low,medium,high,urgent',
            'recipient_id' => 'nullable|exists:users,id',
            'recipient_type' => 'required|in:all,user,role',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'send_push' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            DB::beginTransaction();

            $notification = Notification::create([
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'priority' => $request->priority,
                'sender_id' => auth()->id(),
                'recipient_id' => $request->recipient_id,
                'recipient_type' => $request->recipient_type,
                'status' => 'pending',
                'send_email' => $request->boolean('send_email', false),
                'send_sms' => $request->boolean('send_sms', false),
                'send_push' => $request->boolean('send_push', true),
                'scheduled_at' => $request->scheduled_at,
                'expires_at' => $request->expires_at,
            ]);

            // Send notification if not scheduled
            if (!$request->scheduled_at) {
                $this->sendNotifications($notification->toArray());
                $notification->update(['status' => 'sent', 'sent_at' => now()]);
            }

            DB::commit();

            return AdminApiResponse::success(
                $notification->load(['sender', 'recipient']),
                'اعلان با موفقیت ایجاد شد.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد اعلان: ' . $e->getMessage(),
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiShow(Notification $notification)
    {
        $notification->load(['sender', 'recipient']);

        return AdminApiResponse::success($notification);
    }

    public function apiUpdate(Request $request, Notification $notification)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:info,warning,error,success',
            'priority' => 'required|in:low,medium,high,urgent',
            'recipient_id' => 'nullable|exists:users,id',
            'recipient_type' => 'required|in:all,user,role',
            'status' => 'required|in:pending,sent,failed',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'send_push' => 'boolean',
            'scheduled_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $notification->update([
                        'title' => $request->title,
                        'message' => $request->message,
                'type' => $request->type,
                'priority' => $request->priority,
                'recipient_id' => $request->recipient_id,
                'recipient_type' => $request->recipient_type,
                'status' => $request->status,
                'send_email' => $request->boolean('send_email'),
                'send_sms' => $request->boolean('send_sms'),
                'send_push' => $request->boolean('send_push'),
                'scheduled_at' => $request->scheduled_at,
                'expires_at' => $request->expires_at,
            ]);

            DB::commit();

            return AdminApiResponse::success(
                $notification->load(['sender', 'recipient']),
                'اعلان با موفقیت به‌روزرسانی شد.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی اعلان: ' . $e->getMessage(),
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiDestroy(Notification $notification)
    {
        try {
            $notification->delete();

            return AdminApiResponse::okMessage('اعلان با موفقیت حذف شد.');

        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف اعلان: ' . $e->getMessage(),
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:send,mark_read,mark_unread,delete',
            'notification_ids' => 'nullable|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'integer|exists:notifications,id',
        ]);

        $notificationIds = $validated['notification_ids'] ?? $validated['selected_items'] ?? [];
        if ($notificationIds === []) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ موردی انتخاب نشده است.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $action = $validated['action'];
            $successCount = 0;
            $failureCount = 0;

            foreach ($notificationIds as $notificationId) {
                try {
                    $notification = Notification::findOrFail($notificationId);

                    switch ($action) {
                        case 'send':
                            $this->sendNotifications($notification->toArray());
                            $notification->update(['status' => 'sent', 'sent_at' => now()]);
                            break;

                        case 'mark_read':
                            $notification->update(['is_read' => true, 'read_at' => now()]);
                    break;

                        case 'mark_unread':
                            $notification->update(['is_read' => false, 'read_at' => null]);
                    break;

                        case 'delete':
                            $notification->delete();
                    break;
            }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for notification', [
                        'notification_id' => $notificationId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $actionLabels = [
                'send' => 'ارسال',
                'mark_read' => 'علامت‌گذاری به عنوان خوانده شده',
                'mark_unread' => 'علامت‌گذاری به عنوان خوانده نشده',
                'delete' => 'حذف',
            ];

            $message = "عملیات {$actionLabels[$action]} روی {$successCount} اعلان انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} اعلان ناموفق بود";
        }

            return AdminApiResponse::success([
                'success_count' => $successCount,
                'failure_count' => $failureCount,
            ], $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'notification_ids' => $request->notification_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات گروهی: ' . $e->getMessage(),
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiStatistics()
    {
        $stats = [
            'total_notifications' => Notification::count(),
            'sent_notifications' => Notification::where('status', 'sent')->count(),
            'pending_notifications' => Notification::where('status', 'pending')->count(),
            'failed_notifications' => Notification::where('status', 'failed')->count(),
            'read_notifications' => Notification::where('is_read', true)->count(),
            'unread_notifications' => Notification::where('is_read', false)->count(),
            'notifications_by_type' => Notification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'notifications_by_priority' => Notification::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get(),
            'notifications_by_status' => Notification::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'notifications_by_month' => Notification::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_notifications' => Notification::with(['sender', 'recipient'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'scheduled_notifications' => Notification::whereNotNull('scheduled_at')->count(),
            'expired_notifications' => Notification::where('expires_at', '<', now())->count(),
        ];

        return AdminApiResponse::success($stats);
    }

    public function apiSend(Notification $notification)
    {
        try {
            $this->sendNotifications($notification->toArray());
            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            return AdminApiResponse::okMessage('اعلان با موفقیت ارسال شد.');

        } catch (\Exception $e) {
            Log::error('Error sending notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال اعلان: ' . $e->getMessage(),
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    private function buildNotificationApiListQuery(Request $request)
    {
        $query = Notification::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%')
                    ->orWhereHas('sender', function ($sq) use ($search) {
                        $sq->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('recipient', function ($rq) use ($search) {
                        $rq->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }
        if ($request->filled('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }
        if ($request->filled('recipient_id')) {
            $query->where('recipient_id', $request->recipient_id);
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        } elseif ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('date_range')) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', Carbon::now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', Carbon::now()->subMonth());
                    break;
                case 'year':
                    $query->where('created_at', '>=', Carbon::now()->subYear());
                    break;
            }
        }

        return $query;
    }

    private function applyNotificationListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'title', 'type', 'priority', 'status', 'is_read', 'sender_id', 'recipient_id'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveNotificationPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }

    public function apiComposeOptions()
    {
        return AdminApiResponse::success([
            'types' => $this->formatLabelValueMap(InAppNotificationService::TYPES),
            'categories' => $this->formatLabelValueMap(InAppNotificationService::CATEGORIES),
            'priorities' => $this->formatLabelValueMap(InAppNotificationService::PRIORITIES),
            'roles' => [
                ['value' => User::ROLE_PARENT, 'label' => 'والد'],
                ['value' => User::ROLE_CHILD, 'label' => 'کودک'],
                ['value' => User::ROLE_BASIC, 'label' => 'کاربر پایه'],
                ['value' => User::ROLE_VOICE_ACTOR, 'label' => 'صداپیشه'],
                ['value' => User::ROLE_ADMIN, 'label' => 'مدیر'],
                ['value' => User::ROLE_SUPER_ADMIN, 'label' => 'مدیر ارشد'],
            ],
            'recipient_modes' => [
                ['value' => 'all', 'label' => 'همه کاربران فعال'],
                ['value' => 'roles', 'label' => 'بر اساس نقش کاربری'],
                ['value' => 'users', 'label' => 'کاربران مشخص'],
            ],
            'action_types' => [
                ['value' => 'button', 'label' => 'دکمه'],
                ['value' => 'link', 'label' => 'لینک'],
                ['value' => 'dismiss', 'label' => 'بستن'],
            ],
        ]);
    }

    public function apiSendPush(Request $request)
    {
        $validated = $this->validatePushPayload($request);
        $validated['category'] = $validated['category'] ?? 'system';
        $validated['priority'] = $validated['priority'] ?? 'normal';
        $validated['send_push'] = $request->boolean('send_push', true);
        $validated['is_important'] = $request->boolean('is_important', false);

        $recipientCount = count($this->resolveRecipientUserIds([
            'recipient_mode' => $validated['recipient_mode'],
            'roles' => $validated['roles'] ?? [],
            'user_ids' => $validated['user_ids'] ?? [],
        ]));

        if ($recipientCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ گیرنده‌ای برای ارسال یافت نشد.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        if ($recipientCount > 20000) {
            return response()->json([
                'success' => false,
                'message' => 'تعداد گیرندگان بیش از حد مجاز است. لطفاً فیلتر دقیق‌تری انتخاب کنید.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $result = $this->dispatchPushNotifications($validated);

        if ($result['sent_count'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'ارسال اعلان برای هیچ کاربری موفق نبود.',
                'error' => 'SERVER_ERROR',
                'data' => $result,
            ], 500);
        }

        $message = "اعلان برای {$result['sent_count']} کاربر ارسال شد.";
        if ($result['failed_count'] > 0) {
            $message .= " ({$result['failed_count']} مورد ناموفق)";
        }

        return AdminApiResponse::success($result, $message);
    }

    public function apiTestSend(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|string|in:'.implode(',', array_keys(InAppNotificationService::TYPES)),
            'category' => 'nullable|string|in:'.implode(',', array_keys(InAppNotificationService::CATEGORIES)),
            'priority' => 'nullable|string|in:'.implode(',', array_keys(InAppNotificationService::PRIORITIES)),
            'is_important' => 'nullable|boolean',
            'send_push' => 'nullable|boolean',
            'action_type' => 'nullable|string|in:link,button,dismiss',
            'action_url' => 'nullable|string|max:500',
            'action_text' => 'nullable|string|max:100',
            'story_id' => 'nullable|integer|exists:stories,id',
            'episode_id' => 'nullable|integer|exists:episodes,id',
        ], [
            'user_id.required' => 'انتخاب کاربر برای ارسال تست الزامی است.',
            'title.required' => 'عنوان اعلان الزامی است.',
            'message.required' => 'متن اعلان الزامی است.',
        ]);

        $payload = array_merge($validated, [
            'recipient_mode' => 'users',
            'user_ids' => [(int) $validated['user_id']],
            'send_push' => $request->boolean('send_push', true),
            'is_important' => $request->boolean('is_important', true),
            'category' => $validated['category'] ?? 'system',
            'priority' => $validated['priority'] ?? 'high',
        ]);

        $result = $this->dispatchPushNotifications($payload);

        if ($result['sent_count'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'ارسال تست ناموفق بود.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }

        return AdminApiResponse::success($result, 'اعلان تست با موفقیت ارسال شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePushPayload(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|string|in:'.implode(',', array_keys(InAppNotificationService::TYPES)),
            'category' => 'nullable|string|in:'.implode(',', array_keys(InAppNotificationService::CATEGORIES)),
            'priority' => 'nullable|string|in:'.implode(',', array_keys(InAppNotificationService::PRIORITIES)),
            'recipient_mode' => 'required|string|in:all,roles,users',
            'roles' => 'required_if:recipient_mode,roles|array|min:1',
            'roles.*' => 'string|in:'.implode(',', [
                User::ROLE_PARENT,
                User::ROLE_CHILD,
                User::ROLE_BASIC,
                User::ROLE_VOICE_ACTOR,
                User::ROLE_ADMIN,
                User::ROLE_SUPER_ADMIN,
            ]),
            'user_ids' => 'required_if:recipient_mode,users|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'is_important' => 'nullable|boolean',
            'send_push' => 'nullable|boolean',
            'action_type' => 'nullable|string|in:link,button,dismiss',
            'action_url' => 'nullable|string|max:500',
            'action_text' => 'nullable|string|max:100',
            'story_id' => 'nullable|integer|exists:stories,id',
            'episode_id' => 'nullable|integer|exists:episodes,id',
        ], [
            'title.required' => 'عنوان اعلان الزامی است.',
            'message.required' => 'متن اعلان الزامی است.',
            'recipient_mode.required' => 'نوع گیرنده الزامی است.',
            'roles.required_if' => 'حداقل یک نقش کاربری انتخاب کنید.',
            'user_ids.required_if' => 'حداقل یک کاربر انتخاب کنید.',
        ]);
    }

    /**
     * @param  array<string, string>  $map
     * @return list<array{value: string, label: string}>
     */
    private function formatLabelValueMap(array $map): array
    {
        return collect($map)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
