<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your SMS providers here. You can enable multiple providers
    | and the system will use the first available one.
    |
    */

    'default_provider' => env('SMS_DEFAULT_PROVIDER', 'kavenegar'),

    'providers' => [
        'kavenegar' => [
            'enabled' => env('SMS_KAVENEGAR_ENABLED', false),
            'api_key' => env('SMS_KAVENEGAR_API_KEY'),
            'sender' => env('SMS_KAVENEGAR_SENDER'),
            'base_url' => 'https://api.kavenegar.com/v1',
        ],

        'melipayamak' => [
            'enabled' => env('SMS_MELIPAYAMAK_ENABLED', false),
            'username' => env('SMS_MELIPAYAMAK_USERNAME'),
            'password' => env('SMS_MELIPAYAMAK_PASSWORD'),
            'sender' => env('SMS_MELIPAYAMAK_SENDER'),
            'base_url' => 'https://rest.payamak-resan.com/api',
        ],

        'smsir' => [
            'enabled' => env('SMS_SMSIR_ENABLED', false),
            'api_key' => env('SMS_SMSIR_API_KEY'),
            'sender' => env('SMS_SMSIR_SENDER'),
            'base_url' => 'https://api.sms.ir/v1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Settings
    |--------------------------------------------------------------------------
    |
    | General SMS settings and limits
    |
    */

    'rate_limit' => [
        'max_per_hour' => env('SMS_RATE_LIMIT_PER_HOUR', 10),
        'max_per_day' => env('SMS_RATE_LIMIT_PER_DAY', 50),
    ],

    'message' => [
        'max_length' => env('SMS_MAX_LENGTH', 1000),
        'default_encoding' => 'UTF-8',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Templates
    |--------------------------------------------------------------------------
    |
    | Predefined SMS templates for common use cases
    |
    */

    'templates' => [
        'verification' => [
            'name' => 'کد تایید',
            'template' => 'کد تایید شما: {code}',
            'variables' => ['code']
        ],
        'welcome' => [
            'name' => 'خوش‌آمدگویی',
            'template' => 'به مانجی خوش آمدید! از شنیدن داستان‌های زیبا لذت ببرید.',
            'variables' => []
        ],
        'subscription_activated' => [
            'name' => 'فعال‌سازی اشتراک',
            'template' => 'اشتراک شما فعال شد. از دسترسی کامل به محتوا لذت ببرید.',
            'variables' => []
        ],
        'subscription_expiring' => [
            'name' => 'انقضای اشتراک',
            'template' => 'اشتراک شما در {days} روز منقضی می‌شود. برای تمدید اقدام کنید.',
            'variables' => ['days']
        ],
        'subscription_expired' => [
            'name' => 'انقضای اشتراک',
            'template' => 'اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک خود را تمدید کنید.',
            'variables' => []
        ],
        'payment_success' => [
            'name' => 'پرداخت موفق',
            'template' => 'پرداخت شما با موفقیت انجام شد. مبلغ: {amount} ریال',
            'variables' => ['amount']
        ],
        'payment_failed' => [
            'name' => 'پرداخت ناموفق',
            'template' => 'پرداخت شما ناموفق بود. لطفاً مجدداً تلاش کنید.',
            'variables' => []
        ],
        'new_episode' => [
            'name' => 'قسمت جدید',
            'template' => 'قسمت جدید "{episode_title}" از داستان "{story_title}" منتشر شد.',
            'variables' => ['episode_title', 'story_title']
        ],
        'new_story' => [
            'name' => 'داستان جدید',
            'template' => 'داستان جدید "{story_title}" منتشر شد. از شنیدن آن لذت ببرید.',
            'variables' => ['story_title']
        ],
        'password_reset' => [
            'name' => 'بازیابی رمز عبور',
            'template' => 'کد بازیابی رمز عبور شما: {code}',
            'variables' => ['code']
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Logging
    |--------------------------------------------------------------------------
    |
    | SMS logging and monitoring settings
    |
    */

    'logging' => [
        'enabled' => env('SMS_LOGGING_ENABLED', true),
        'log_failed_only' => env('SMS_LOG_FAILED_ONLY', false),
        'retention_days' => env('SMS_LOG_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Analytics
    |--------------------------------------------------------------------------
    |
    | SMS analytics and statistics settings
    |
    */

    'analytics' => [
        'enabled' => env('SMS_ANALYTICS_ENABLED', true),
        'track_providers' => env('SMS_TRACK_PROVIDERS', true),
        'track_templates' => env('SMS_TRACK_TEMPLATES', true),
        'track_delivery' => env('SMS_TRACK_DELIVERY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Campaign Settings
    |--------------------------------------------------------------------------
    */

    'campaign' => [
        'queue' => env('SMS_CAMPAIGN_QUEUE', 'sms'),
        'max_recipients' => (int) env('SMS_CAMPAIGN_MAX_RECIPIENTS', 10000),
        'daily_dispatch_limit' => (int) env('SMS_CAMPAIGN_DAILY_LIMIT', 3),
        'send_delay_ms' => (int) env('SMS_CAMPAIGN_SEND_DELAY_MS', 200),
        'chunk_size' => (int) env('SMS_CAMPAIGN_CHUNK_SIZE', 50),
        'preview_sample_size' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Delivery Polling (Melipayamak GetDeliveries2)
    |--------------------------------------------------------------------------
    */

    'delivery' => [
        'polling_enabled' => env('SMS_DELIVERY_POLLING_ENABLED', true),
        'initial_delay_seconds' => (int) env('SMS_DELIVERY_INITIAL_DELAY_SECONDS', 60),
        'max_age_hours' => (int) env('SMS_DELIVERY_MAX_AGE_HOURS', 48),
        'batch_size' => (int) env('SMS_DELIVERY_BATCH_SIZE', 100),
        'job_tries' => (int) env('SMS_DELIVERY_JOB_TRIES', 5),
        'job_backoff_seconds' => (int) env('SMS_DELIVERY_JOB_BACKOFF_SECONDS', 120),
    ],
];