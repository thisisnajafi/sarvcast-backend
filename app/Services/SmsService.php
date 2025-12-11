<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Melipayamak\MelipayamakApi;

class SmsService
{
    private string $apiToken;
    private string $username;
    private string $baseUrl = 'http://api.payamak-panel.com/post/send.asmx?wsdl';

    public function __construct()
    {
        $this->apiToken = config('melipayamak.password', 'Prof48017421@#');
        $this->username = config('melipayamak.username', '09136708883');
    }

    /**
     * Send SMS using Melipayamk service
     * Based on: https://github.com/Melipayamak/melipayamak-php
     */
    public function sendSms(string $to, string $message): array
    {
        try {
            // Use Melipayamak library exactly as documented
            $username = $this->username;
            $password = $this->apiToken;
            $api = new MelipayamakApi($username, $password);
            $sms = $api->sms();

            $from = config('services.melipayamk.sender', '50002710008883');

            // Prepare sending data for logging
            $sendingData = [
                'username' => $username,
                'password' => $password,
                'to' => $to,
                'from' => $from,
                'text' => $message,
                'isFlash' => false
            ];

            // Set timeout for Melipayamak library (if supported)
            ini_set('default_socket_timeout', 30);

            // Send SMS exactly as documented with timeout
            $response = $sms->send($to, $from, $message);
            $json = json_decode($response);

            // Log the response
            Log::info('SMS sent via Melipayamk', [
                'melipayamak_username' => $this->username,
                'sending_data' => $sendingData,
                'response' => $json,
                'raw_response' => $response,
                'message_id' => $json->Value ?? null
            ]);

            return [
                'success' => isset($json->RetStatus) && $json->RetStatus == 1,
                'response' => $json,
                'message_id' => $json->Value ?? $json->StrRetStatus ?? null
            ];

        } catch (\Exception $e) {
            // Prepare sending data for error logging
            $sendingData = [
                'username' => $this->username,
                'password' => $this->apiToken,
                'to' => $to,
                'from' => config('services.melipayamk.sender', '50002710008883'),
                'text' => $message,
                'isFlash' => false
            ];

            Log::error('SMS sending failed via Melipayamak library', [
                'melipayamak_username' => $this->username,
                'sending_data' => $sendingData,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'timeout_possible' => strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'Timeout') !== false,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'method' => 'melipayamak_library'
            ];
        }
    }

    /**
     * Send SMS using Melipayamk service with template
     * Based on: https://github.com/Melipayamak/melipayamak-php
     */
    public function sendSmsWithTemplate(string $to, int $templateId, array $parameters = []): array
    {
        // Use procedural PHP method as primary (it works!)
        try {
            $text = implode(';', $parameters);
            $proceduralResult = $this->sendSmsWithProceduralPhp($to, $templateId, $text);
            if ($proceduralResult['success']) {
                return $proceduralResult;
            }
        } catch (\Exception $proceduralError) {
            Log::error('Procedural PHP method failed in sendSmsWithTemplate', [
                'to' => $to,
                'template_id' => $templateId,
                'parameters' => $parameters,
                'error' => $proceduralError->getMessage()
            ]);
        }

        // Fallback to GitHub package method if procedural fails
        try {
            // Use Melipayamak library with REST method
            $username = $this->username;
            $password = $this->apiToken;
            $api = new MelipayamakApi($username, $password);
            $sms = $api->sms('rest');

            $from = config('services.melipayamk.sender', '50002710008883');

            // Prepare sending data for logging
            $sendingData = [
                'username' => $username,
                'password' => $password,
                'to' => $to,
                'from' => $from,
                'template_id' => $templateId,
                'parameters' => $parameters,
                'isFlash' => false
            ];

            // Send SMS with template exactly as documented
            // Parameters: text (semicolon-separated string), to (phone), bodyId (pattern code)
            $text = implode(';', $parameters);
            $response = $sms->sendByBaseNumber($text, $to, $templateId);
            $json = json_decode($response);

            // Log the response
            Log::info('SMS sent via Melipayamk with template (GitHub package fallback)', [
                'melipayamak_username' => $this->username,
                'sending_data' => $sendingData,
                'response' => $json,
                'raw_response' => $response,
                'message_id' => $json->Value ?? null
            ]);

            return [
                'success' => isset($json->RetStatus) && $json->RetStatus == 1,
                'response' => $json,
                'message_id' => $json->Value ?? $json->StrRetStatus ?? null
            ];

        } catch (\Exception $e) {
            // Prepare sending data for error logging
            $sendingData = [
                'username' => $this->username,
                'password' => $this->apiToken,
                'to' => $to,
                'from' => config('services.melipayamk.sender', '50002710008883'),
                'template_id' => $templateId,
                'parameters' => $parameters,
                'isFlash' => false
            ];

            Log::error('Both procedural and GitHub package methods failed in sendSmsWithTemplate', [
                'melipayamak_username' => $this->username,
                'sending_data' => $sendingData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate and send OTP code using Melipayamak template pattern 372382
     * Falls back to regular SMS if template fails
     */
    public function sendOtp(string $phoneNumber, string $purpose = 'verification'): array
    {
        $otpCode = $this->generateOtpCode();

        // Store OTP in cache for 5 minutes
        $cacheKey = "otp_{$phoneNumber}_{$purpose}";
        Cache::put($cacheKey, $otpCode, 300); // 5 minutes

        // Try template SMS first (pattern 372382)
        $templateResult = $this->sendOtpWithTemplate($phoneNumber, $otpCode, $purpose);

        if ($templateResult['success']) {
            return $templateResult;
        }

        // Fallback to regular SMS with template pattern message
        $otpMessage = "کد ورود شما: {$otpCode} این کد 5 دقیقه اعتبار دارد سروکست";

        Log::info('Template SMS failed, using regular SMS fallback', [
            'phone_number' => $phoneNumber,
            'template_error' => $templateResult['error'] ?? 'Unknown error',
            'otp_code' => $otpCode
        ]);

        $result = $this->sendSms($phoneNumber, $otpMessage);

        if ($result['success']) {
            // Store OTP attempt in database
            $this->storeOtpAttempt($phoneNumber, $otpCode, $purpose);
        }

        return $result;
    }

    /**
     * Send OTP using Melipayamak template pattern 372382
     * Template message: "کد ورود شما: {0} این کد 5 دقیقه اعتبار دارد سروکست"
     */
    private function sendOtpWithTemplate(string $phoneNumber, string $otpCode, string $purpose): array
    {
        $templateId = config('services.melipayamk.templates.verification', 372382);

        // Use procedural PHP method as primary (it works!)
        try {
            $proceduralResult = $this->sendSmsWithProceduralPhp($phoneNumber, $templateId, $otpCode);
            if ($proceduralResult['success']) {
                // Store OTP attempt in database
                $this->storeOtpAttempt($phoneNumber, $otpCode, $purpose);
                return $proceduralResult;
            }
        } catch (\Exception $proceduralError) {
            Log::error('Procedural PHP method failed', [
                'phone_number' => $phoneNumber,
                'template_id' => $templateId,
                'otp_code' => $otpCode,
                'error' => $proceduralError->getMessage()
            ]);
        }

        // Fallback to GitHub package method if procedural fails
        try {
            // Use Melipayamak library for template SMS (REST method)
            $username = $this->username;
            $password = $this->apiToken;
            $api = new MelipayamakApi($username, $password);
            $sms = $api->sms('rest');

            // Prepare sending data for logging
            $sendingData = [
                'username' => $username,
                'password' => $password,
                'to' => $phoneNumber,
                'template_id' => $templateId,
                'parameters' => [$otpCode],
                'template_message' => "کد ورود شما: {$otpCode} این کد 5 دقیقه اعتبار دارد سروکست"
            ];

            // Send SMS with template pattern 372382
            // Parameters: text (semicolon-separated string), to (phone), bodyId (pattern code)
            $text = $otpCode; // Single parameter, no need for semicolon
            $response = $sms->sendByBaseNumber($text, $phoneNumber, $templateId);
            $json = json_decode($response);

            // Log the response
            Log::info('SMS sent via Melipayamk template pattern 372382 (GitHub package fallback)', [
                'melipayamak_username' => $this->username,
                'sending_data' => $sendingData,
                'response' => $json,
                'raw_response' => $response,
                'message_id' => $json->Value ?? null
            ]);

            $success = isset($json->RetStatus) && $json->RetStatus == 1;

            if ($success) {
                // Store OTP attempt in database
                $this->storeOtpAttempt($phoneNumber, $otpCode, $purpose);
            }

            return [
                'success' => $success,
                'response' => $json,
                'message_id' => $json->Value ?? $json->StrRetStatus ?? null,
                'method' => 'template_fallback'
            ];

        } catch (\Exception $e) {
            Log::error('Both procedural and GitHub package methods failed', [
                'melipayamak_username' => $this->username,
                'phone_number' => $phoneNumber,
                'template_id' => $templateId,
                'otp_code' => $otpCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'method' => 'template_failed'
            ];
        }
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
     * Generate 6-digit OTP code for template pattern 372382
     */
    private function generateOtpCode(): string
    {
        // Generate random 6-digit OTP code
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

    /**
     * Send SMS using procedural PHP cURL method (fallback)
     * Based on procedural PHP example from Melipayamak documentation
     */
    private function sendSmsWithProceduralPhp(string $to, int $bodyId, string $otpCode): array
    {
        try {
            $username = $this->username;
            $password = $this->apiToken;

            $data = array(
                'username' => $username,
                'password' => $password,
                'text' => $otpCode, // Single parameter, no semicolon needed
                'to' => $to,
                'bodyId' => $bodyId
            );

            $post_data = http_build_query($data);
            $handle = curl_init('https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber');

            curl_setopt($handle, CURLOPT_HTTPHEADER, array(
                'content-type' => 'application/x-www-form-urlencoded'
            ));
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);

            // Add timeout settings to prevent hanging
            curl_setopt($handle, CURLOPT_TIMEOUT, 30); // 30 seconds total timeout
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10); // 10 seconds connection timeout

            $response = curl_exec($handle);
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $curlError = curl_error($handle);
            $totalTime = curl_getinfo($handle, CURLINFO_TOTAL_TIME);
            curl_close($handle);

            Log::info('cURL request details', [
                'phone' => $to,
                'bodyId' => $bodyId,
                'url' => 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber',
                'http_code' => $httpCode,
                'total_time' => $totalTime,
                'response_length' => strlen($response ?? ''),
                'has_error' => !empty($curlError)
            ]);

            if ($curlError) {
                Log::error('cURL Error in SMS sending', [
                    'error' => $curlError,
                    'phone' => $to,
                    'bodyId' => $bodyId
                ]);
                throw new \Exception("cURL Error: " . $curlError);
            }

            if ($httpCode !== 200) {
                Log::error('HTTP Error in SMS sending', [
                    'http_code' => $httpCode,
                    'response' => $response,
                    'phone' => $to,
                    'bodyId' => $bodyId
                ]);
                throw new \Exception("HTTP Error: {$httpCode}");
            }

            Log::info('Procedural PHP SMS response', [
                'phone' => $to,
                'bodyId' => $bodyId,
                'otp_code' => $otpCode,
                'response' => $response,
                'http_code' => $httpCode
            ]);

            $result = json_decode($response);

            return [
                'success' => isset($result->RetStatus) && $result->RetStatus == 1,
                'response' => $result,
                'message_id' => $result->Value ?? null,
                'method' => 'procedural_php'
            ];

        } catch (\Exception $e) {
            Log::error('Procedural PHP SMS failed', [
                'phone' => $to,
                'bodyId' => $bodyId,
                'otp_code' => $otpCode,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'method' => 'procedural_php'
            ];
        }
    }
}
