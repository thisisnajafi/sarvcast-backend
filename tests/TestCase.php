<?php

namespace Tests;

use App\Services\SmsService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sms.driver' => 'log',
            'melipayamak.username' => '',
            'melipayamak.password' => '',
            'services.melipayamk.token' => '',
            'services.melipayamk.sender' => '',
            'notification.sms.api_key' => '',
            'notification.firebase.server_key' => '',
            'notification.push.enabled' => false,
            'mail.default' => 'array',
            'services.zarinpal.sandbox' => true,
            'services.cafebazaar.api_key' => '',
            'services.myket.api_key' => '',
            'services.myket.partner_token' => '',
            'services.telegram.bot_token' => '',
            'services.telegram.chat_id' => '',
            'services.telegram.enabled' => false,
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
            'https://fcm.googleapis.com/*' => Http::response(['success' => 1], 200),
            'https://oauth2.googleapis.com/*' => Http::response([
                'access_token' => 'phpunit-fcm-token',
                'expires_in' => 3600,
            ], 200),
            'https://api.sms.ir/*' => Http::response(['status' => 'success'], 200),
        ]);

        Mail::fake();

        $this->app->forgetInstance(SmsService::class);
        $this->app->bind(SmsService::class, function () {
            return new SmsService();
        });
    }
}
