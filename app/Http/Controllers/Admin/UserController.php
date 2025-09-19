<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\PlayHistory;
use App\Models\Favorite;
use App\Models\Rating;
use App\Services\InAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $notificationService;

    public function __construct(InAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['profiles', 'activeSubscription', 'subscriptions', 'payments']);

        // Apply filters
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('has_subscription')) {
            if ($request->boolean('has_subscription')) {
                $query->whereHas('subscriptions', function($q) {
                    $q->where('status', 'active')->where('end_date', '>', now());
                });
            } else {
                $query->whereDoesntHave('subscriptions', function($q) {
                    $q->where('status', 'active')->where('end_date', '>', now());
                });
            }
        }

        if ($request->filled('subscription_type')) {
            $query->whereHas('subscriptions', function($q) use ($request) {
                $q->where('type', $request->subscription_type)
                  ->where('status', 'active')
                  ->where('end_date', '>', now());
            });
        }

        if ($request->filled('has_payment')) {
            if ($request->boolean('has_payment')) {
                $query->whereHas('payments', function($q) {
                    $q->where('status', 'completed');
                });
            } else {
                $query->whereDoesntHave('payments', function($q) {
                    $q->where('status', 'completed');
                });
            }
        }

        if ($request->filled('min_payment_amount')) {
            $query->whereHas('payments', function($q) use ($request) {
                $q->where('status', 'completed')
                  ->where('amount', '>=', $request->min_payment_amount);
            });
        }

        if ($request->filled('max_payment_amount')) {
            $query->whereHas('payments', function($q) use ($request) {
                $q->where('status', 'completed')
                  ->where('amount', '<=', $request->max_payment_amount);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('last_login_from')) {
            $query->whereDate('last_login_at', '>=', $request->last_login_from);
        }

        if ($request->filled('last_login_to')) {
            $query->whereDate('last_login_at', '<=', $request->last_login_to);
        }

        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        if ($request->filled('timezone')) {
            $query->where('timezone', $request->timezone);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $request->search . '%')
                  ->orWhere('id', 'like', '%' . $request->search . '%');
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        switch ($sort) {
            case 'name':
                $query->orderBy('first_name', $direction)->orderBy('last_name', $direction);
                break;
            case 'email':
                $query->orderBy('email', $direction);
                break;
            case 'phone_number':
                $query->orderBy('phone_number', $direction);
                break;
            case 'status':
                $query->orderBy('status', $direction);
                break;
            case 'role':
                $query->orderBy('role', $direction);
                break;
            case 'last_login_at':
                $query->orderBy('last_login_at', $direction);
                break;
            case 'total_payments':
                $query->withCount(['payments as total_payments' => function($q) {
                    $q->where('status', 'completed');
                }])->orderBy('total_payments', $direction);
                break;
            case 'total_play_time':
                $query->withSum('playHistories', 'duration_played')->orderBy('play_histories_sum_duration_played', $direction);
                break;
            default:
                $query->orderBy('created_at', $direction);
        }

        $perPage = $request->get('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page

        $users = $query->paginate($perPage);
        
        // Get statistics
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'pending' => User::where('status', 'pending')->count(),
            'parents' => User::where('role', 'parent')->count(),
            'children' => User::where('role', 'child')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'with_subscription' => User::whereHas('subscriptions', function($q) {
                $q->where('status', 'active')->where('end_date', '>', now());
            })->count(),
            'without_subscription' => User::whereDoesntHave('subscriptions', function($q) {
                $q->where('status', 'active')->where('end_date', '>', now());
            })->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'avg_payment' => Payment::where('status', 'completed')->avg('amount'),
            'total_play_time' => PlayHistory::sum('duration_played'),
            'total_ratings' => Rating::count(),
            'total_favorites' => Favorite::count()
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parents = User::where('role', 'parent')->get();
        return view('admin.users.create', compact('parents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'phone_number' => 'nullable|string|unique:users',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:parent,child,admin',
            'parent_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,suspended,pending',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ]);

        $data = $request->except(['password_confirmation']);
        $data['password'] = Hash::make($request->password);

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load(['profiles', 'activeSubscription', 'subscriptions', 'payments', 'playHistories', 'favorites', 'ratings']);
        
        // Get recent activity
        $recentActivity = $user->playHistories()
            ->with('episode.story')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.users.show', compact('user', 'recentActivity'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $parents = User::where('role', 'parent')->where('id', '!=', $user->id)->get();
        return view('admin.users.edit', compact('user', 'parents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $user->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:parent,child,admin',
            'parent_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,suspended,pending',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ]);

        $data = $request->except(['password_confirmation']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deletion of admin users
        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'نمی‌توان کاربران مدیر را حذف کرد.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت حذف شد.');
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $message = $newStatus === 'active' ? 'کاربر فعال شد.' : 'کاربر غیرفعال شد.';
        
        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * Suspend user
     */
    public function suspend(User $user)
    {
        $user->update(['status' => 'suspended']);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر معلق شد.');
    }

    /**
     * Activate user
     */
    public function activate(User $user)
    {
        $user->update(['status' => 'active']);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر فعال شد.');
    }

    /**
     * Bulk operations on users
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:activate,deactivate,suspend,delete,change_role,change_status,send_notification',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'status' => 'required_if:action,change_status|string|in:active,inactive,suspended,pending',
            'role' => 'required_if:action,change_role|string|in:parent,child,admin',
            'notification_title' => 'required_if:action,send_notification|string|max:255',
            'notification_message' => 'required_if:action,send_notification|string|max:1000',
            'notification_type' => 'required_if:action,send_notification|string|in:info,success,warning,error',
            'notification_priority' => 'nullable|string|in:low,normal,high,urgent'
        ], [
            'action.required' => 'عملیات الزامی است',
            'action.in' => 'عملیات نامعتبر است',
            'user_ids.required' => 'انتخاب حداقل یک کاربر الزامی است',
            'user_ids.array' => 'شناسه‌های کاربران باید آرایه باشند',
            'user_ids.min' => 'حداقل یک کاربر باید انتخاب شود',
            'user_ids.*.exists' => 'یکی از کاربران یافت نشد',
            'status.required_if' => 'وضعیت الزامی است',
            'status.in' => 'وضعیت نامعتبر است',
            'role.required_if' => 'نقش الزامی است',
            'role.in' => 'نقش نامعتبر است',
            'notification_title.required_if' => 'عنوان اعلان الزامی است',
            'notification_title.max' => 'عنوان اعلان نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'notification_message.required_if' => 'متن اعلان الزامی است',
            'notification_message.max' => 'متن اعلان نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'notification_type.required_if' => 'نوع اعلان الزامی است',
            'notification_type.in' => 'نوع اعلان نامعتبر است',
            'notification_priority.in' => 'اولویت اعلان نامعتبر است'
        ]);

        try {
            DB::beginTransaction();

            $userIds = $request->user_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);

                    // Prevent operations on admin users
                    if ($user->isAdmin() && in_array($action, ['delete', 'suspend', 'change_role'])) {
                        $failureCount++;
                        continue;
                    }

                    switch ($action) {
                        case 'activate':
                            $user->update(['status' => 'active']);
                            break;

                        case 'deactivate':
                            $user->update(['status' => 'inactive']);
                            break;

                        case 'suspend':
                            $user->update(['status' => 'suspended']);
                            break;

                        case 'delete':
                            $user->delete();
                            break;

                        case 'change_status':
                            $user->update(['status' => $request->status]);
                            break;

                        case 'change_role':
                            $user->update(['role' => $request->role]);
                            break;

                        case 'send_notification':
                            $this->notificationService->createNotification(
                                $user->id,
                                $request->notification_type,
                                $request->notification_title,
                                $request->notification_message,
                                [
                                    'priority' => $request->notification_priority ?? 'normal',
                                    'is_important' => $request->notification_priority === 'urgent'
                                ]
                            );
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for user', [
                        'user_id' => $userId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $message = "عملیات {$action} روی {$successCount} کاربر انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} کاربر ناموفق بود";
            }

            return redirect()->route('admin.users.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'user_ids' => $request->user_ids,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'خطا در انجام عملیات گروهی: ' . $e->getMessage());
        }
    }

    /**
     * Export users
     */
    public function export(Request $request)
    {
        $query = User::with(['profiles', 'activeSubscription']);

        // Apply same filters as index
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('has_subscription')) {
            if ($request->boolean('has_subscription')) {
                $query->whereHas('subscriptions', function($q) {
                    $q->where('status', 'active')->where('end_date', '>', now());
                });
            } else {
                $query->whereDoesntHave('subscriptions', function($q) {
                    $q->where('status', 'active')->where('end_date', '>', now());
                });
            }
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'نام', 'نام خانوادگی', 'ایمیل', 'شماره تلفن', 'نقش', 'وضعیت',
            'زبان', 'منطقه زمانی', 'اشتراک فعال', 'تاریخ عضویت', 'آخرین ورود'
        ];

        foreach ($users as $user) {
            $csvData[] = [
                $user->id,
                $user->first_name,
                $user->last_name,
                $user->email,
                $user->phone_number,
                $user->role,
                $user->status,
                $user->language,
                $user->timezone,
                $user->activeSubscription ? $user->activeSubscription->type : 'بدون اشتراک',
                $user->created_at->format('Y-m-d H:i:s'),
                $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'هرگز'
            ];
        }

        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get user statistics
     */
    public function statistics()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'pending_users' => User::where('status', 'pending')->count(),
            'parent_users' => User::where('role', 'parent')->count(),
            'child_users' => User::where('role', 'child')->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'users_with_subscription' => User::whereHas('subscriptions', function($q) {
                $q->where('status', 'active')->where('end_date', '>', now());
            })->count(),
            'users_without_subscription' => User::whereDoesntHave('subscriptions', function($q) {
                $q->where('status', 'active')->where('end_date', '>', now());
            })->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'avg_payment_amount' => round(Payment::where('status', 'completed')->avg('amount'), 2),
            'total_payments' => Payment::where('status', 'completed')->count(),
            'total_play_time' => PlayHistory::sum('duration_played'),
            'total_ratings' => Rating::count(),
            'total_favorites' => Favorite::count(),
            'users_by_status' => User::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->get(),
            'users_by_subscription_type' => User::selectRaw('subscriptions.type, COUNT(*) as count')
                ->join('subscriptions', 'users.id', '=', 'subscriptions.user_id')
                ->where('subscriptions.status', 'active')
                ->where('subscriptions.end_date', '>', now())
                ->groupBy('subscriptions.type')
                ->get(),
            'recent_users' => User::with(['activeSubscription'])
                ->latest()
                ->limit(10)
                ->get(),
            'top_paying_users' => User::withSum(['payments as total_payments' => function($q) {
                $q->where('status', 'completed');
            }], 'amount')
            ->orderBy('total_payments', 'desc')
            ->limit(10)
            ->get(),
            'most_active_users' => User::withSum('playHistories', 'duration_played')
                ->orderBy('play_histories_sum_duration_played', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get user activity details
     */
    public function activity(User $user)
    {
        $activity = [
            'play_histories' => $user->playHistories()
                ->with(['episode.story'])
                ->latest()
                ->limit(20)
                ->get(),
            'favorites' => $user->favorites()
                ->with(['story'])
                ->latest()
                ->limit(20)
                ->get(),
            'ratings' => $user->ratings()
                ->with(['story', 'episode'])
                ->latest()
                ->limit(20)
                ->get(),
            'subscriptions' => $user->subscriptions()
                ->latest()
                ->get(),
            'payments' => $user->payments()
                ->latest()
                ->limit(20)
                ->get(),
            'notifications' => $user->notifications()
                ->latest()
                ->limit(20)
                ->get(),
            'stats' => [
                'total_play_time' => $user->playHistories()->sum('duration_played'),
                'total_episodes_played' => $user->playHistories()->distinct('episode_id')->count(),
                'total_stories_played' => $user->playHistories()->join('episodes', 'play_histories.episode_id', '=', 'episodes.id')
                    ->distinct('episodes.story_id')->count(),
                'total_favorites' => $user->favorites()->count(),
                'total_ratings' => $user->ratings()->count(),
                'avg_rating_given' => round($user->ratings()->avg('rating'), 2),
                'total_payments' => $user->payments()->where('status', 'completed')->count(),
                'total_spent' => $user->payments()->where('status', 'completed')->sum('amount'),
                'subscription_count' => $user->subscriptions()->count(),
                'current_subscription' => $user->activeSubscription
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    /**
     * Send notification to user
     */
    public function sendNotification(Request $request, User $user)
    {
        $request->validate([
            'type' => 'required|string|in:info,success,warning,error,subscription,payment,content,system',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'is_important' => 'nullable|boolean',
            'action_type' => 'nullable|string|in:link,button,dismiss',
            'action_url' => 'nullable|string|max:500',
            'action_text' => 'nullable|string|max:100',
            'expires_at' => 'nullable|date|after:now'
        ], [
            'type.required' => 'نوع اعلان الزامی است',
            'type.in' => 'نوع اعلان نامعتبر است',
            'title.required' => 'عنوان اعلان الزامی است',
            'title.max' => 'عنوان اعلان نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'message.required' => 'متن اعلان الزامی است',
            'message.max' => 'متن اعلان نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'priority.in' => 'اولویت نامعتبر است',
            'action_type.in' => 'نوع عمل نامعتبر است',
            'action_url.max' => 'آدرس عمل نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'action_text.max' => 'متن عمل نمی‌تواند بیشتر از 100 کاراکتر باشد',
            'expires_at.date' => 'تاریخ انقضا نامعتبر است',
            'expires_at.after' => 'تاریخ انقضا باید در آینده باشد'
        ]);

        try {
            $notification = $this->notificationService->createNotification(
                $user->id,
                $request->type,
                $request->title,
                $request->message,
                [
                    'priority' => $request->priority ?? 'normal',
                    'is_important' => $request->boolean('is_important'),
                    'action_type' => $request->action_type,
                    'action_url' => $request->action_url,
                    'action_text' => $request->action_text,
                    'expires_at' => $request->expires_at
                ]
            );

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'اعلان با موفقیت ارسال شد.');

        } catch (\Exception $e) {
            Log::error('Failed to send notification to user', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.users.show', $user)
                ->with('error', 'خطا در ارسال اعلان: ' . $e->getMessage());
        }
    }

    /**
     * Get user profile details
     */
    public function profile(User $user)
    {
        $user->load(['profiles', 'activeSubscription', 'subscriptions', 'payments']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'profiles' => $user->profiles,
                'subscriptions' => $user->subscriptions,
                'payments' => $user->payments,
                'stats' => [
                    'total_play_time' => $user->playHistories()->sum('duration_played'),
                    'total_favorites' => $user->favorites()->count(),
                    'total_ratings' => $user->ratings()->count(),
                    'total_payments' => $user->payments()->where('status', 'completed')->count(),
                    'total_spent' => $user->payments()->where('status', 'completed')->sum('amount')
                ]
            ]
        ]);
    }
}
