<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for push notifications, email, and SMS
    |
    */

    'firebase' => [
        // Legacy API (deprecated - use only as fallback)
        'server_key' => env('FIREBASE_SERVER_KEY'),

        // FCM v1 API Configuration (Recommended)
        'project_id' => env('FIREBASE_PROJECT_ID', 'sarvcast-20d5c'),
        'service_account_path' => (function() {
            $path = env('FIREBASE_SERVICE_ACCOUNT_PATH');
            if (!$path) {
                return storage_path('app/firebase-service-account.json');
            }
            // If absolute path (Windows or Unix), use as-is
            if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\\\\/', $path)) {
                return $path;
            }
            // If path starts with 'storage/', remove it
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8);
            }
            // If path starts with 'app/', use storage_path directly
            if (str_starts_with($path, 'app/')) {
                return storage_path($path);
            }
            // Otherwise, assume it's a filename in storage/app
            return storage_path('app/' . $path);
        })(),
        'use_v1_api' => env('FIREBASE_USE_V1_API', true), // Set to false to use legacy API

        // API URLs
        'api_url_v1' => 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send',
        'api_url_legacy' => 'https://fcm.googleapis.com/fcm/send',
    ],

    'email' => [
        'from' => env('MAIL_FROM_ADDRESS', 'noreply@sarvcast.com'),
        'from_name' => env('MAIL_FROM_NAME', 'سروکست'),
        'templates' => [
            'notification' => 'emails.notification',
            'subscription' => 'emails.subscription',
            'payment' => 'emails.payment',
            'welcome' => 'emails.welcome',
        ],
    ],

    'sms' => [
        'api_key' => env('SMS_API_KEY'),
        'api_url' => 'https://api.sms.ir/v1/send/verify',
        'sender' => env('SMS_SENDER', 'سروکست'),
    ],

    'push' => [
        'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', true),
        'sound' => 'default',
        'badge' => 1,
        'priority' => 'high',
    ],

    'in_app' => [
        'enabled' => env('IN_APP_NOTIFICATIONS_ENABLED', true),
        'retention_days' => env('NOTIFICATION_RETENTION_DAYS', 30),
    ],

    'channels' => [
        'default' => ['in_app', 'push'],
        'subscription' => ['in_app', 'push'],
        'payment' => ['in_app', 'push'],
        'content' => ['in_app', 'push'],
        'marketing' => ['in_app', 'push', 'sms'],
    ],

    'types' => [
        'info' => [
            'icon' => 'info',
            'color' => 'blue',
        ],
        'success' => [
            'icon' => 'check',
            'color' => 'green',
        ],
        'warning' => [
            'icon' => 'warning',
            'color' => 'yellow',
        ],
        'error' => [
            'icon' => 'error',
            'color' => 'red',
        ],
    ],

    'templates' => [
        'subscription_created' => [
            'title' => 'اشتراک جدید',
            'message' => 'اشتراک شما با موفقیت ایجاد شد',
            'channels' => ['in_app', 'push'],
        ],
        'subscription_activated' => [
            'title' => 'اشتراک فعال شد',
            'message' => 'اشتراک شما فعال شد و می‌توانید از تمام امکانات استفاده کنید',
            'channels' => ['in_app', 'push'],
        ],
        'subscription_expired' => [
            'title' => 'اشتراک منقضی شد',
            'message' => 'اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک جدید خریداری کنید',
            'channels' => ['in_app', 'push'],
        ],
        'payment_success' => [
            'title' => 'پرداخت موفق',
            'message' => 'پرداخت شما با موفقیت انجام شد',
            'channels' => ['in_app', 'push'],
        ],
        'payment_failed' => [
            'title' => 'پرداخت ناموفق',
            'message' => 'پرداخت شما انجام نشد. لطفاً مجدداً تلاش کنید',
            'channels' => ['in_app', 'push'],
        ],
        'new_episode' => [
            'title' => 'اپیزود جدید',
            'message' => 'اپیزود جدید از داستان مورد علاقه شما منتشر شد',
            'channels' => ['in_app', 'push'],
        ],
        'new_story' => [
            'title' => 'داستان جدید',
            'message' => 'داستان جدید در دسته‌بندی مورد علاقه شما منتشر شد',
            'channels' => ['in_app', 'push'],
        ],
    ],

    'scheduling' => [
        'enabled' => env('NOTIFICATION_SCHEDULING_ENABLED', true),
        'timezone' => env('NOTIFICATION_TIMEZONE', 'Asia/Tehran'),
        'quiet_hours' => [
            'start' => '22:00',
            'end' => '08:00',
        ],
    ],

    'analytics' => [
        'enabled' => env('NOTIFICATION_ANALYTICS_ENABLED', true),
        'track_opens' => true,
        'track_clicks' => true,
    ],

    'log_notifications' => env('LOG_NOTIFICATIONS', true),

    'rate_limiting' => [
        'enabled' => env('NOTIFICATION_RATE_LIMITING_ENABLED', true),
        'max_per_hour' => env('NOTIFICATION_MAX_PER_HOUR', 10),
        'max_per_day' => env('NOTIFICATION_MAX_PER_DAY', 50),
    ],
];
