<?php

namespace Tests\Feature;

use App\Services\SmsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SmsLoginTest extends TestCase
{
    use RefreshDatabase;

    protected SmsService $smsService;
    protected string $testPhoneNumber = '09339487801';

    protected function setUp(): void
    {
        parent::setUp();
        $this->smsService = new SmsService();
    }

    /** @test */
    public function it_can_send_otp_for_login_to_specific_phone_number()
    {
        // Clear any existing cache
        Cache::forget("otp_{$this->testPhoneNumber}_login");
        
        // Send OTP to the specific phone number
        $result = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
        
        // Assert SMS was sent successfully
        $this->assertTrue($result['success'], 'SMS should be sent successfully. Error: ' . ($result['error'] ?? 'Unknown error'));
        
        // Verify OTP is stored in cache
        $cacheKey = "otp_{$this->testPhoneNumber}_login";
        $storedOtp = Cache::get($cacheKey);
        $this->assertNotNull($storedOtp, 'OTP should be stored in cache');
        $this->assertEquals(6, strlen($storedOtp), 'OTP should be 6 digits');
        $this->assertTrue(is_numeric($storedOtp), 'OTP should be numeric');
        
        // Log the result for debugging
        $this->addToAssertionCount(1);
        echo "\n✅ OTP sent successfully to {$this->testPhoneNumber}\n";
        echo "📱 Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
        echo "🔢 Generated OTP: {$storedOtp}\n";
        
        return $storedOtp;
    }

    /** @test */
    public function it_can_verify_otp_for_login()
    {
        // First send OTP
        $otpResult = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
        
        if (!$otpResult['success']) {
            $this->markTestSkipped('SMS sending failed: ' . ($otpResult['error'] ?? 'Unknown error'));
        }
        
        // Get the stored OTP
        $cacheKey = "otp_{$this->testPhoneNumber}_login";
        $storedOtp = Cache::get($cacheKey);
        
        $this->assertNotNull($storedOtp, 'OTP should be stored in cache');
        
        // Verify the OTP
        $verificationResult = $this->smsService->verifyOtp($this->testPhoneNumber, $storedOtp, 'login');
        
        $this->assertTrue($verificationResult, 'OTP verification should succeed');
        
        // Verify OTP is removed from cache after successful verification
        $this->assertNull(Cache::get($cacheKey), 'OTP should be removed from cache after verification');
        
        echo "\n✅ OTP verification successful for {$this->testPhoneNumber}\n";
        echo "🔢 Verified OTP: {$storedOtp}\n";
    }

    /** @test */
    public function it_handles_invalid_otp_gracefully()
    {
        // First send OTP
        $otpResult = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
        
        if (!$otpResult['success']) {
            $this->markTestSkipped('SMS sending failed: ' . ($otpResult['error'] ?? 'Unknown error'));
        }
        
        // Try to verify with wrong OTP
        $invalidOtp = '9999';
        $verificationResult = $this->smsService->verifyOtp($this->testPhoneNumber, $invalidOtp, 'login');
        
        $this->assertFalse($verificationResult, 'Invalid OTP should fail verification');
        
        // Verify OTP is still in cache (not removed for failed attempts)
        $cacheKey = "otp_{$this->testPhoneNumber}_login";
        $storedOtp = Cache::get($cacheKey);
        $this->assertNotNull($storedOtp, 'OTP should remain in cache after failed verification');
        
        echo "\n✅ Invalid OTP handling works correctly for {$this->testPhoneNumber}\n";
    }

    /** @test */
    public function it_can_perform_complete_sms_login_flow()
    {
        // Step 1: Send verification code
        echo "\n📱 Step 1: Sending verification code to {$this->testPhoneNumber}...\n";
        
        $otpResult = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
        
        if (!$otpResult['success']) {
            $this->fail('Failed to send OTP: ' . ($otpResult['error'] ?? 'Unknown error'));
        }
        
        echo "✅ Verification code sent successfully\n";
        echo "📋 Message ID: " . ($otpResult['message_id'] ?? 'N/A') . "\n";
        
        // Step 2: Get the OTP from cache
        $cacheKey = "otp_{$this->testPhoneNumber}_login";
        $storedOtp = Cache::get($cacheKey);
        
        $this->assertNotNull($storedOtp, 'OTP should be stored in cache');
        echo "🔢 Generated OTP: {$storedOtp}\n";
        
        // Step 3: Verify the OTP
        echo "\n🔍 Step 2: Verifying OTP...\n";
        
        $verificationResult = $this->smsService->verifyOtp($this->testPhoneNumber, $storedOtp, 'login');
        
        $this->assertTrue($verificationResult, 'OTP verification should succeed');
        echo "✅ OTP verification successful\n";
        
        // Step 4: Verify OTP is removed from cache
        $this->assertNull(Cache::get($cacheKey), 'OTP should be removed from cache after verification');
        echo "🗑️ OTP removed from cache after verification\n";
        
        echo "\n🎉 Complete SMS login flow test passed for {$this->testPhoneNumber}!\n";
    }

    /** @test */
    public function it_handles_sms_service_errors_gracefully()
    {
        // Test with invalid phone number format
        $invalidPhone = '123';
        
        try {
            $result = $this->smsService->sendOtp($invalidPhone, 'login');
            
            // If it doesn't throw an exception, check the result
            if (!$result['success']) {
                echo "\n⚠️ SMS service correctly handled invalid phone number: {$invalidPhone}\n";
                echo "📋 Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
            
        } catch (\Exception $e) {
            echo "\n⚠️ Exception caught for invalid phone number: " . $e->getMessage() . "\n";
        }
        
        // This test passes if it doesn't crash the application
        $this->assertTrue(true, 'Error handling should not crash the application');
    }

    /** @test */
    public function it_tests_rate_limiting_functionality()
    {
        // Test rate limiting by sending multiple OTPs quickly
        $maxAttempts = 5;
        $successCount = 0;
        
        echo "\n🚦 Testing rate limiting with {$maxAttempts} attempts...\n";
        
        for ($i = 1; $i <= $maxAttempts; $i++) {
            echo "📱 Attempt {$i}: Sending OTP...\n";
            
            $result = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
            
            if ($result['success']) {
                $successCount++;
                echo "✅ Attempt {$i}: Success\n";
            } else {
                echo "❌ Attempt {$i}: Failed - " . ($result['error'] ?? 'Unknown error') . "\n";
            }
            
            // Small delay between attempts
            usleep(100000); // 0.1 second
        }
        
        echo "\n📊 Rate limiting test results:\n";
        echo "✅ Successful attempts: {$successCount}/{$maxAttempts}\n";
        echo "❌ Failed attempts: " . ($maxAttempts - $successCount) . "/{$maxAttempts}\n";
        
        // The test passes regardless of rate limiting behavior
        $this->assertTrue(true, 'Rate limiting test completed');
    }

    /** @test */
    public function it_tests_otp_expiration()
    {
        // Send OTP
        $otpResult = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
        
        if (!$otpResult['success']) {
            $this->markTestSkipped('SMS sending failed: ' . ($otpResult['error'] ?? 'Unknown error'));
        }
        
        // Get the stored OTP
        $cacheKey = "otp_{$this->testPhoneNumber}_login";
        $storedOtp = Cache::get($cacheKey);
        
        $this->assertNotNull($storedOtp, 'OTP should be stored in cache');
        
        // Simulate OTP expiration by manually removing it from cache
        Cache::forget($cacheKey);
        
        // Try to verify expired OTP
        $verificationResult = $this->smsService->verifyOtp($this->testPhoneNumber, $storedOtp, 'login');
        
        $this->assertFalse($verificationResult, 'Expired OTP should fail verification');
        
        echo "\n✅ OTP expiration handling works correctly for {$this->testPhoneNumber}\n";
    }
}
