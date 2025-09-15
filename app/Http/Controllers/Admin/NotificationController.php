<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications
     */
    public function index(Request $request)
    {
        $query = Notification::with('user');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('read')) {
            if ($request->boolean('read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('message', 'like', '%' . $request->search . '%');
            });
        }

        $notifications = $query->latest()->paginate(20);
        $users = User::select('id', 'first_name', 'last_name', 'email')->get();

        return view('admin.notifications.index', compact('notifications', 'users'));
    }

    /**
     * Show the form for creating a new notification
     */
    public function create()
    {
        $users = User::select('id', 'first_name', 'last_name', 'email')->get();
        $templates = config('notification.templates');

        return view('admin.notifications.create', compact('users', 'templates'));
    }

    /**
     * Store a newly created notification
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:200',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:info,success,warning,error',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:in_app,push,email,sms',
            'data' => 'nullable|array'
        ]);

        $results = $this->notificationService->sendBulkNotification(
            $request->user_ids,
            $request->title,
            $request->message,
            $request->type,
            $request->channels
        );

        $successCount = 0;
        $failureCount = 0;

        foreach ($results as $userId => $userResults) {
            foreach ($userResults as $channel => $success) {
                if ($success) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', "اعلان ارسال شد. موفق: {$successCount}, ناموفق: {$failureCount}");
    }

    /**
     * Display the specified notification
     */
    public function show(Notification $notification)
    {
        $notification->load('user');
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $this->notificationService->markAsRead($notification);

        return redirect()->route('admin.notifications.index')
            ->with('success', 'اعلان به عنوان خوانده شده علامت‌گذاری شد');
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user)
    {
        $this->notificationService->markAllAsRead($user);

        return redirect()->route('admin.notifications.index')
            ->with('success', 'همه اعلان‌های کاربر به عنوان خوانده شده علامت‌گذاری شد');
    }

    /**
     * Remove the specified notification
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'اعلان حذف شد');
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:200',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:info,success,warning,error',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:in_app,push,email,sms'
        ]);

        $user = User::find($request->user_id);
        
        $results = [];
        foreach ($request->channels as $channel) {
            switch ($channel) {
                case 'push':
                    $results['push'] = $this->notificationService->sendPushNotification($user, $request->title, $request->message);
                    break;
                case 'email':
                    $results['email'] = $this->notificationService->sendEmailNotification($user, $request->title, 'emails.notification', [
                        'title' => $request->title,
                        'message' => $request->message,
                        'user' => $user
                    ]);
                    break;
                case 'sms':
                    $results['sms'] = $this->notificationService->sendSmsNotification($user, $request->message);
                    break;
                case 'in_app':
                    $results['in_app'] = $this->notificationService->sendInAppNotification($user, $request->title, $request->message, $request->type);
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'اعلان تست ارسال شد',
            'results' => $results
        ]);
    }

    /**
     * Get notification statistics
     */
    public function statistics()
    {
        $stats = [
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::whereNull('read_at')->count(),
            'notifications_by_type' => Notification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type'),
            'notifications_today' => Notification::whereDate('created_at', today())->count(),
            'notifications_this_week' => Notification::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'notifications_this_month' => Notification::whereMonth('created_at', now()->month)->count(),
        ];

        return view('admin.notifications.statistics', compact('stats'));
    }
}
