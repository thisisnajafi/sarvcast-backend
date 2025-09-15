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
            'template' => 'به سروکست خوش آمدید! از شنیدن داستان‌های زیبا لذت ببرید.',
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
];