<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ZarinPal payment gateway
    |
    */

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID', '77751ff3-c1cc-411b-869d-2ac7d7b02f88'),
        'sandbox' => env('ZARINPAL_SANDBOX', false),
        'api_url' => 'https://api.zarinpal.com/pg/v4/payment/',
    ],

    'callback_url' => env('PAYMENT_CALLBACK_URL', env('APP_URL') . '/payment/zarinpal/callback'),

    'success_url' => env('PAYMENT_SUCCESS_URL', env('APP_URL') . '/payment/success'),
    'failure_url' => env('PAYMENT_FAILURE_URL', env('APP_URL') . '/payment/failure'),

    'currencies' => [
        'IRR' => 'ریال ایران',
        'USD' => 'دلار آمریکا',
    ],

    'default_currency' => 'IRR',

    'subscription_plans' => [
        '1month' => [
            'name' => 'اشتراک یک ماهه',
            'price' => 50000, // in IRR
            'duration_days' => 30,
            'features' => [
                'دسترسی به تمام داستان‌ها',
                'اپیزودهای پولی',
                'بدون تبلیغات',
                'دانلود آفلاین'
            ]
        ],
        '3months' => [
            'name' => 'اشتراک سه‌ماهه',
            'price' => 135000, // in IRR
            'duration_days' => 90,
            'features' => [
                'دسترسی به تمام داستان‌ها',
                'اپیزودهای پولی',
                'بدون تبلیغات',
                'دانلود آفلاین',
                '10% تخفیف ویژه'
            ]
        ],
        '6months' => [
            'name' => 'اشتراک شش‌ماهه',
            'price' => 240000, // in IRR
            'duration_days' => 180,
            'features' => [
                'دسترسی به تمام داستان‌ها',
                'اپیزودهای پولی',
                'بدون تبلیغات',
                'دانلود آفلاین',
                '20% تخفیف ویژه'
            ]
        ],
        '1year' => [
            'name' => 'اشتراک یک ساله',
            'price' => 400000, // in IRR
            'duration_days' => 365,
            'features' => [
                'دسترسی به تمام داستان‌ها',
                'اپیزودهای پولی',
                'بدون تبلیغات',
                'دانلود آفلاین',
                '33% تخفیف ویژه'
            ]
        ]
    ],

    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),

    'timeout' => 30, // seconds

    'retry_attempts' => 3,

    'log_payments' => env('PAYMENT_LOG', true),
];
