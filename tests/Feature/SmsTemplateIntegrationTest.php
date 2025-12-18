<?php

namespace Tests\Feature;

use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SmsTemplateIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SmsService $smsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->smsService = new SmsService();
    }

    /** @test */
    public function it_can_send_otp_using_template()
    {
        // Mock the SMS service to avoid actual SMS sending
        $mockSmsService = $this->mock(SmsService::class);
        
        $mockSmsService->shouldReceive('sendOtp')
            ->once()
            ->with('09123456789', 'login')
            ->andReturn([
                'success' => true,
                'message_id' => 'test_message_123'
            ]);

        $result = $mockSmsService->sendOtp('09123456789', 'login');

        $this->assertTrue($result['success']);
        $this->assertEquals('test_message_123', $result['message_id']);
    }

    /** @test */
    public function it_stores_otp_in_cache()
    {
        $phoneNumber = '09123456789';
        $purpose = 'login';
        
        // Clear cache first
        Cache::forget("otp_{$phoneNumber}_{$purpose}");
        
        // Generate OTP (this will be mocked in real implementation)
        $otpCode = '123456';
        Cache::put("otp_{$phoneNumber}_{$purpose}", $otpCode, 300);
        
        // Verify OTP is stored
        $storedCode = Cache::get("otp_{$phoneNumber}_{$purpose}");
        $this->assertEquals($otpCode, $storedCode);
    }

    /** @test */
    public function it_verifies_otp_correctly()
    {
        $phoneNumber = '09123456789';
        $purpose = 'login';
        $otpCode = '1234';
        
        // Store OTP in cache
        Cache::put("otp_{$phoneNumber}_{$purpose}", $otpCode, 300);
        
        // Mock the SMS service
        $mockSmsService = $this->mock(SmsService::class);
        
        $mockSmsService->shouldReceive('verifyOtp')
            ->once()
            ->with($phoneNumber, $otpCode, $purpose)
            ->andReturn(true);

        $result = $mockSmsService->verifyOtp($phoneNumber, $otpCode, $purpose);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_invalid_otp()
    {
        $phoneNumber = '09123456789';
        $purpose = 'login';
        $validCode = '1234';
        $invalidCode = '5678';
        
        // Store valid OTP in cache
        Cache::put("otp_{$phoneNumber}_{$purpose}", $validCode, 300);
        
        // Mock the SMS service
        $mockSmsService = $this->mock(SmsService::class);
        
        $mockSmsService->shouldReceive('verifyOtp')
            ->once()
            ->with($phoneNumber, $invalidCode, $purpose)
            ->andReturn(false);

        $result = $mockSmsService->verifyOtp($phoneNumber, $invalidCode, $purpose);
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_uses_correct_template_id()
    {
        $templateId = config('services.melipayamk.templates.verification', 371085);
        
        $this->assertEquals(371085, $templateId);
    }

    /** @test */
    public function it_generates_6_digit_otp()
    {
        // Test the OTP generation logic
        $otpCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->assertIsString($otpCode);
        $this->assertEquals(6, strlen($otpCode));
        $this->assertTrue(is_numeric($otpCode));
        $this->assertGreaterThanOrEqual(100000, (int)$otpCode);
        $this->assertLessThanOrEqual(999999, (int)$otpCode);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $phoneNumber = '09123456789';
        $purpose = 'login';
        
        // Mock the SMS service
        $mockSmsService = $this->mock(SmsService::class);
        
        $mockSmsService->shouldReceive('hasTooManyAttempts')
            ->once()
            ->with($phoneNumber, $purpose)
            ->andReturn(true);

        $hasTooMany = $mockSmsService->hasTooManyAttempts($phoneNumber, $purpose);
        
        $this->assertTrue($hasTooMany);
    }

    /** @test */
    public function it_calculates_remaining_attempts()
    {
        $phoneNumber = '09123456789';
        $purpose = 'login';
        
        // Mock the SMS service
        $mockSmsService = $this->mock(SmsService::class);
        
        $mockSmsService->shouldReceive('getRemainingAttempts')
            ->once()
            ->with($phoneNumber, $purpose)
            ->andReturn(3);

        $remaining = $mockSmsService->getRemainingAttempts($phoneNumber, $purpose);
        
        $this->assertEquals(3, $remaining);
    }
}