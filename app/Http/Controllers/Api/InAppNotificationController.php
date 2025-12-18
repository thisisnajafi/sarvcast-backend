<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InAppNotificationService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class InAppNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(InAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notifications
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $filters = [
                'type' => $request->input('type'),
                'category' => $request->input('category'),
                'priority' => $request->input('priority'),
                'is_read' => $request->input('is_read'),
                'is_important' => $request->input('is_important')
            ];

            // Remove null values
            $filters = array_filter($filters, function($value) {
                return $value !== null;
            });

            $notifications = $this->notificationService->getUserNotifications($user->id, $perPage, $filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications->items(),
                    'unread_count' => $this->notificationService->getUnreadCount($user->id),
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'has_more' => $notifications->hasMorePages()
                    ]
                ],
                'message' => 'اعلان‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user notifications', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اعلان‌ها'
            ], 500);
        }
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $count = $this->notificationService->getUnreadCount($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count
                ],
                'message' => 'تعداد اعلان‌های خوانده نشده دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get unread count', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تعداد اعلان‌های خوانده نشده'
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        try {
            $user = $request->user();
            $success = $this->notificationService->markAsRead($notificationId, $user->id);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'اعلان به عنوان خوانده شده علامت‌گذاری شد'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'اعلان یافت نشد یا قبلاً خوانده شده است'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در علامت‌گذاری اعلان'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $count = $this->notificationService->markAllAsRead($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'marked_count' => $count
                ],
                'message' => "{$count} اعلان به عنوان خوانده شده علامت‌گذاری شد"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در علامت‌گذاری همه اعلان‌ها'
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, int $notificationId): JsonResponse
    {
        try {
            $user = $request->user();
            $success = $this->notificationService->deleteNotification($notificationId, $user->id);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'اعلان حذف شد'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'اعلان یافت نشد'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'notification_id' => $notificationId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف اعلان'
            ], 500);
        }
    }

    /**
     * Create notification (admin only)
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'type' => 'required|string|in:info,success,warning,error,subscription,payment,content,system',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'data' => 'nullable|array',
            'action_type' => 'nullable|string|in:link,button,dismiss',
            'action_url' => 'nullable|string|max:500',
            'action_text' => 'nullable|string|max:100',
            'is_important' => 'nullable|boolean',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'category' => 'nullable|string|in:subscription,payment,content,system,security,promotion',
            'expires_at' => 'nullable|date|after:now'
        ], [
            'user_id.required' => 'شناسه کاربر الزامی است',
            'user_id.exists' => 'کاربر یافت نشد',
            'type.required' => 'نوع اعلان الزامی است',
            'type.in' => 'نوع اعلان نامعتبر است',
            'title.required' => 'عنوان اعلان الزامی است',
            'title.max' => 'عنوان اعلان نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'message.required' => 'متن اعلان الزامی است',
            'message.max' => 'متن اعلان نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'data.array' => 'داده‌ها باید آرایه باشند',
            'action_type.in' => 'نوع عمل نامعتبر است',
            'action_url.max' => 'آدرس عمل نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'action_text.max' => 'متن عمل نمی‌تواند بیشتر از 100 کاراکتر باشد',
            'priority.in' => 'اولویت نامعتبر است',
            'category.in' => 'دسته‌بندی نامعتبر است',
            'expires_at.date' => 'تاریخ انقضا نامعتبر است',
            'expires_at.after' => 'تاریخ انقضا باید در آینده باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->input('user_id');
            $type = $request->input('type');
            $title = $request->input('title');
            $message = $request->input('message');
            $options = [
                'data' => $request->input('data'),
                'action_type' => $request->input('action_type'),
                'action_url' => $request->input('action_url'),
                'action_text' => $request->input('action_text'),
                'is_important' => $request->input('is_important', false),
                'priority' => $request->input('priority', 'normal'),
                'category' => $request->input('category'),
                'expires_at' => $request->input('expires_at')
            ];

            $notification = $this->notificationService->createNotification($userId, $type, $title, $message, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'notification' => $notification->summary
                ],
                'message' => 'اعلان ایجاد شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'user_id' => $request->input('user_id'),
                'type' => $request->input('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد اعلان'
            ], 500);
        }
    }

    /**
     * Send notification to multiple users (admin only)
     */
    public function sendToMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1|max:1000',
            'user_ids.*' => 'integer|exists:users,id',
            'type' => 'required|string|in:info,success,warning,error,subscription,payment,content,system',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'data' => 'nullable|array',
            'action_type' => 'nullable|string|in:link,button,dismiss',
            'action_url' => 'nullable|string|max:500',
            'action_text' => 'nullable|string|max:100',
            'is_important' => 'nullable|boolean',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'category' => 'nullable|string|in:subscription,payment,content,system,security,promotion',
            'expires_at' => 'nullable|date|after:now'
        ], [
            'user_ids.required' => 'شناسه‌های کاربران الزامی است',
            'user_ids.array' => 'شناسه‌های کاربران باید آرایه باشند',
            'user_ids.min' => 'حداقل یک کاربر الزامی است',
            'user_ids.max' => 'حداکثر 1000 کاربر مجاز است',
            'user_ids.*.exists' => 'یکی از کاربران یافت نشد',
            'type.required' => 'نوع اعلان الزامی است',
            'type.in' => 'نوع اعلان نامعتبر است',
            'title.required' => 'عنوان اعلان الزامی است',
            'title.max' => 'عنوان اعلان نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'message.required' => 'متن اعلان الزامی است',
            'message.max' => 'متن اعلان نمی‌تواند بیشتر از 1000 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userIds = $request->input('user_ids');
            $type = $request->input('type');
            $title = $request->input('title');
            $message = $request->input('message');
            $options = [
                'data' => $request->input('data'),
                'action_type' => $request->input('action_type'),
                'action_url' => $request->input('action_url'),
                'action_text' => $request->input('action_text'),
                'is_important' => $request->input('is_important', false),
                'priority' => $request->input('priority', 'normal'),
                'category' => $request->input('category'),
                'expires_at' => $request->input('expires_at')
            ];

            $result = $this->notificationService->sendToMultipleUsers($userIds, $type, $title, $message, $options);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "اعلان به {$result['success_count']} کاربر ارسال شد"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send notifications to multiple users', [
                'user_ids' => $request->input('user_ids'),
                'type' => $request->input('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال اعلان‌ها'
            ], 500);
        }
    }

    /**
     * Get notification statistics (admin only)
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->notificationService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار اعلان‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get notification statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار اعلان‌ها'
            ], 500);
        }
    }

    /**
     * Get notification types
     */
    public function getTypes(): JsonResponse
    {
        try {
            $types = $this->notificationService->getTypes();

            return response()->json([
                'success' => true,
                'data' => [
                    'types' => $types
                ],
                'message' => 'انواع اعلان‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get notification types', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت انواع اعلان‌ها'
            ], 500);
        }
    }

    /**
     * Get notification priorities
     */
    public function getPriorities(): JsonResponse
    {
        try {
            $priorities = $this->notificationService->getPriorities();

            return response()->json([
                'success' => true,
                'data' => [
                    'priorities' => $priorities
                ],
                'message' => 'اولویت‌های اعلان دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get notification priorities', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اولویت‌های اعلان'
            ], 500);
        }
    }

    /**
     * Get notification categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = $this->notificationService->getCategories();

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories
                ],
                'message' => 'دسته‌بندی‌های اعلان دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get notification categories', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت دسته‌بندی‌های اعلان'
            ], 500);
        }
    }

    /**
     * Cleanup expired notifications (admin only)
     */
    public function cleanupExpired(): JsonResponse
    {
        try {
            $count = $this->notificationService->cleanupExpired();

            return response()->json([
                'success' => true,
                'data' => [
                    'cleaned_count' => $count
                ],
                'message' => "{$count} اعلان منقضی شده حذف شد"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired notifications', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پاکسازی اعلان‌های منقضی شده'
            ], 500);
        }
    }
}
