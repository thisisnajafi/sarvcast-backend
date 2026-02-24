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
        'api_key' => env('CAFEBAZAAR_API_KEY'),
        // If you get 404, try the other path in .env (e.g. .../api/validate vs .../validate)
        'api_url' => env('CAFEBAZAAR_API_URL', 'https://pardakht.cafebazaar.ir/devapi/v2/validate'),
        'subscription_api_url' => env('CAFEBAZAAR_SUBSCRIPTION_API_URL', 'https://pardakht.cafebazaar.ir/devapi/v2/validate/subscription'),
        'acknowledge_url' => env('CAFEBAZAAR_ACKNOWLEDGE_URL', 'https://pardakht.cafebazaar.ir/devapi/v2/acknowledge'),
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
