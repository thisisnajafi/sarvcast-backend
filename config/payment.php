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
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'sandbox' => env('ZARINPAL_SANDBOX', false),
        'api_url' => env('ZARINPAL_SANDBOX', false) 
            ? 'https://sandbox.zarinpal.com/pg/rest/WebGate/' 
            : 'https://api.zarinpal.com/pg/v4/payment/',
    ],

    'callback_url' => env('PAYMENT_CALLBACK_URL', env('APP_URL') . '/payment/callback'),

    'success_url' => env('PAYMENT_SUCCESS_URL', env('APP_URL') . '/payment/success'),
    'failure_url' => env('PAYMENT_FAILURE_URL', env('APP_URL') . '/payment/failure'),

    'currencies' => [
        'IRR' => 'ریال ایران',
        'USD' => 'دلار آمریکا',
    ],

    'default_currency' => 'IRR',

    'subscription_plans' => [
        'monthly' => [
            'name' => 'اشتراک ماهانه',
            'price' => 50000, // in IRR
            'duration_days' => 30,
            'features' => [
                'دسترسی به تمام داستان‌ها',
                'اپیزودهای پولی',
                'بدون تبلیغات',
                'دانلود آفلاین'
            ]
        ],
        'yearly' => [
            'name' => 'اشتراک سالانه',
            'price' => 500000, // in IRR
            'duration_days' => 365,
            'features' => [
                'دسترسی به تمام داستان‌ها',
                'اپیزودهای پولی',
                'بدون تبلیغات',
                'دانلود آفلاین',
                '20% تخفیف ویژه'
            ]
        ]
    ],

    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),

    'timeout' => 30, // seconds

    'retry_attempts' => 3,

    'log_payments' => env('PAYMENT_LOG', true),
];
