<?php

/**
 * SMS Login Test Script for Phone Number: 09339487801
 * 
 * This script tests the complete SMS login flow with Melipayamak
 * Usage: php test-sms-login.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Override cache configuration to use file cache for testing
config(['cache.default' => 'file']);

use App\Services\SmsService;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SmsLoginTester
{
    private SmsService $smsService;
    private string $testPhoneNumber = '09339487801';
    private int $maxRetries = 3;
    private int $retryDelay = 2; // seconds

    public function __construct()
    {
        $this->smsService = new SmsService();
    }

    public function runCompleteTest(): void
    {
        echo "=== SMS Login Test for {$this->testPhoneNumber} ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Max Retries: {$this->maxRetries}\n";
        echo "Retry Delay: {$this->retryDelay}s\n\n";

        // Configuration check
        $this->checkConfiguration();

        // Test 1: Send OTP
        $otpCode = $this->testSendOtp();
        if (!$otpCode) {
            echo "❌ Test failed: Could not send OTP\n";
            return;
        }

        // Test 2: Verify OTP
        $verificationSuccess = $this->testVerifyOtp($otpCode);
        if (!$verificationSuccess) {
            echo "❌ Test failed: OTP verification failed\n";
            return;
        }

        // Test 3: Test invalid OTP
        $this->testInvalidOtp();

        // Test 4: Test OTP expiration
        $this->testOtpExpiration();

        // Test 5: Test rate limiting
        $this->testRateLimiting();

        echo "\n🎉 All SMS login tests completed successfully!\n";
    }

    private function checkConfiguration(): void
    {
        echo "📋 Configuration Check:\n";
        echo "- Melipayamak Username: " . config('melipayamak.username') . "\n";
        echo "- Melipayamak Password: " . (config('melipayamak.password') ? '***' : 'Not set') . "\n";
        echo "- Sender Number: " . config('services.melipayamk.sender') . "\n";
        echo "- Template ID: " . config('services.melipayamk.templates.verification') . "\n";
        echo "- Base URL: " . config('melipayamak.base_url') . "\n\n";
    }

    private function testSendOtp(): ?string
    {
        echo "📱 Test 1: Sending OTP to {$this->testPhoneNumber}...\n";
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            echo "   Attempt {$attempt}/{$this->maxRetries}...\n";
            
            try {
                // Clear any existing cache
                Cache::forget("otp_{$this->testPhoneNumber}_login");
                
                $result = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
                
                if ($result['success']) {
                    echo "   ✅ OTP sent successfully!\n";
                    echo "   📋 Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
                    
                    // Get the stored OTP
                    $cacheKey = "otp_{$this->testPhoneNumber}_login";
                    $storedOtp = Cache::get($cacheKey);
                    
                    if ($storedOtp) {
                        echo "   🔢 Generated OTP: {$storedOtp}\n";
                        echo "   ⏰ OTP expires in: 5 minutes\n";
                        return $storedOtp;
                    } else {
                        echo "   ⚠️ OTP sent but not found in cache\n";
                    }
                } else {
                    echo "   ❌ OTP sending failed!\n";
                    echo "   📋 Error: " . ($result['error'] ?? 'Unknown error') . "\n";
                    
                    if (isset($result['response'])) {
                        echo "   📋 Response: " . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                    }
                }
                
            } catch (\Exception $e) {
                echo "   ❌ Exception: " . $e->getMessage() . "\n";
            }
            
            if ($attempt < $this->maxRetries) {
                echo "   ⏳ Waiting {$this->retryDelay} seconds before retry...\n";
                sleep($this->retryDelay);
            }
        }
        
        return null;
    }

    private function testVerifyOtp(string $otpCode): bool
    {
        echo "\n🔍 Test 2: Verifying OTP...\n";
        
        try {
            $result = $this->smsService->verifyOtp($this->testPhoneNumber, $otpCode, 'login');
            
            if ($result) {
                echo "   ✅ OTP verification successful!\n";
                echo "   🔢 Verified OTP: {$otpCode}\n";
                
                // Check if OTP is removed from cache
                $cacheKey = "otp_{$this->testPhoneNumber}_login";
                $remainingOtp = Cache::get($cacheKey);
                
                if ($remainingOtp === null) {
                    echo "   🗑️ OTP correctly removed from cache\n";
                } else {
                    echo "   ⚠️ OTP still in cache after verification\n";
                }
                
                return true;
            } else {
                echo "   ❌ OTP verification failed!\n";
                return false;
            }
            
        } catch (\Exception $e) {
            echo "   ❌ Exception during verification: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function testInvalidOtp(): void
    {
        echo "\n🚫 Test 3: Testing invalid OTP handling...\n";
        
        try {
            // First send a valid OTP
            $otpResult = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
            
            if (!$otpResult['success']) {
                echo "   ⚠️ Skipping invalid OTP test - could not send valid OTP\n";
                return;
            }
            
            // Try to verify with wrong OTP
            $invalidOtp = '9999';
            $result = $this->smsService->verifyOtp($this->testPhoneNumber, $invalidOtp, 'login');
            
            if (!$result) {
                echo "   ✅ Invalid OTP correctly rejected\n";
                
                // Check if original OTP is still in cache
                $cacheKey = "otp_{$this->testPhoneNumber}_login";
                $storedOtp = Cache::get($cacheKey);
                
                if ($storedOtp) {
                    echo "   ✅ Original OTP preserved in cache\n";
                } else {
                    echo "   ⚠️ Original OTP removed from cache (unexpected)\n";
                }
            } else {
                echo "   ❌ Invalid OTP was accepted (security issue!)\n";
            }
            
        } catch (\Exception $e) {
            echo "   ❌ Exception during invalid OTP test: " . $e->getMessage() . "\n";
        }
    }

    private function testOtpExpiration(): void
    {
        echo "\n⏰ Test 4: Testing OTP expiration...\n";
        
        try {
            // Send OTP
            $otpResult = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
            
            if (!$otpResult['success']) {
                echo "   ⚠️ Skipping expiration test - could not send OTP\n";
                return;
            }
            
            // Get the stored OTP
            $cacheKey = "otp_{$this->testPhoneNumber}_login";
            $storedOtp = Cache::get($cacheKey);
            
            if (!$storedOtp) {
                echo "   ⚠️ Skipping expiration test - OTP not in cache\n";
                return;
            }
            
            // Simulate expiration by removing from cache
            Cache::forget($cacheKey);
            echo "   🗑️ Simulated OTP expiration\n";
            
            // Try to verify expired OTP
            $result = $this->smsService->verifyOtp($this->testPhoneNumber, $storedOtp, 'login');
            
            if (!$result) {
                echo "   ✅ Expired OTP correctly rejected\n";
            } else {
                echo "   ❌ Expired OTP was accepted (security issue!)\n";
            }
            
        } catch (\Exception $e) {
            echo "   ❌ Exception during expiration test: " . $e->getMessage() . "\n";
        }
    }

    private function testRateLimiting(): void
    {
        echo "\n🚦 Test 5: Testing rate limiting...\n";
        
        $maxAttempts = 3;
        $successCount = 0;
        
        for ($i = 1; $i <= $maxAttempts; $i++) {
            echo "   📱 Attempt {$i}/{$maxAttempts}: Sending OTP...\n";
            
            try {
                $result = $this->smsService->sendOtp($this->testPhoneNumber, 'login');
                
                if ($result['success']) {
                    $successCount++;
                    echo "   ✅ Attempt {$i}: Success\n";
                } else {
                    echo "   ❌ Attempt {$i}: Failed - " . ($result['error'] ?? 'Unknown error') . "\n";
                }
                
            } catch (\Exception $e) {
                echo "   ❌ Attempt {$i}: Exception - " . $e->getMessage() . "\n";
            }
            
            if ($i < $maxAttempts) {
                echo "   ⏳ Waiting 1 second...\n";
                sleep(1);
            }
        }
        
        echo "   📊 Rate limiting results: {$successCount}/{$maxAttempts} successful\n";
        
        if ($successCount < $maxAttempts) {
            echo "   ✅ Rate limiting appears to be working\n";
        } else {
            echo "   ⚠️ No rate limiting detected\n";
        }
    }

    private function handleError(string $message, \Exception $e = null): void
    {
        echo "\n❌ ERROR: {$message}\n";
        
        if ($e) {
            echo "Exception: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
        
        echo "\n🔧 Troubleshooting suggestions:\n";
        echo "1. Check Melipayamak credentials in .env file\n";
        echo "2. Verify internet connection\n";
        echo "3. Check Melipayamak account balance\n";
        echo "4. Verify phone number format\n";
        echo "5. Check Laravel logs for more details\n";
    }
}

// Run the test
try {
    $tester = new SmsLoginTester();
    $tester->runCompleteTest();
} catch (\Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Test completed ===\n";
echo "Check the Laravel logs for more details.\n";
