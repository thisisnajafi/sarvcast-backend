<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    protected $firebaseProjectId;
    protected $firebaseAuthService;
    protected $smsApiKey;
    protected $emailFrom;
    protected $useV1Api;

    public function __construct(FirebaseAuthService $firebaseAuthService = null)
    {
        $this->firebaseProjectId = config('notification.firebase.project_id');
        $this->firebaseAuthService = $firebaseAuthService ?? app(FirebaseAuthService::class);
        $this->smsApiKey = config('notification.sms.api_key');
        $this->emailFrom = config('notification.email.from');
        $this->useV1Api = config('notification.firebase.use_v1_api', true);
    }

    /**
     * Send push notification using FCM v1 API
     */
    public function sendPushNotification(User $user, string $title, string $body, array $data = []): bool
    {
        if (! config('notification.push.enabled', true)) {
            Log::info('Push notifications disabled via config', ['user_id' => $user->id]);
            return false;
        }

        if (! $this->userAllowsPushNotifications($user)) {
            Log::info('Push notifications disabled by user preference', ['user_id' => $user->id]);
            return false;
        }

        try {
            // Get user's FCM tokens
            $fcmTokens = $this->getUserFcmTokens($user);

            if (empty($fcmTokens)) {
                return false;
            }

            // Use FCM v1 API if enabled, otherwise fallback to legacy
            if ($this->useV1Api) {
                return $this->sendPushNotificationV1($fcmTokens, $title, $body, $data, $user);
            } else {
                return $this->sendPushNotificationLegacy($fcmTokens, $title, $body, $data, $user);
            }
        } catch (\Exception $e) {
            Log::error('Push notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification using FCM HTTP v1 API
     */
    protected function sendPushNotificationV1(array $fcmTokens, string $title, string $body, array $data, User $user): bool
    {
        try {
            // Get OAuth2 access token
            $accessToken = $this->firebaseAuthService->getAccessToken();

            if (!$accessToken) {
                Log::error('Failed to get Firebase access token');
                return false;
            }

            $successCount = 0;
            $projectId = $this->firebaseProjectId;
            $apiUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // Send to each token individually (FCM v1 requires individual requests)
            foreach ($fcmTokens as $token) {
                $message = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $this->prepareDataPayload($data),
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'sound' => 'default',
                                'channel_id' => 'manji_notifications',
                                'icon' => 'ic_notification',
                                'color' => '#00BCD4',
                            ],
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10',
                            ],
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                    'badge' => 1,
                                ],
                            ],
                        ],
                    ],
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($apiUrl, $message);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    $this->handleFcmSendFailure($user, $token, $response);
                }
            }

            // Log notification
            $this->logNotification($user, 'push', $title, $body, $data, [
                'success_count' => $successCount,
                'total_tokens' => count($fcmTokens),
            ]);

            return $successCount > 0;
        } catch (\Exception $e) {
            Log::error('FCM v1 push notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification using Legacy FCM API (fallback)
     */
    protected function sendPushNotificationLegacy(array $fcmTokens, string $title, string $body, array $data, User $user): bool
    {
        $serverKey = config('notification.firebase.server_key');

        if (!$serverKey) {
            Log::error('Firebase server key not configured for legacy API');
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
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json'
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        if ($response->successful()) {
            $result = $response->json();
            $this->logNotification($user, 'push', $title, $body, $data, $result);
            return $result['success'] > 0;
        }

        return false;
    }

    /**
     * Prepare data payload for FCM (convert all values to strings)
     */
    protected function prepareDataPayload(array $data): array
    {
        $prepared = [];
        foreach ($data as $key => $value) {
            // FCM data payload values must be strings
            $prepared[$key] = is_array($value) ? json_encode($value) : (string) $value;
        }
        return $prepared;
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
                        ->from($this->emailFrom, 'مانجی');
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
            ],
            'payment_cancelled' => [
                'title' => 'پرداخت لغو شد',
                'message' => 'پرداخت توسط شما لغو شد. در صورت تمایل می‌توانید مجدداً تلاش کنید',
                'type' => 'info'
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
     * Send marketing notification (SMS + In-App + Push)
     */
    public function sendMarketingNotification(User $user, string $title, string $message, array $data = [], bool $sendSms = true): bool
    {
        try {
            // Send in-app notification
            $this->sendInAppNotification($user, $title, $message, 'promotion', $data);

            // Send push notification
            $this->sendPushNotification($user, $title, $message, $data);

            // Send SMS for marketing (if enabled and user has phone)
            if ($sendSms && $user->phone_number) {
                $this->sendSmsNotification($user, $message);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Marketing notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send marketing notification to multiple users
     */
    public function sendBulkMarketingNotification(array $userIds, string $title, string $message, array $data = [], bool $sendSms = true): array
    {
        $results = [];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            $results[$userId] = $this->sendMarketingNotification($user, $title, $message, $data, $sendSms);
        }

        return $results;
    }

    /**
     * Whether the user has push notifications enabled in preferences.
     */
    public function userAllowsPushNotifications(User $user): bool
    {
        $preferences = $user->preferences ?? [];

        if (! is_array($preferences)) {
            return true;
        }

        return ($preferences['push_notifications_enabled'] ?? true) === true;
    }

    /**
     * Log and optionally remove invalid FCM tokens.
     */
    protected function handleFcmSendFailure(User $user, string $token, $response): void
    {
        $body = $response->body();
        $projectId = $this->firebaseProjectId;
        Log::warning('FCM v1 notification failed for token', [
            'user_id' => $user->id,
            'firebase_project_id' => $projectId,
            'token_preview' => substr($token, 0, 20) . '...',
            'status' => $response->status(),
            'response' => $body,
        ]);

        if (
            $response->status() === 403
            && str_contains($body, 'PERMISSION_DENIED')
            && str_contains($body, 'sarvcast-20d5c')
        ) {
            Log::error('FCM project mismatch: server or token still references old Firebase project sarvcast-20d5c. Set FIREBASE_PROJECT_ID=manjiapp-3028e on production, run config:clear, and reinstall the app so devices register new tokens.', [
                'configured_project_id' => $projectId,
                'user_id' => $user->id,
            ]);
        }

        if (
            str_contains($body, 'UNREGISTERED')
            || str_contains($body, 'INVALID_ARGUMENT')
            || str_contains($body, 'NOT_FOUND')
            || str_contains($body, 'SenderId mismatch')
        ) {
            $this->removeInvalidFcmToken($user->id, $token);
        }
    }

    /**
     * Remove a stale device token after FCM rejects it.
     */
    protected function removeInvalidFcmToken(int $userId, string $token): void
    {
        try {
            $deleted = \DB::table('user_devices')
                ->where('user_id', $userId)
                ->where('fcm_token', $token)
                ->delete();

            if ($deleted > 0) {
                Log::info('Removed invalid FCM token', [
                    'user_id' => $userId,
                    'token_preview' => substr($token, 0, 20) . '...',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove invalid FCM token: ' . $e->getMessage());
        }
    }

    /**
     * Get user's FCM tokens
     */
    private function getUserFcmTokens(User $user): array
    {
        try {
            $tokens = \DB::table('user_devices')
                ->where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->toArray();

            return array_filter($tokens); // Remove any empty values
        } catch (\Exception $e) {
            Log::error('Failed to get user FCM tokens: ' . $e->getMessage());
            return [];
        }
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

    /**
     * Send voice actor assignment notification
     */
    public function sendVoiceActorAssignmentNotification(User $user, string $assignmentType, array $data): bool
    {
        $notifications = [
            'story_narrator' => [
                'title' => 'اختصاص راوی داستان',
                'message' => "شما به عنوان راوی داستان «{$data['story_title']}» انتخاب شده‌اید.",
                'type' => 'info'
            ],
            'character' => [
                'title' => 'اختصاص صداپیشه شخصیت',
                'message' => "شما به عنوان صداپیشه شخصیت «{$data['character_name']}» در داستان «{$data['story_title']}» انتخاب شده‌اید.",
                'type' => 'info'
            ],
            'episode_voice_actor' => [
                'title' => 'اختصاص صداپیشه قسمت',
                'message' => "شما به عنوان صداپیشه در قسمت «{$data['episode_title']}» از داستان «{$data['story_title']}» انتخاب شده‌اید.",
                'type' => 'info'
            ],
            'episode_narrator' => [
                'title' => 'اختصاص راوی قسمت',
                'message' => "شما به عنوان راوی قسمت «{$data['episode_title']}» از داستان «{$data['story_title']}» انتخاب شده‌اید.",
                'type' => 'info'
            ]
        ];

        if (!isset($notifications[$assignmentType])) {
            return false;
        }

        $notification = $notifications[$assignmentType];

        // Prepare deep link data
        $pushData = [
            'type' => 'voice_actor_assignment',
            'assignment_type' => $assignmentType,
            'action' => 'assigned'
        ];

        if (isset($data['story_id'])) {
            $pushData['story_id'] = $data['story_id'];
        }
        if (isset($data['episode_id'])) {
            $pushData['episode_id'] = $data['episode_id'];
        }
        if (isset($data['character_id'])) {
            $pushData['character_id'] = $data['character_id'];
        }

        $pushData = array_merge($pushData, $data);

        // Send in-app notification
        $this->sendInAppNotification($user, $notification['title'], $notification['message'], $notification['type'], $pushData);

        // Send push notification
        $this->sendPushNotification($user, $notification['title'], $notification['message'], $pushData);

        // Send email notification
        $this->sendEmailNotification($user, $notification['title'], 'emails.voice_actor_assignment', [
            'user' => $user,
            'assignment_type' => $assignmentType,
            'data' => $data
        ]);

        return true;
    }

    /**
     * Send voice actor removal notification
     */
    public function sendVoiceActorRemovalNotification(User $user, string $assignmentType, array $data): bool
    {
        $notifications = [
            'story_narrator' => [
                'title' => 'حذف از نقش راوی',
                'message' => "شما از نقش راوی داستان «{$data['story_title']}» حذف شده‌اید.",
                'type' => 'warning'
            ],
            'character' => [
                'title' => 'حذف از نقش صداپیشه',
                'message' => "شما از نقش صداپیشه شخصیت «{$data['character_name']}» در داستان «{$data['story_title']}» حذف شده‌اید.",
                'type' => 'warning'
            ],
            'episode_voice_actor' => [
                'title' => 'حذف از نقش صداپیشه',
                'message' => "شما از نقش صداپیشه در قسمت «{$data['episode_title']}» از داستان «{$data['story_title']}» حذف شده‌اید.",
                'type' => 'warning'
            ]
        ];

        if (!isset($notifications[$assignmentType])) {
            return false;
        }

        $notification = $notifications[$assignmentType];

        // Prepare deep link data
        $pushData = [
            'type' => 'voice_actor_assignment',
            'assignment_type' => $assignmentType,
            'action' => 'removed'
        ];

        if (isset($data['story_id'])) {
            $pushData['story_id'] = $data['story_id'];
        }
        if (isset($data['episode_id'])) {
            $pushData['episode_id'] = $data['episode_id'];
        }

        $pushData = array_merge($pushData, $data);

        // Send in-app notification
        $this->sendInAppNotification($user, $notification['title'], $notification['message'], $notification['type'], $pushData);

        // Send push notification
        $this->sendPushNotification($user, $notification['title'], $notification['message'], $pushData);

        return true;
    }

    /**
     * Send workflow status change notification
     */
    public function sendWorkflowStatusChangeNotification(User $user, \App\Models\Story $story, string $oldStatus, string $newStatus): bool
    {
        $statusLabels = [
            'written' => 'نوشته شده',
            'characters_made' => 'شخصیت‌ها ساخته شده',
            'recorded' => 'ضبط شده',
            'timeline_created' => 'تایم‌لاین ایجاد شده',
            'published' => 'منتشر شده'
        ];

        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        $title = 'تغییر وضعیت داستان';
        $message = "وضعیت داستان «{$story->title}» به «{$newLabel}» تغییر کرد.";

        // Special messages for specific transitions
        if ($newStatus === 'characters_made') {
            $message = "شخصیت‌ها برای داستان «{$story->title}» ساخته شده‌اند. آماده ضبط هستید.";
        } elseif ($newStatus === 'recorded') {
            $message = "ضبط داستان «{$story->title}» شروع شده است.";
        } elseif ($newStatus === 'timeline_created') {
            $message = "تایم‌لاین برای داستان «{$story->title}» ایجاد شده است.";
        } elseif ($newStatus === 'published') {
            $message = "داستان «{$story->title}» منتشر شد! 🎉";
        }

        $pushData = [
            'type' => 'workflow_status_change',
            'story_id' => $story->id,
            'story_title' => $story->title,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'status_label' => $newLabel
        ];

        // Send in-app notification
        $this->sendInAppNotification($user, $title, $message, 'info', $pushData);

        // Send push notification
        $this->sendPushNotification($user, $title, $message, $pushData);

        // Send email for important status changes
        if (in_array($newStatus, ['characters_made', 'recorded', 'published'])) {
            $this->sendEmailNotification($user, $title, 'emails.workflow_status_change', [
                'user' => $user,
                'story' => $story,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
        }

        return true;
    }

    /**
     * Send content published notification for voice actors
     */
    public function sendContentPublishedNotification(User $user, string $contentType, array $data): bool
    {
        $notifications = [
            'story' => [
                'narrator' => [
                    'title' => 'داستان منتشر شد',
                    'message' => "داستان «{$data['story_title']}» که شما راوی آن هستید منتشر شد! 🎉",
                    'type' => 'success'
                ],
                'character_voice_actor' => [
                    'title' => 'داستان منتشر شد',
                    'message' => "داستان «{$data['story_title']}» که شما صداپیشه شخصیت «{$data['character_name']}» در آن هستید منتشر شد! 🎉",
                    'type' => 'success'
                ]
            ],
            'episode' => [
                'narrator' => [
                    'title' => 'قسمت منتشر شد',
                    'message' => "قسمت «{$data['episode_title']}» از داستان «{$data['story_title']}» که شما راوی آن هستید منتشر شد! 🎉",
                    'type' => 'success'
                ],
                'voice_actor' => [
                    'title' => 'قسمت منتشر شد',
                    'message' => "قسمت «{$data['episode_title']}» از داستان «{$data['story_title']}» که شما در آن صداپیشه هستید منتشر شد! 🎉",
                    'type' => 'success'
                ]
            ]
        ];

        $role = $data['role'] ?? 'voice_actor';
        if (!isset($notifications[$contentType][$role])) {
            return false;
        }

        $notification = $notifications[$contentType][$role];

        $pushData = [
            'type' => 'content_published',
            'content_type' => $contentType,
            'role' => $role
        ];

        if (isset($data['story_id'])) {
            $pushData['story_id'] = $data['story_id'];
        }
        if (isset($data['episode_id'])) {
            $pushData['episode_id'] = $data['episode_id'];
        }
        if (isset($data['character_id'])) {
            $pushData['character_id'] = $data['character_id'];
        }

        $pushData = array_merge($pushData, $data);

        // Send in-app notification
        $this->sendInAppNotification($user, $notification['title'], $notification['message'], $notification['type'], $pushData);

        // Send push notification
        $this->sendPushNotification($user, $notification['title'], $notification['message'], $pushData);

        // Send email for published content
        $this->sendEmailNotification($user, $notification['title'], 'emails.content_published', [
            'user' => $user,
            'content_type' => $contentType,
            'data' => $data
        ]);

        return true;
    }

    /**
     * Send script ready notification
     */
    public function sendScriptReadyNotification(User $user, string $contentType, array $data): bool
    {
        $notifications = [
            'story' => [
                'title' => 'فیلمنامه آماده است',
                'message' => "فیلمنامه داستان «{$data['story_title']}» آماده است. می‌توانید آن را مطالعه کنید.",
                'type' => 'info'
            ],
            'episode' => [
                'title' => 'فیلمنامه آماده است',
                'message' => "فیلمنامه قسمت «{$data['episode_title']}» از داستان «{$data['story_title']}» آماده است.",
                'type' => 'info'
            ]
        ];

        if (!isset($notifications[$contentType])) {
            return false;
        }

        $notification = $notifications[$contentType];

        $pushData = [
            'type' => 'script_ready',
            'content_type' => $contentType
        ];

        if (isset($data['story_id'])) {
            $pushData['story_id'] = $data['story_id'];
        }
        if (isset($data['episode_id'])) {
            $pushData['episode_id'] = $data['episode_id'];
        }

        $pushData = array_merge($pushData, $data);

        // Send in-app notification
        $this->sendInAppNotification($user, $notification['title'], $notification['message'], $notification['type'], $pushData);

        // Send push notification
        $this->sendPushNotification($user, $notification['title'], $notification['message'], $pushData);

        // Send email notification
        $this->sendEmailNotification($user, $notification['title'], 'emails.script_ready', [
            'user' => $user,
            'content_type' => $contentType,
            'data' => $data
        ]);

        return true;
    }
}
