<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmsService
{
    private string $apiToken;
    private string $baseUrl = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';

    public function __construct()
    {
        $this->apiToken = config('services.melipayamk.token', '77c431b7-aec5-4313-b744-d2f16bf760ab');
    }

    /**
     * Send SMS using Melipayamk service
     */
    public function sendSms(string $to, string $message): array
    {
        try {
            $response = Http::timeout(30)->asForm()->post($this->baseUrl, [
                'username' => $this->apiToken,
                'password' => $this->apiToken,
                'to' => $this->formatPhoneNumber($to),
                'from' => config('services.melipayamk.sender', '50004001414000'),
                'text' => $message,
                'isFlash' => false
            ]);

            $result = $response->json();

            Log::info('SMS sent via Melipayamk', [
                'to' => $to,
                'message' => $message,
                'response' => $result
            ]);

            return [
                'success' => $response->successful() && isset($result['RetStatus']) && $result['RetStatus'] == 1,
                'response' => $result,
                'message_id' => $result['StrRetStatus'] ?? $result['Value'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'to' => $to,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate and send OTP code
     */
    public function sendOtp(string $phoneNumber, string $purpose = 'verification'): array
    {
        $otpCode = $this->generateOtpCode();
        
        // Store OTP in cache for 5 minutes
        $cacheKey = "otp_{$phoneNumber}_{$purpose}";
        Cache::put($cacheKey, $otpCode, 300); // 5 minutes

        $message = $this->getOtpMessage($otpCode, $purpose);
        
        $result = $this->sendSms($phoneNumber, $message);
        
        if ($result['success']) {
            // Store OTP attempt in database
            $this->storeOtpAttempt($phoneNumber, $otpCode, $purpose);
        }

        return $result;
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(string $phoneNumber, string $code, string $purpose = 'verification'): bool
    {
        $cacheKey = "otp_{$phoneNumber}_{$purpose}";
        $storedCode = Cache::get($cacheKey);

        if ($storedCode && $storedCode === $code) {
            // Remove OTP from cache after successful verification
            Cache::forget($cacheKey);
            
            // Update OTP attempt as verified
            $this->updateOtpAttempt($phoneNumber, $code, $purpose, true);
            
            return true;
        }

        // Log failed attempt
        $this->updateOtpAttempt($phoneNumber, $code, $purpose, false);
        
        return false;
    }

    /**
     * Send payment notification to affiliate
     */
    public function sendPaymentNotification(string $phoneNumber, float $amount, string $currency = 'IRT'): array
    {
        $message = "پرداخت کمیسیون شما به مبلغ " . number_format($amount) . " " . $currency . " به حساب بانکی شما واریز شد. با تشکر از همکاری شما.";
        
        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Generate 6-digit OTP code
     */
    private function generateOtpCode(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Format phone number for Iran
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if not present
        if (strlen($phoneNumber) == 10 && substr($phoneNumber, 0, 1) == '9') {
            $phoneNumber = '98' . $phoneNumber;
        } elseif (strlen($phoneNumber) == 11 && substr($phoneNumber, 0, 2) == '09') {
            $phoneNumber = '98' . substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }

    /**
     * Get OTP message based on purpose
     */
    private function getOtpMessage(string $code, string $purpose): string
    {
        switch ($purpose) {
            case 'login':
                return "کد ورود شما: {$code}\nاین کد تا ۵ دقیقه معتبر است.";
            case 'admin_2fa':
                return "کد تایید دو مرحله‌ای: {$code}\nاین کد تا ۵ دقیقه معتبر است.";
            case 'verification':
            default:
                return "کد تایید شما: {$code}\nاین کد تا ۵ دقیقه معتبر است.";
        }
    }

    /**
     * Store OTP attempt in database
     */
    private function storeOtpAttempt(string $phoneNumber, string $code, string $purpose): void
    {
        try {
            \App\Models\OtpAttempt::create([
                'phone_number' => $phoneNumber,
                'code' => $code,
                'purpose' => $purpose,
                'verified' => false,
                'expires_at' => now()->addMinutes(5)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store OTP attempt', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update OTP attempt verification status
     */
    private function updateOtpAttempt(string $phoneNumber, string $code, string $purpose, bool $verified): void
    {
        try {
            \App\Models\OtpAttempt::where('phone_number', $phoneNumber)
                ->where('code', $code)
                ->where('purpose', $purpose)
                ->where('verified', false)
                ->where('expires_at', '>', now())
                ->update(['verified' => $verified]);
        } catch (\Exception $e) {
            Log::error('Failed to update OTP attempt', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if phone number has too many OTP attempts
     */
    public function hasTooManyAttempts(string $phoneNumber, string $purpose = 'verification'): bool
    {
        $attempts = \App\Models\OtpAttempt::where('phone_number', $phoneNumber)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $attempts >= 5; // Max 5 attempts per hour
    }

    /**
     * Get remaining attempts for phone number
     */
    public function getRemainingAttempts(string $phoneNumber, string $purpose = 'verification'): int
    {
        $attempts = \App\Models\OtpAttempt::where('phone_number', $phoneNumber)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return max(0, 5 - $attempts);
    }
}