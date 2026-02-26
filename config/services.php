<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'melipayamk' => [
        'token' => env('MELIPAYAMK_TOKEN', '77c431b7-aec5-4313-b744-d2f16bf760ab'),
        'sender' => env('MELIPAYAMK_SENDER', '50002710008883'),
        'templates' => [
            'verification' => env('MELIPAYAMK_VERIFICATION_TEMPLATE', 372382),
        ],
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID', '77751ff3-c1cc-411b-869d-2ac7d7b02f88'),
        'callback_url' => env('ZARINPAL_CALLBACK_URL', 'https://my.sarvcast.ir'),
        'sandbox' => env('ZARINPAL_SANDBOX', false),
    ],

    'cafebazaar' => [
        'package_name' => env('CAFEBAZAAR_PACKAGE_NAME', 'com.sarvabi.sarvcast'),
        // API key from Pishkhan; sent in header CAFEBAZAAR-PISHKHAN-API-SECRET (doc: developers.cafebazaar.ir)
        'api_key' => env('CAFEBAZAAR_API_KEY'),
        'api_header_name' => env('CAFEBAZAAR_API_HEADER_NAME', 'CAFEBAZAAR-PISHKHAN-API-SECRET'),
        // Base for subscription URL: GET applications/<package_name>/subscriptions/<subscription_id>/purchases/<purchase_token>
        'api_base_url' => env('CAFEBAZAAR_API_BASE_URL', 'https://pardakht.cafebazaar.ir/devapi/v2/api'),
        // One-time in-app purchase: POST validate/inapp/purchases/
        'api_url' => env('CAFEBAZAAR_API_URL', 'https://pardakht.cafebazaar.ir/devapi/v2/api/validate/inapp/purchases/'),
        'acknowledge_url' => env('CAFEBAZAAR_ACKNOWLEDGE_URL', 'https://pardakht.cafebazaar.ir/devapi/v2/api/acknowledge'),
        'product_mapping' => [
            'subscription_1month' => '1month',
            'subscription_3months' => '3months',
            'subscription_6months' => '6months',
            'subscription_1year' => '1year',
            '1-month-sub' => '1month',
            '3-month-sub' => '3months',
            '6-month-sub' => '6months',
            '1-year-sub' => '1year',
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'), // Group or channel chat ID for payment notifications
        'enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true), // Set false to disable even when token/chat_id are set
    ],

    'myket' => [
        'package_name' => env('MYKET_PACKAGE_NAME', 'ir.sarvcast.app'),
        'api_key' => env('MYKET_API_KEY'),
        'api_url' => env('MYKET_API_URL', 'https://developer.myket.ir/api/applications/validatePurchase'),
        'subscription_api_url' => env('MYKET_SUBSCRIPTION_API_URL', 'https://developer.myket.ir/api/applications/validateSubscription'),
        'product_mapping' => [
            'subscription_1month' => '1month',
            'subscription_3months' => '3months',
            'subscription_6months' => '6months',
            'subscription_1year' => '1year',
        ],
    ],

];
