<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmsService
{
    protected $apiKey;
    protected $apiUrl;
    protected $sender;

    public function __construct()
    {
        $this->apiKey = config('sms.api_key');
        $this->apiUrl = config('sms.api_url');
        $this->sender = config('sms.sender', 'سروکست');
    }

    /**
     * Send SMS verification code
     */
    public function sendVerificationCode(string $phoneNumber): array
    {
        try {
            // Generate verification code
            $code = $this->generateVerificationCode();
            
            // Store code in cache for 5 minutes
            Cache::put("sms_verification_{$phoneNumber}", $code, 300);
            
            // Prepare SMS content
            $message = "کد تایید شما: {$code}\nسروکست - پلتفرم داستان‌های صوتی کودکان";
            
            // Send SMS
            $result = $this->sendSms($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info("SMS verification code sent to {$phoneNumber}");
                return [
                    'success' => true,
                    'message' => 'کد تایید ارسال شد',
                    'expires_in' => 300 // 5 minutes
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در ارسال کد تایید'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('SMS verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در سیستم ارسال پیام'
            ];
        }
    }

    /**
     * Verify SMS code
     */
    public function verifyCode(string $phoneNumber, string $code): array
    {
        try {
            $cachedCode = Cache::get("sms_verification_{$phoneNumber}");
            
            if (!$cachedCode) {
                return [
                    'success' => false,
                    'message' => 'کد تایید منقضی شده است'
                ];
            }
            
            if ($cachedCode === $code) {
                // Remove code from cache after successful verification
                Cache::forget("sms_verification_{$phoneNumber}");
                
                Log::info("SMS verification successful for {$phoneNumber}");
                return [
                    'success' => true,
                    'message' => 'کد تایید صحیح است'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'کد تایید اشتباه است'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('SMS verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در تایید کد'
            ];
        }
    }

    /**
     * Send SMS message
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            // Clean phone number (remove spaces, dashes, etc.)
            $phoneNumber = $this->cleanPhoneNumber($phoneNumber);
            
            // Validate phone number
            if (!$this->isValidPhoneNumber($phoneNumber)) {
                return [
                    'success' => false,
                    'message' => 'شماره تلفن نامعتبر است'
                ];
            }
            
            // Prepare request data
            $data = [
                'api_key' => $this->apiKey,
                'mobile' => $phoneNumber,
                'message' => $message,
                'sender' => $this->sender
            ];
            
            // Send request to SMS provider
            $response = Http::timeout(30)->post($this->apiUrl, $data);
            
            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['status']) && $result['status'] === 'success') {
                    return [
                        'success' => true,
                        'message' => 'پیام با موفقیت ارسال شد',
                        'provider_response' => $result
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'خطا در ارسال پیام: ' . ($result['message'] ?? 'خطای نامشخص')
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در ارتباط با سرویس پیامک'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در سیستم ارسال پیام'
            ];
        }
    }

    /**
     * Send notification SMS
     */
    public function sendNotificationSms(string $phoneNumber, string $title, string $message): array
    {
        $fullMessage = "{$title}\n\n{$message}\n\nسروکست";
        return $this->sendSms($phoneNumber, $fullMessage);
    }

    /**
     * Send subscription SMS
     */
    public function sendSubscriptionSms(string $phoneNumber, string $type, array $data = []): array
    {
        $messages = [
            'subscription_created' => 'اشتراک شما با موفقیت ایجاد شد',
            'subscription_activated' => 'اشتراک شما فعال شد و می‌توانید از تمام امکانات استفاده کنید',
            'subscription_expired' => 'اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک جدید خریداری کنید',
            'subscription_cancelled' => 'اشتراک شما لغو شد',
            'payment_success' => 'پرداخت شما با موفقیت انجام شد',
            'payment_failed' => 'پرداخت شما انجام نشد. لطفاً مجدداً تلاش کنید'
        ];
        
        $message = $messages[$type] ?? 'اعلان جدید از سروکست';
        
        if (!empty($data)) {
            $message .= "\n\nجزئیات: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Generate verification code
     */
    private function generateVerificationCode(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Clean phone number
     */
    private function cleanPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters except +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Handle Iranian phone numbers
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '+98' . substr($phoneNumber, 1);
        } elseif (str_starts_with($phoneNumber, '98')) {
            $phoneNumber = '+' . $phoneNumber;
        } elseif (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+98' . $phoneNumber;
        }
        
        return $phoneNumber;
    }

    /**
     * Validate phone number
     */
    private function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Iranian phone number validation
        $pattern = '/^\+98[0-9]{10}$/';
        return preg_match($pattern, $phoneNumber) === 1;
    }

    /**
     * Get SMS delivery status
     */
    public function getDeliveryStatus(string $messageId): array
    {
        try {
            $data = [
                'api_key' => $this->apiKey,
                'message_id' => $messageId
            ];
            
            $response = Http::timeout(30)->post($this->apiUrl . '/status', $data);
            
            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'status' => $result['status'] ?? 'unknown',
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در دریافت وضعیت پیام'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('SMS status check failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در بررسی وضعیت پیام'
            ];
        }
    }

    /**
     * Get SMS balance
     */
    public function getBalance(): array
    {
        try {
            $data = [
                'api_key' => $this->apiKey
            ];
            
            $response = Http::timeout(30)->post($this->apiUrl . '/balance', $data);
            
            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'balance' => $result['balance'] ?? 0,
                    'data' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در دریافت موجودی'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('SMS balance check failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در بررسی موجودی'
            ];
        }
    }

    /**
     * Check if SMS is enabled
     */
    public function isEnabled(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiUrl);
    }

    /**
     * Get rate limit info
     */
    public function getRateLimitInfo(string $phoneNumber): array
    {
        $key = "sms_rate_limit_{$phoneNumber}";
        $attempts = Cache::get($key, 0);
        $lastAttempt = Cache::get("sms_last_attempt_{$phoneNumber}");
        
        return [
            'attempts' => $attempts,
            'last_attempt' => $lastAttempt,
            'can_send' => $attempts < 5, // Max 5 attempts per hour
            'reset_time' => $lastAttempt ? $lastAttempt + 3600 : null
        ];
    }

    /**
     * Record SMS attempt
     */
    public function recordAttempt(string $phoneNumber): void
    {
        $key = "sms_rate_limit_{$phoneNumber}";
        $attempts = Cache::get($key, 0);
        Cache::put($key, $attempts + 1, 3600); // 1 hour
        Cache::put("sms_last_attempt_{$phoneNumber}", time(), 3600);
    }
}
