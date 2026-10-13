<?php

namespace Tests\Feature;

use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class SmsOutboundBlockedTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_send_paths_never_reach_the_network_during_tests(): void
    {
        $source = (string) file_get_contents(app_path('Services/SmsService.php'));
        $this->assertStringNotContainsString('curl_init', $source);
        $this->assertStringNotContainsString('MelipayamakApi', $source);
        $this->assertSame('log', config('sms.driver'));
        $this->assertSame('', config('melipayamak.username'));
        $this->assertSame('', config('melipayamak.password'));

        $service = new SmsService();
        $phone = '09120000000';

        $sms = $service->sendSms($phone, 'blocked');
        $otp = $service->sendOtp($phone, 'login');
        $template = $service->sendSmsWithTemplate($phone, 485459, ['123456']);
        $delivery = $service->checkDeliveryStatus('999');

        $procedural = new ReflectionMethod(SmsService::class, 'sendSmsWithProceduralPhp');
        $procedural->setAccessible(true);
        $proceduralResult = $procedural->invoke($service, $phone, 485459, '123456');

        $this->assertTrue($sms['success']);
        $this->assertSame('log', $sms['method']);
        $this->assertSame('FAKE', $sms['response']['StrRetStatus']);
        $this->assertTrue($otp['success']);
        $this->assertTrue($template['success']);
        $this->assertTrue($delivery['success']);
        $this->assertFalse($delivery['delivered']);
        $this->assertTrue($proceduralResult['success']);
        $this->assertSame('procedural_php', $proceduralResult['method']);

        Http::assertNothingSent();
    }

    public function test_melipayamak_driver_without_an_http_fake_cannot_leave_the_process(): void
    {
        config([
            'sms.driver' => 'melipayamak',
            'melipayamak.username' => 'phpunit-user',
            'melipayamak.password' => 'phpunit-pass',
            'services.melipayamk.sender' => '1000',
        ]);

        $service = new SmsService();
        $result = $service->sendSms('09120000000', 'must not leave');

        $this->assertFalse($result['success']);
        Http::assertNothingSent();

        $template = $service->sendSmsWithTemplate('09120000000', 485459, ['123456']);
        $this->assertFalse($template['success']);
        Http::assertNothingSent();

        $delivery = $service->checkDeliveryStatus('999');
        $this->assertFalse($delivery['success']);
        Http::assertNothingSent();
    }
}
