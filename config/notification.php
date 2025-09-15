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
        'server_key' => env('FIREBASE_SERVER_KEY'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'api_url' => 'https://fcm.googleapis.com/fcm/send',
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
        'subscription' => ['in_app', 'push', 'email'],
        'payment' => ['in_app', 'push', 'email'],
        'content' => ['in_app', 'push'],
        'marketing' => ['in_app', 'push', 'email', 'sms'],
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
            'channels' => ['in_app', 'push', 'email'],
        ],
        'subscription_expired' => [
            'title' => 'اشتراک منقضی شد',
            'message' => 'اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک جدید خریداری کنید',
            'channels' => ['in_app', 'push', 'email'],
        ],
        'payment_success' => [
            'title' => 'پرداخت موفق',
            'message' => 'پرداخت شما با موفقیت انجام شد',
            'channels' => ['in_app', 'push'],
        ],
        'payment_failed' => [
            'title' => 'پرداخت ناموفق',
            'message' => 'پرداخت شما انجام نشد. لطفاً مجدداً تلاش کنید',
            'channels' => ['in_app', 'push', 'email'],
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
