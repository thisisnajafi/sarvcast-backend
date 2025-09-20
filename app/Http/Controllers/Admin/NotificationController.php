<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
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
    private function sendNotifications($notificationData)
    {
        // In a real application, you would implement actual sending logic here
        // For now, we'll just simulate the process
        
        if ($notificationData['send_email']) {
            // Send email notification
            // Mail::to($user)->send(new NotificationMail($notificationData));
        }

        if ($notificationData['send_sms']) {
            // Send SMS notification
            // SMS::send($user->phone, $notificationData['message']);
        }

        if ($notificationData['send_push']) {
            // Send push notification
            // PushNotification::send($user, $notificationData);
        }
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = Notification::with(['sender', 'recipient']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereHas('sender', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('recipient', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by read status
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Filter by sender
        if ($request->filled('sender_id')) {
            $query->where('sender_id', $request->sender_id);
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

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
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

            return response()->json([
                'success' => true,
                'message' => 'اعلان با موفقیت ایجاد شد.',
                'data' => $notification->load(['sender', 'recipient'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating notification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد اعلان: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiShow(Notification $notification)
    {
        $notification->load(['sender', 'recipient']);

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
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

            return response()->json([
                'success' => true,
                'message' => 'اعلان با موفقیت به‌روزرسانی شد.',
                'data' => $notification->load(['sender', 'recipient'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating notification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی اعلان: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDestroy(Notification $notification)
    {
        try {
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'اعلان با موفقیت حذف شد.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف اعلان: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:send,mark_read,mark_unread,delete',
            'notification_ids' => 'required|array|min:1',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);

        try {
            DB::beginTransaction();

            $notificationIds = $request->notification_ids;
            $action = $request->action;
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

        return response()->json([
            'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'notification_ids' => $request->notification_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات گروهی: ' . $e->getMessage()
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

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function apiSend(Notification $notification)
    {
        try {
            $this->sendNotifications($notification->toArray());
            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'اعلان با موفقیت ارسال شد.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending notification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال اعلان: ' . $e->getMessage()
            ], 500);
        }
    }
}