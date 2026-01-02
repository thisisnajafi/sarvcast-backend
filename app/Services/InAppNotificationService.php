<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InAppNotificationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }

    /**
     * Notification types
     */
    const TYPES = [
        'info' => 'اطلاعات',
        'success' => 'موفقیت',
        'warning' => 'هشدار',
        'error' => 'خطا',
        'subscription' => 'اشتراک',
        'payment' => 'پرداخت',
        'content' => 'محتوا',
        'system' => 'سیستم'
    ];

    /**
     * Notification priorities
     */
    const PRIORITIES = [
        'low' => 'کم',
        'normal' => 'عادی',
        'high' => 'بالا',
        'urgent' => 'فوری'
    ];

    /**
     * Notification categories
     */
    const CATEGORIES = [
        'subscription' => 'اشتراک',
        'payment' => 'پرداخت',
        'content' => 'محتوا',
        'system' => 'سیستم',
        'security' => 'امنیت',
        'promotion' => 'تبلیغات'
    ];

    /**
     * Create a new notification
     */
    public function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): Notification {
        try {
            $notification = Notification::createNotification($userId, $type, $title, $message, $options);

            // Send push notification if user has FCM token
            $user = User::find($userId);
            if ($user) {
                try {
                    // Extract data for push notification
                    $pushData = $options['data'] ?? [];
                    if (isset($options['story_id'])) {
                        $pushData['story_id'] = $options['story_id'];
                        $pushData['type'] = 'story';
                    }
                    if (isset($options['episode_id'])) {
                        $pushData['episode_id'] = $options['episode_id'];
                        $pushData['type'] = 'episode';
                    }
                    if (isset($options['subscription_id'])) {
                        $pushData['subscription_id'] = $options['subscription_id'];
                        $pushData['type'] = 'subscription';
                    }
                    if (isset($options['payment_id'])) {
                        $pushData['payment_id'] = $options['payment_id'];
                        $pushData['type'] = 'payment';
                    }

                    // Send push notification
                    $this->notificationService->sendPushNotification($user, $title, $message, $pushData);
                } catch (\Exception $e) {
                    // Log but don't fail if push notification fails
                    Log::warning('Failed to send push notification', [
                        'user_id' => $userId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('In-app notification created', [
                'user_id' => $userId,
                'notification_id' => $notification->id,
                'type' => $type,
                'title' => $title
            ]);

            return $notification;

        } catch (\Exception $e) {
            Log::error('Failed to create in-app notification', [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create subscription notification
     */
    public function createSubscriptionNotification(
        int $userId,
        string $title,
        string $message,
        array $options = []
    ): Notification {
        return $this->createNotification($userId, 'subscription', $title, $message, array_merge($options, [
            'category' => 'subscription',
            'priority' => 'high'
        ]));
    }

    /**
     * Create payment notification
     */
    public function createPaymentNotification(
        int $userId,
        string $title,
        string $message,
        array $options = []
    ): Notification {
        return $this->createNotification($userId, 'payment', $title, $message, array_merge($options, [
            'category' => 'payment',
            'priority' => 'high'
        ]));
    }

    /**
     * Create content notification
     */
    public function createContentNotification(
        int $userId,
        string $title,
        string $message,
        array $options = []
    ): Notification {
        return $this->createNotification($userId, 'content', $title, $message, array_merge($options, [
            'category' => 'content',
            'priority' => 'normal'
        ]));
    }

    /**
     * Create system notification
     */
    public function createSystemNotification(
        int $userId,
        string $title,
        string $message,
        array $options = []
    ): Notification {
        return $this->createNotification($userId, 'system', $title, $message, array_merge($options, [
            'category' => 'system',
            'priority' => $options['priority'] ?? 'normal'
        ]));
    }

    /**
     * Create welcome notification
     */
    public function createWelcomeNotification(int $userId): Notification
    {
        return $this->createNotification($userId, 'success', 'خوش آمدید!', 'به سروکست خوش آمدید! از شنیدن داستان‌های زیبا لذت ببرید.', [
            'category' => 'system',
            'priority' => 'normal',
            'is_important' => true,
            'action_type' => 'button',
            'action_text' => 'شروع کنید',
            'action_url' => '/stories'
        ]);
    }

    /**
     * Create subscription activated notification
     */
    public function createSubscriptionActivatedNotification(int $userId, string $planType): Notification
    {
        $planNames = [
            'monthly' => 'ماهانه',
            'quarterly' => 'سه‌ماهه',
            'yearly' => 'سالانه',
            'family' => 'خانوادگی'
        ];

        $planName = $planNames[$planType] ?? $planType;

        return $this->createSubscriptionNotification($userId, 'اشتراک فعال شد', "اشتراک {$planName} شما با موفقیت فعال شد. از دسترسی کامل به محتوا لذت ببرید.", [
            'is_important' => true,
            'action_type' => 'button',
            'action_text' => 'مشاهده محتوا',
            'action_url' => '/stories',
            'data' => ['plan_type' => $planType, 'plan_name' => $planName]
        ]);
    }

    /**
     * Create subscription expiring notification
     */
    public function createSubscriptionExpiringNotification(int $userId, int $daysRemaining): Notification
    {
        return $this->createSubscriptionNotification($userId, 'اشتراک در حال انقضا', "اشتراک شما در {$daysRemaining} روز منقضی می‌شود. برای تمدید اقدام کنید.", [
            'is_important' => true,
            'action_type' => 'button',
            'action_text' => 'تمدید اشتراک',
            'action_url' => '/subscription/renew',
            'data' => ['days_remaining' => $daysRemaining]
        ]);
    }

    /**
     * Create subscription expired notification
     */
    public function createSubscriptionExpiredNotification(int $userId): Notification
    {
        return $this->createSubscriptionNotification($userId, 'اشتراک منقضی شد', 'اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک خود را تمدید کنید.', [
            'is_important' => true,
            'priority' => 'urgent',
            'action_type' => 'button',
            'action_text' => 'تمدید اشتراک',
            'action_url' => '/subscription/renew'
        ]);
    }

    /**
     * Create payment success notification
     */
    public function createPaymentSuccessNotification(int $userId, float $amount): Notification
    {
        return $this->createPaymentNotification($userId, 'پرداخت موفق', "پرداخت شما با موفقیت انجام شد. مبلغ: " . number_format($amount) . " ریال", [
            'is_important' => true,
            'action_type' => 'button',
            'action_text' => 'مشاهده تراکنش',
            'action_url' => '/payments/history',
            'data' => ['amount' => $amount]
        ]);
    }

    /**
     * Create payment failed notification
     */
    public function createPaymentFailedNotification(int $userId, string $reason = null): Notification
    {
        $message = 'پرداخت شما ناموفق بود. لطفاً مجدداً تلاش کنید.';
        if ($reason) {
            $message .= " دلیل: {$reason}";
        }

        return $this->createPaymentNotification($userId, 'پرداخت ناموفق', $message, [
            'is_important' => true,
            'priority' => 'high',
            'action_type' => 'button',
            'action_text' => 'تلاش مجدد',
            'action_url' => '/subscription/renew',
            'data' => ['reason' => $reason]
        ]);
    }

    /**
     * Create new episode notification
     */
    public function createNewEpisodeNotification(int $userId, string $storyTitle, string $episodeTitle): Notification
    {
        return $this->createContentNotification($userId, 'قسمت جدید منتشر شد', "قسمت جدید \"{$episodeTitle}\" از داستان \"{$storyTitle}\" منتشر شد.", [
            'is_important' => true,
            'action_type' => 'button',
            'action_text' => 'شنیدن قسمت',
            'action_url' => '/episodes/latest',
            'data' => ['story_title' => $storyTitle, 'episode_title' => $episodeTitle]
        ]);
    }

    /**
     * Create new story notification
     */
    public function createNewStoryNotification(int $userId, string $storyTitle): Notification
    {
        return $this->createContentNotification($userId, 'داستان جدید منتشر شد', "داستان جدید \"{$storyTitle}\" منتشر شد. از شنیدن آن لذت ببرید.", [
            'is_important' => true,
            'action_type' => 'button',
            'action_text' => 'شنیدن داستان',
            'action_url' => '/stories/latest',
            'data' => ['story_title' => $storyTitle]
        ]);
    }

    /**
     * Create security notification
     */
    public function createSecurityNotification(int $userId, string $title, string $message): Notification
    {
        return $this->createNotification($userId, 'warning', $title, $message, [
            'category' => 'security',
            'priority' => 'urgent',
            'is_important' => true,
            'action_type' => 'button',
            'action_text' => 'بررسی امنیت',
            'action_url' => '/security'
        ]);
    }

    /**
     * Create promotion notification
     */
    public function createPromotionNotification(int $userId, string $title, string $message, array $options = []): Notification
    {
        return $this->createNotification($userId, 'info', $title, $message, array_merge($options, [
            'category' => 'promotion',
            'priority' => 'normal',
            'expires_at' => $options['expires_at'] ?? now()->addDays(7)
        ]));
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(int $userId, int $perPage = 20, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Notification::where('user_id', $userId)
                           ->notExpired()
                           ->orderBy('priority', 'desc')
                           ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }

        if (isset($filters['is_important'])) {
            $query->where('is_important', $filters['is_important']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::getUnreadCount($userId);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $notification = Notification::where('id', $notificationId)
                                      ->where('user_id', $userId)
                                      ->first();

            if (!$notification) {
                return false;
            }

            return $notification->markAsRead();

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId): int
    {
        try {
            $count = Notification::markAllAsRead($userId);

            Log::info('All notifications marked as read', [
                'user_id' => $userId,
                'count' => $count
            ]);

            return $count;

        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Delete notification
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        try {
            $notification = Notification::where('id', $notificationId)
                                      ->where('user_id', $userId)
                                      ->first();

            if (!$notification) {
                return false;
            }

            $notification->delete();

            Log::info('Notification deleted', [
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clean up expired notifications
     */
    public function cleanupExpired(): int
    {
        try {
            $count = Notification::cleanupExpired();

            Log::info('Expired notifications cleaned up', [
                'count' => $count
            ]);

            return $count;

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired notifications', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(): array
    {
        try {
            return Notification::getStatistics();
        } catch (\Exception $e) {
            Log::error('Failed to get notification statistics', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendToMultipleUsers(array $userIds, string $type, string $title, string $message, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($userIds as $userId) {
            try {
                $notification = $this->createNotification($userId, $type, $title, $message, $options);
                $results[] = [
                    'user_id' => $userId,
                    'success' => true,
                    'notification_id' => $notification->id
                ];
                $successCount++;
            } catch (\Exception $e) {
                $results[] = [
                    'user_id' => $userId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        Log::info('Bulk notification sent', [
            'total_users' => count($userIds),
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ]);

        return [
            'total_sent' => count($userIds),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * Send notification to all users
     */
    public function sendToAllUsers(string $type, string $title, string $message, array $options = []): array
    {
        $userIds = User::pluck('id')->toArray();
        return $this->sendToMultipleUsers($userIds, $type, $title, $message, $options);
    }

    /**
     * Send notification to premium users
     */
    public function sendToPremiumUsers(string $type, string $title, string $message, array $options = []): array
    {
        $userIds = User::whereHas('subscriptions', function($query) {
            $query->where('status', 'active')
                  ->where('end_date', '>', now());
        })->pluck('id')->toArray();

        return $this->sendToMultipleUsers($userIds, $type, $title, $message, $options);
    }

    /**
     * Get notification types
     */
    public function getTypes(): array
    {
        return self::TYPES;
    }

    /**
     * Get notification priorities
     */
    public function getPriorities(): array
    {
        return self::PRIORITIES;
    }

    /**
     * Get notification categories
     */
    public function getCategories(): array
    {
        return self::CATEGORIES;
    }
}
