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

];
