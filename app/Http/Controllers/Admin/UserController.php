<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\PlayHistory;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\Permission;
use App\Models\Role;
use App\Services\InAppNotificationService;
use App\Services\UserDeletionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $notificationService;

    protected UserDeletionService $userDeletionService;

    public function __construct(
        InAppNotificationService $notificationService,
        UserDeletionService $userDeletionService,
    ) {
        $this->notificationService = $notificationService;
        $this->userDeletionService = $userDeletionService;
    }
    /**
     * Search users for admin forms - based on mobile phone number
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        // Search primarily by phone number, with fallback to name for display
        $users = User::where(function($q) use ($query) {
                $q->where('phone_number', 'LIKE', "%{$query}%")
                  ->orWhere('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
            })
            ->select('id', 'first_name', 'last_name', 'phone_number', 'status')
            ->where('status', 'active')
            ->orderByRaw("CASE WHEN phone_number LIKE ? THEN 1 ELSE 2 END", ["%{$query}%"])
            ->orderBy('first_name', 'asc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
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
            'phone_number' => 'required|string|unique:users',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:parent,child,admin,basic',
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
            ->orderBy('played_at', 'desc')
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
        $availableRoles = \App\Models\Role::all();
        $permissions = \App\Models\Permission::all()->groupBy('group');
        
        return view('admin.users.edit', compact('user', 'parents', 'availableRoles', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
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

        $this->userDeletionService->deleteUser($user);

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
     * Change user role
     */
    public function changeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:' . implode(',', [
                User::ROLE_BASIC,
                User::ROLE_PARENT,
                User::ROLE_CHILD,
                User::ROLE_VOICE_ACTOR,
                User::ROLE_ADMIN,
                User::ROLE_SUPER_ADMIN
            ]),
        ]);

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        // Update 2FA requirement if role changed to/from admin
        $user->update2FARequirement();

        return redirect()->back()
            ->with('success', "نقش کاربر از {$oldRole} به {$validated['role']} تغییر کرد.");
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
            'role' => 'required_if:action,change_role|string|in:parent,child,admin,basic,voice_actor,super_admin',
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
            $failures = [];
            $actor = auth('web')->user() ?? auth('sanctum')->user();

            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);
                    $result = $this->applyBulkUserAction($actor, $user, $action, $request);

                    if ($result['success']) {
                        $successCount++;
                        continue;
                    }

                    $failureCount++;
                    $failures[] = [
                        'user_id' => $userId,
                        'reason' => $result['reason'] ?? 'عملیات برای این کاربر مجاز نیست.',
                    ];
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
                  ->orWhere('phone_number', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'نام', 'نام خانوادگی', 'شماره تلفن', 'نقش', 'وضعیت',
            'زبان', 'منطقه زمانی', 'اشتراک فعال', 'تاریخ عضویت', 'آخرین ورود'
        ];

        foreach ($users as $user) {
            $csvData[] = [
                $user->id,
                $user->first_name,
                $user->last_name,
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
                ->orderBy('played_at', 'desc')
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

    /**
     * Toggle permission for a user
     */
    public function togglePermission(Request $request, User $user, Permission $permission)
    {
        try {
            $action = $request->input('action'); // 'grant' or 'revoke'
            
            if ($action === 'grant') {
                // Grant direct permission to user
                if (!$user->directPermissions()->where('permissions.id', $permission->id)->exists()) {
                    $user->directPermissions()->attach($permission);
                }
                $message = 'مجوز با موفقیت به کاربر اعطا شد.';
            } elseif ($action === 'revoke') {
                // Revoke direct permission from user
                $user->directPermissions()->detach($permission);
                $message = 'مجوز با موفقیت از کاربر حذف شد.';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'عمل نامعتبر است.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling permission: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعمال تغییرات مجوز.'
            ], 500);
        }
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = $this->buildUserApiListQuery($request);
        $this->applyUserListSort($query, $request);

        $perPage = $this->resolveUserListPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->with(['profiles', 'activeSubscription', 'subscriptions', 'payments'])
            ->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildUserApiListQuery($request);
        $this->applyUserListSort($query, $request);

        $filename = 'users-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'first_name', 'last_name', 'phone_number', 'role', 'status', 'created_at']);

            $query->clone()->select(['id', 'first_name', 'last_name', 'phone_number', 'role', 'status', 'created_at'])
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->first_name,
                            $row->last_name,
                            $row->phone_number,
                            $row->role,
                            $row->status,
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
        $roleValues = ['parent', 'child', 'admin', 'basic', 'super_admin', 'voice_actor'];

        $request->validate([
            'phone_number' => 'nullable|string|unique:users',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in($roleValues)],
            'parent_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,suspended,pending',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ]);

        $data = $request->except(['password_confirmation']);
        $data['password'] = Hash::make($request->password);

        $user = User::create($data);

        return AdminApiResponse::success(
            $user->load(['profiles', 'activeSubscription']),
            'کاربر با موفقیت ایجاد شد.',
            201
        );
    }

    public function apiShow(User $user)
    {
        $user->load(['profiles', 'activeSubscription', 'subscriptions', 'payments', 'playHistories', 'favorites', 'ratings', 'roles.permissions', 'directPermissions']);

        return AdminApiResponse::success($this->formatUserForApi($user));
    }

    public function apiUpdate(Request $request, User $user)
    {
        $roleValues = ['parent', 'child', 'admin', 'basic', 'super_admin', 'voice_actor'];

        $request->validate([
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $user->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in($roleValues)],
            'parent_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive,suspended,pending',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
            'direct_permission_ids' => 'nullable|array',
            'direct_permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $data = $request->except(['password_confirmation', 'role_ids', 'direct_permission_ids']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $previousRole = $user->role;
        $newRole = $data['role'] ?? $previousRole;
        unset($data['role']);

        $user->update($data);

        if ($newRole !== $previousRole) {
            $user->applyLegacyRoleChange($newRole);
        }

        if ($request->has('role_ids')) {
            $user->syncRoles($request->input('role_ids', []));
            $user->update2FARequirement();
        }

        if ($request->has('direct_permission_ids')) {
            $user->directPermissions()->sync($request->input('direct_permission_ids', []));
        }

        $user->load(['profiles', 'activeSubscription', 'roles.permissions', 'directPermissions']);

        return AdminApiResponse::success(
            $this->formatUserForApi($user),
            'کاربر با موفقیت به‌روزرسانی شد.'
        );
    }

    public function apiDestroy(User $user)
    {
        // Prevent deletion of admin users
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توان کاربران مدیر را حذف کرد.',
                'error' => 'FORBIDDEN',
            ], 403);
        }

        $this->userDeletionService->deleteUser($user);

        return AdminApiResponse::okMessage('کاربر با موفقیت حذف شد.');
    }

    public function apiSendNotification(Request $request, User $user)
    {
        $request->validate([
            'type' => 'required|string|in:info,success,warning,error,subscription,payment,content,system',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
        ]);

        try {
            $notification = $this->notificationService->createNotification(
                $user->id,
                $request->type,
                $request->title,
                $request->message,
                ['priority' => $request->priority ?? 'normal']
            );

            return AdminApiResponse::success($notification, 'اعلان با موفقیت ارسال شد.');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال اعلان: '.$e->getMessage(),
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:activate,deactivate,suspend,delete,change_role,change_status,send_notification',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'integer|exists:users,id',
            'status' => 'required_if:action,change_status|string|in:active,inactive,suspended,pending',
            'role' => 'required_if:action,change_role|string|in:parent,child,admin,basic,voice_actor,super_admin',
            'notification_title' => 'required_if:action,send_notification|string|max:255',
            'notification_message' => 'required_if:action,send_notification|string|max:1000',
            'notification_type' => 'required_if:action,send_notification|string|in:info,success,warning,error',
            'notification_priority' => 'nullable|string|in:low,normal,high,urgent',
        ]);

        $userIds = $validated['user_ids'] ?? $validated['selected_items'] ?? [];
        if (count($userIds) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ کاربری انتخاب نشده است.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;
            $failures = [];
            $actor = auth('sanctum')->user() ?? auth('web')->user();

            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);
                    $result = $this->applyBulkUserAction($actor, $user, $action, $request);

                    if ($result['success']) {
                        $successCount++;
                        continue;
                    }

                    $failureCount++;
                    $failures[] = [
                        'user_id' => $userId,
                        'reason' => $result['reason'] ?? 'عملیات برای این کاربر مجاز نیست.',
                    ];
                } catch (\Exception $e) {
                    $failureCount++;
                    $failures[] = [
                        'user_id' => $userId,
                        'reason' => 'خطای داخلی سرور.',
                    ];
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

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'failures' => $failures,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'user_ids' => $userIds,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات گروهی: '.$e->getMessage(),
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiStatistics()
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

        return AdminApiResponse::success($stats);
    }

    private function buildUserApiListQuery(Request $request)
    {
        $query = User::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('phone_number', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('has_subscription')) {
            if ($request->boolean('has_subscription')) {
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('status', 'active')->where('end_date', '>', now());
                });
            } else {
                $query->whereDoesntHave('subscriptions', function ($q) {
                    $q->where('status', 'active')->where('end_date', '>', now());
                });
            }
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
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

    private function applyUserListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'first_name', 'last_name', 'role', 'status', 'phone_number'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveUserListPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }

    /**
     * @return array{success: bool, reason?: string}
     */
    private function applyBulkUserAction(?User $actor, User $target, string $action, Request $request): array
    {
        $newRole = $action === 'change_role' ? (string) $request->input('role') : null;
        $blockReason = $this->resolveBulkUserActionBlock($actor, $target, $action, $newRole);

        if ($blockReason !== null) {
            return ['success' => false, 'reason' => $blockReason];
        }

        switch ($action) {
            case 'activate':
                $target->update(['status' => 'active']);
                break;

            case 'deactivate':
                $target->update(['status' => 'inactive']);
                break;

            case 'suspend':
                $target->update(['status' => 'suspended']);
                break;

            case 'delete':
                $this->userDeletionService->deleteUser($target);
                break;

            case 'change_status':
                $target->update(['status' => $request->status]);
                break;

            case 'change_role':
                $target->applyLegacyRoleChange((string) $request->role);
                break;

            case 'send_notification':
                $this->notificationService->createNotification(
                    $target->id,
                    $request->notification_type,
                    $request->notification_title,
                    $request->notification_message,
                    [
                        'priority' => $request->notification_priority ?? 'normal',
                        'is_important' => $request->notification_priority === 'urgent',
                    ]
                );
                break;
        }

        return ['success' => true];
    }

    private function resolveBulkUserActionBlock(?User $actor, User $target, string $action, ?string $newRole = null): ?string
    {
        if ($actor === null) {
            return 'کاربر احراز هویت نشده است.';
        }

        if ($actor->id === $target->id && in_array($action, ['delete', 'suspend', 'change_role'], true)) {
            return 'امکان انجام این عملیات روی حساب کاربری خودتان وجود ندارد.';
        }

        if ($action === 'change_role' && $newRole !== null) {
            if (in_array($newRole, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true) && ! $actor->isSuperAdmin()) {
                return 'فقط مدیر کل می‌تواند نقش مدیر یا مدیر کل اختصاص دهد.';
            }
        }

        if ($target->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return 'فقط مدیر کل می‌تواند کاربران مدیر کل را تغییر دهد.';
        }

        if ($target->isAdmin() && in_array($action, ['delete', 'suspend', 'change_role'], true) && ! $actor->isSuperAdmin()) {
            return 'فقط مدیر کل می‌تواند نقش یا وضعیت کاربران مدیر را تغییر دهد.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserForApi(User $user): array
    {
        $user->loadMissing(['roles.permissions', 'directPermissions']);

        $data = $user->toArray();
        $data['role_ids'] = $user->roles->pluck('id')->values()->all();
        $data['direct_permission_ids'] = $user->directPermissions->pluck('id')->values()->all();
        $data['assigned_roles'] = $user->roles->map(static fn (Role $role) => [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
        ])->values()->all();
        $data['direct_permissions'] = $user->directPermissions->map(static fn (Permission $permission) => [
            'id' => $permission->id,
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'group' => $permission->group,
        ])->values()->all();

        $rolePermissionNames = $user->roles
            ->flatMap(static fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values();

        $data['effective_permissions'] = $rolePermissionNames
            ->merge($user->directPermissions->pluck('name'))
            ->unique()
            ->values()
            ->all();

        return $data;
    }
}
