<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS service provider
    |
    */

    'api_key' => env('SMS_API_KEY'),
    'api_url' => env('SMS_API_URL', 'https://api.sms.ir/v1/send/verify'),
    'sender' => env('SMS_SENDER', 'سروکست'),

    'verification' => [
        'code_length' => 6,
        'expires_in' => 300, // 5 minutes
        'max_attempts' => 5,
        'rate_limit_hours' => 1,
    ],

    'templates' => [
        'verification' => 'کد تایید شما: {code}\nسروکست - پلتفرم داستان‌های صوتی کودکان',
        'subscription_created' => 'اشتراک شما با موفقیت ایجاد شد',
        'subscription_activated' => 'اشتراک شما فعال شد و می‌توانید از تمام امکانات استفاده کنید',
        'subscription_expired' => 'اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک جدید خریداری کنید',
        'subscription_cancelled' => 'اشتراک شما لغو شد',
        'payment_success' => 'پرداخت شما با موفقیت انجام شد',
        'payment_failed' => 'پرداخت شما انجام نشد. لطفاً مجدداً تلاش کنید',
        'new_episode' => 'اپیزود جدید از داستان مورد علاقه شما منتشر شد',
        'new_story' => 'داستان جدید در دسته‌بندی مورد علاقه شما منتشر شد',
    ],

    'rate_limiting' => [
        'enabled' => env('SMS_RATE_LIMITING_ENABLED', true),
        'max_per_hour' => env('SMS_MAX_PER_HOUR', 5),
        'max_per_day' => env('SMS_MAX_PER_DAY', 20),
    ],

    'phone_validation' => [
        'pattern' => '/^(\+98|0)?9[0-9]{9}$/',
        'country_code' => '+98',
        'default_prefix' => '0',
    ],

    'logging' => [
        'enabled' => env('SMS_LOGGING_ENABLED', true),
        'log_success' => env('SMS_LOG_SUCCESS', true),
        'log_failure' => env('SMS_LOG_FAILURE', true),
    ],

    'timeout' => env('SMS_TIMEOUT', 30),
    'retry_attempts' => env('SMS_RETRY_ATTEMPTS', 3),
];
