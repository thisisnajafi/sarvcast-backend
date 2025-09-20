<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsServiceTest extends TestCase
{
    protected SmsService $smsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->smsService = new SmsService();
    }

    /**
     * Test SMS sending functionality
     */
    public function test_send_sms_success(): void
    {
        // Mock successful HTTP response
        Http::fake([
            'rest.payamak-panel.com/*' => Http::response([
                'RetStatus' => 1,
                'StrRetStatus' => 'SUCCESS',
                'Value' => '123456789'
            ], 200)
        ]);

        $result = $this->smsService->sendSms('09136708883', 'Test message from SarvCast');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('message_id', $result);
        $this->assertEquals('123456789', $result['message_id']);
    }

    /**
     * Test SMS sending with failure response
     */
    public function test_send_sms_failure(): void
    {
        // Mock failed HTTP response
        Http::fake([
            'rest.payamak-panel.com/*' => Http::response([
                'RetStatus' => 0,
                'StrRetStatus' => 'FAILED',
                'Value' => 'Invalid credentials'
            ], 200)
        ]);

        $result = $this->smsService->sendSms('09136708883', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('response', $result);
    }

    /**
     * Test SMS sending with network error
     */
    public function test_send_sms_network_error(): void
    {
        // Mock network error
        Http::fake([
            'rest.payamak-panel.com/*' => Http::response([], 500)
        ]);

        $result = $this->smsService->sendSms('09136708883', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test OTP generation and sending
     */
    public function test_send_otp(): void
    {
        Http::fake([
            'rest.payamak-panel.com/*' => Http::response([
                'RetStatus' => 1,
                'StrRetStatus' => 'SUCCESS',
                'Value' => '123456789'
            ], 200)
        ]);

        $result = $this->smsService->sendOtp('09136708883', 'verification');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('response', $result);
    }

    /**
     * Test OTP verification
     */
    public function test_verify_otp(): void
    {
        // First send OTP
        Http::fake([
            'rest.payamak-panel.com/*' => Http::response([
                'RetStatus' => 1,
                'StrRetStatus' => 'SUCCESS',
                'Value' => '123456789'
            ], 200)
        ]);

        $this->smsService->sendOtp('09136708883', 'verification');

        // Mock the cache to return a test OTP
        $testOtp = '123456';
        cache(['otp_09136708883_verification' => $testOtp], 300);

        $result = $this->smsService->verifyOtp('09136708883', $testOtp, 'verification');

        $this->assertTrue($result);
    }

    /**
     * Test phone number formatting
     */
    public function test_phone_number_formatting(): void
    {
        $reflection = new \ReflectionClass($this->smsService);
        $method = $reflection->getMethod('formatPhoneNumber');
        $method->setAccessible(true);

        // Test various phone number formats
        $this->assertEquals('989136708883', $method->invoke($this->smsService, '09136708883'));
        $this->assertEquals('989136708883', $method->invoke($this->smsService, '9136708883'));
        $this->assertEquals('989136708883', $method->invoke($this->smsService, '+989136708883'));
        $this->assertEquals('989136708883', $method->invoke($this->smsService, '98-913-670-8883'));
    }

    /**
     * Test payment notification sending
     */
    public function test_send_payment_notification(): void
    {
        Http::fake([
            'rest.payamak-panel.com/*' => Http::response([
                'RetStatus' => 1,
                'StrRetStatus' => 'SUCCESS',
                'Value' => '123456789'
            ], 200)
        ]);

        $result = $this->smsService->sendPaymentNotification('09136708883', 100000, 'IRT');

        $this->assertTrue($result['success']);
        $this->assertStringContains('100,000', $result['response']['text'] ?? '');
    }

    /**
     * Test rate limiting functionality
     */
    public function test_rate_limiting(): void
    {
        $phoneNumber = '09136708883';
        $purpose = 'verification';

        // Test when no attempts exist
        $this->assertFalse($this->smsService->hasTooManyAttempts($phoneNumber, $purpose));
        $this->assertEquals(5, $this->smsService->getRemainingAttempts($phoneNumber, $purpose));

        // Create some test attempts (this would normally be done through the database)
        // For this test, we'll just verify the method exists and returns expected types
        $this->assertIsBool($this->smsService->hasTooManyAttempts($phoneNumber, $purpose));
        $this->assertIsInt($this->smsService->getRemainingAttempts($phoneNumber, $purpose));
    }
}
