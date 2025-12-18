<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    protected $firebaseServerKey;
    protected $smsApiKey;
    protected $emailFrom;

    public function __construct()
    {
        $this->firebaseServerKey = config('notification.firebase.server_key');
        $this->smsApiKey = config('notification.sms.api_key');
        $this->emailFrom = config('notification.email.from');
    }

    /**
     * Send push notification
     */
    public function sendPushNotification(User $user, string $title, string $body, array $data = []): bool
    {
        try {
            // Get user's FCM tokens (this would be stored in user profile)
            $fcmTokens = $this->getUserFcmTokens($user);
            
            if (empty($fcmTokens)) {
                return false;
            }

            $payload = [
                'registration_ids' => $fcmTokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1
                ],
                'data' => $data,
                'priority' => 'high'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->firebaseServerKey,
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                // Log notification
                $this->logNotification($user, 'push', $title, $body, $data, $result);
                
                return $result['success'] > 0;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Push notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email notification
     */
    public function sendEmailNotification(User $user, string $subject, string $template, array $data = []): bool
    {
        try {
            Mail::send($template, $data, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->first_name . ' ' . $user->last_name)
                        ->subject($subject)
                        ->from($this->emailFrom, 'سروکست');
            });

            // Log notification
            $this->logNotification($user, 'email', $subject, '', $data);

            return true;
        } catch (\Exception $e) {
            Log::error('Email notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS notification
     */
    public function sendSmsNotification(User $user, string $message): bool
    {
        try {
            if (!$user->phone_number) {
                return false;
            }

            $payload = [
                'api_key' => $this->smsApiKey,
                'mobile' => $user->phone_number,
                'message' => $message
            ];

            $response = Http::post('https://api.sms.ir/v1/send/verify', $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                // Log notification
                $this->logNotification($user, 'sms', 'SMS', $message, $result);
                
                return $result['status'] === 'success';
            }

            return false;
        } catch (\Exception $e) {
            Log::error('SMS notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send in-app notification
     */
    public function sendInAppNotification(User $user, string $title, string $message, string $type = 'info', array $data = []): bool
    {
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'read_at' => null
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('In-app notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendBulkNotification(array $userIds, string $title, string $message, string $type = 'info', array $channels = ['in_app']): array
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            $userResults = [];
            
            foreach ($channels as $channel) {
                switch ($channel) {
                    case 'push':
                        $userResults['push'] = $this->sendPushNotification($user, $title, $message);
                        break;
                    case 'email':
                        $userResults['email'] = $this->sendEmailNotification($user, $title, 'emails.notification', ['title' => $title, 'message' => $message]);
                        break;
                    case 'sms':
                        $userResults['sms'] = $this->sendSmsNotification($user, $message);
                        break;
                    case 'in_app':
                        $userResults['in_app'] = $this->sendInAppNotification($user, $title, $message, $type);
                        break;
                }
            }
            
            $results[$userId] = $userResults;
        }

        return $results;
    }

    /**
     * Send subscription-related notifications
     */
    public function sendSubscriptionNotification(User $user, string $event, array $data = []): bool
    {
        $notifications = [
            'subscription_created' => [
                'title' => 'اشتراک جدید',
                'message' => 'اشتراک شما با موفقیت ایجاد شد',
                'type' => 'success'
            ],
            'subscription_activated' => [
                'title' => 'اشتراک فعال شد',
                'message' => 'اشتراک شما فعال شد و می‌توانید از تمام امکانات استفاده کنید',
                'type' => 'success'
            ],
            'subscription_expired' => [
                'title' => 'اشتراک منقضی شد',
                'message' => 'اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک جدید خریداری کنید',
                'type' => 'warning'
            ],
            'subscription_cancelled' => [
                'title' => 'اشتراک لغو شد',
                'message' => 'اشتراک شما لغو شد',
                'type' => 'info'
            ],
            'payment_success' => [
                'title' => 'پرداخت موفق',
                'message' => 'پرداخت شما با موفقیت انجام شد',
                'type' => 'success'
            ],
            'payment_failed' => [
                'title' => 'پرداخت ناموفق',
                'message' => 'پرداخت شما انجام نشد. لطفاً مجدداً تلاش کنید',
                'type' => 'error'
            ]
        ];

        if (!isset($notifications[$event])) {
            return false;
        }

        $notification = $notifications[$event];
        
        // Send in-app notification
        $this->sendInAppNotification($user, $notification['title'], $notification['message'], $notification['type'], $data);
        
        // Send push notification
        $this->sendPushNotification($user, $notification['title'], $notification['message'], $data);
        
        // Send email for important events
        if (in_array($event, ['subscription_expired', 'payment_failed'])) {
            $this->sendEmailNotification($user, $notification['title'], 'emails.notification', [
                'title' => $notification['title'],
                'message' => $notification['message'],
                'user' => $user
            ]);
        }

        return true;
    }

    /**
     * Send content-related notifications
     */
    public function sendContentNotification(User $user, string $event, array $data = []): bool
    {
        $notifications = [
            'new_episode' => [
                'title' => 'اپیزود جدید',
                'message' => 'اپیزود جدید از داستان مورد علاقه شما منتشر شد',
                'type' => 'info'
            ],
            'new_story' => [
                'title' => 'داستان جدید',
                'message' => 'داستان جدید در دسته‌بندی مورد علاقه شما منتشر شد',
                'type' => 'info'
            ],
            'story_completed' => [
                'title' => 'داستان تکمیل شد',
                'message' => 'شما داستان را به پایان رساندید',
                'type' => 'success'
            ]
        ];

        if (!isset($notifications[$event])) {
            return false;
        }

        $notification = $notifications[$event];
        
        // Send in-app notification
        $this->sendInAppNotification($user, $notification['title'], $notification['message'], $notification['type'], $data);
        
        // Send push notification
        $this->sendPushNotification($user, $notification['title'], $notification['message'], $data);

        return true;
    }

    /**
     * Get user's FCM tokens
     */
    private function getUserFcmTokens(User $user): array
    {
        // This would typically be stored in a user_devices table
        // For now, return empty array
        return [];
    }

    /**
     * Log notification
     */
    private function logNotification(User $user, string $channel, string $title, string $message, array $data = [], array $response = []): void
    {
        if (config('notification.log_notifications', true)) {
            Log::info('Notification sent', [
                'user_id' => $user->id,
                'channel' => $channel,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'response' => $response
            ]);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        try {
            $notification->update(['read_at' => now()]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): bool
    {
        try {
            $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->count();
    }

    /**
     * Clean old notifications
     */
    public function cleanOldNotifications(int $days = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))->delete();
    }
}
