<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SmsService
{
    /**
     * SMS Provider configurations
     */
    protected $providers = [
        'kavenegar' => [
            'name' => 'کاوه‌نگار',
            'api_key' => null,
            'sender' => null,
            'base_url' => 'https://api.kavenegar.com/v1',
            'enabled' => false
        ],
        'melipayamak' => [
            'name' => 'ملی‌پیامک',
            'username' => null,
            'password' => null,
            'sender' => null,
            'base_url' => 'https://rest.payamak-resan.com/api',
            'enabled' => false
        ],
        'smsir' => [
            'name' => 'پیامک آی‌آر',
            'api_key' => null,
            'sender' => null,
            'base_url' => 'https://api.sms.ir/v1',
            'enabled' => false
        ]
    ];

    /**
     * SMS Templates
     */
    protected $templates = [
        'verification' => [
            'name' => 'کد تایید',
            'template' => 'کد تایید شما: {code}',
            'variables' => ['code']
        ],
        'welcome' => [
            'name' => 'خوش‌آمدگویی',
            'template' => 'به سروکست خوش آمدید! از شنیدن داستان‌های زیبا لذت ببرید.',
            'variables' => []
        ],
        'subscription_activated' => [
            'name' => 'فعال‌سازی اشتراک',
            'template' => 'اشتراک شما فعال شد. از دسترسی کامل به محتوا لذت ببرید.',
            'variables' => []
        ],
        'subscription_expiring' => [
            'name' => 'انقضای اشتراک',
            'template' => 'اشتراک شما در {days} روز منقضی می‌شود. برای تمدید اقدام کنید.',
            'variables' => ['days']
        ],
        'subscription_expired' => [
            'name' => 'انقضای اشتراک',
            'template' => 'اشتراک شما منقضی شده است. برای ادامه استفاده، اشتراک خود را تمدید کنید.',
            'variables' => []
        ],
        'payment_success' => [
            'name' => 'پرداخت موفق',
            'template' => 'پرداخت شما با موفقیت انجام شد. مبلغ: {amount} ریال',
            'variables' => ['amount']
        ],
        'payment_failed' => [
            'name' => 'پرداخت ناموفق',
            'template' => 'پرداخت شما ناموفق بود. لطفاً مجدداً تلاش کنید.',
            'variables' => []
        ],
        'new_episode' => [
            'name' => 'قسمت جدید',
            'template' => 'قسمت جدید "{episode_title}" از داستان "{story_title}" منتشر شد.',
            'variables' => ['episode_title', 'story_title']
        ],
        'new_story' => [
            'name' => 'داستان جدید',
            'template' => 'داستان جدید "{story_title}" منتشر شد. از شنیدن آن لذت ببرید.',
            'variables' => ['story_title']
        ],
        'password_reset' => [
            'name' => 'بازیابی رمز عبور',
            'template' => 'کد بازیابی رمز عبور شما: {code}',
            'variables' => ['code']
        ]
    ];

    /**
     * Send SMS using specified provider
     */
    public function sendSms(string $phoneNumber, string $message, string $provider = null): array
    {
        try {
            // Validate phone number
            if (!$this->validatePhoneNumber($phoneNumber)) {
                return [
                    'success' => false,
                    'message' => 'شماره تلفن نامعتبر است',
                    'error_code' => 'INVALID_PHONE'
                ];
            }

            // Clean phone number
            $phoneNumber = $this->cleanPhoneNumber($phoneNumber);

            // Check rate limiting
            if (!$this->checkRateLimit($phoneNumber)) {
                return [
                    'success' => false,
                    'message' => 'تعداد پیامک‌های ارسالی بیش از حد مجاز است',
                    'error_code' => 'RATE_LIMIT_EXCEEDED'
                ];
            }

            // Get active provider
            $activeProvider = $this->getActiveProvider($provider);

            if (!$activeProvider) {
                return [
                    'success' => false,
                    'message' => 'ارائه‌دهنده پیامک فعال نیست',
                    'error_code' => 'NO_ACTIVE_PROVIDER'
                ];
            }

            // Send SMS
            $result = $this->sendViaProvider($activeProvider, $phoneNumber, $message);

            // Log the attempt
            $this->logSmsAttempt($phoneNumber, $message, $activeProvider, $result);

            // Update rate limiting
            $this->updateRateLimit($phoneNumber);

            return $result;

        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone_number' => $phoneNumber,
                'message' => $message,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال پیامک',
                'error_code' => 'SEND_FAILED'
            ];
        }
    }

    /**
     * Send SMS using template
     */
    public function sendTemplateSms(string $phoneNumber, string $templateKey, array $variables = [], string $provider = null): array
    {
        try {
            if (!isset($this->templates[$templateKey])) {
                return [
                    'success' => false,
                    'message' => 'قالب پیامک یافت نشد',
                    'error_code' => 'TEMPLATE_NOT_FOUND'
                ];
            }

            $template = $this->templates[$templateKey];
            $message = $this->buildMessageFromTemplate($template, $variables);

            return $this->sendSms($phoneNumber, $message, $provider);

        } catch (\Exception $e) {
            Log::error('Template SMS sending failed', [
                'phone_number' => $phoneNumber,
                'template_key' => $templateKey,
                'variables' => $variables,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال پیامک قالب‌دار',
                'error_code' => 'TEMPLATE_SEND_FAILED'
            ];
        }
    }

    /**
     * Send verification code
     */
    public function sendVerificationCode(string $phoneNumber, string $code, string $provider = null): array
    {
        return $this->sendTemplateSms($phoneNumber, 'verification', ['code' => $code], $provider);
    }

    /**
     * Send welcome message
     */
    public function sendWelcomeMessage(string $phoneNumber, string $provider = null): array
    {
        return $this->sendTemplateSms($phoneNumber, 'welcome', [], $provider);
    }

    /**
     * Send subscription notification
     */
    public function sendSubscriptionNotification(string $phoneNumber, string $type, array $data = [], string $provider = null): array
    {
        $templateKey = 'subscription_' . $type;
        return $this->sendTemplateSms($phoneNumber, $templateKey, $data, $provider);
    }

    /**
     * Send payment notification
     */
    public function sendPaymentNotification(string $phoneNumber, string $type, array $data = [], string $provider = null): array
    {
        $templateKey = 'payment_' . $type;
        return $this->sendTemplateSms($phoneNumber, $templateKey, $data, $provider);
    }

    /**
     * Send content notification
     */
    public function sendContentNotification(string $phoneNumber, string $type, array $data = [], string $provider = null): array
    {
        $templateKey = 'new_' . $type;
        return $this->sendTemplateSms($phoneNumber, $templateKey, $data, $provider);
    }

    /**
     * Send bulk SMS
     */
    public function sendBulkSms(array $phoneNumbers, string $message, string $provider = null): array
    {
        try {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($phoneNumbers as $phoneNumber) {
                $result = $this->sendSms($phoneNumber, $message, $provider);
                $results[] = [
                    'phone_number' => $phoneNumber,
                    'result' => $result
                ];

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }

            return [
                'success' => true,
                'data' => [
                    'total_sent' => count($phoneNumbers),
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'results' => $results
                ],
                'message' => "پیامک‌های گروهی ارسال شد. موفق: {$successCount}, ناموفق: {$failureCount}"
            ];

        } catch (\Exception $e) {
            Log::error('Bulk SMS sending failed', [
                'phone_numbers' => $phoneNumbers,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال پیامک‌های گروهی',
                'error_code' => 'BULK_SEND_FAILED'
            ];
        }
    }

    /**
     * Get SMS statistics
     */
    public function getSmsStatistics(): array
    {
        try {
            $today = now()->format('Y-m-d');
            $thisMonth = now()->format('Y-m');
            $lastMonth = now()->subMonth()->format('Y-m');

            $stats = [
                'today' => [
                    'sent' => Cache::get("sms_stats_sent_{$today}", 0),
                    'failed' => Cache::get("sms_stats_failed_{$today}", 0),
                    'success_rate' => $this->calculateSuccessRate($today)
                ],
                'this_month' => [
                    'sent' => Cache::get("sms_stats_sent_{$thisMonth}", 0),
                    'failed' => Cache::get("sms_stats_failed_{$thisMonth}", 0),
                    'success_rate' => $this->calculateSuccessRate($thisMonth)
                ],
                'last_month' => [
                    'sent' => Cache::get("sms_stats_sent_{$lastMonth}", 0),
                    'failed' => Cache::get("sms_stats_failed_{$lastMonth}", 0),
                    'success_rate' => $this->calculateSuccessRate($lastMonth)
                ],
                'providers' => $this->getProviderStats(),
                'templates' => $this->getTemplateStats()
            ];

            return [
                'success' => true,
                'data' => $stats,
                'message' => 'آمار پیامک‌ها دریافت شد'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get SMS statistics', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت آمار پیامک‌ها'
            ];
        }
    }

    /**
     * Get available templates
     */
    public function getTemplates(): array
    {
        return [
            'success' => true,
            'data' => [
                'templates' => $this->templates
            ],
            'message' => 'قالب‌های پیامک دریافت شد'
        ];
    }

    /**
     * Get provider configurations
     */
    public function getProviders(): array
    {
        return [
            'success' => true,
            'data' => [
                'providers' => $this->providers
            ],
            'message' => 'ارائه‌دهندگان پیامک دریافت شد'
        ];
    }

    /**
     * Send SMS via specific provider
     */
    protected function sendViaProvider(string $provider, string $phoneNumber, string $message): array
    {
        switch ($provider) {
            case 'kavenegar':
                return $this->sendViaKavenegar($phoneNumber, $message);
            case 'melipayamak':
                return $this->sendViaMelipayamak($phoneNumber, $message);
            case 'smsir':
                return $this->sendViaSmsir($phoneNumber, $message);
            default:
                return [
                    'success' => false,
                    'message' => 'ارائه‌دهنده پشتیبانی نمی‌شود',
                    'error_code' => 'UNSUPPORTED_PROVIDER'
                ];
        }
    }

    /**
     * Send SMS via Kavenegar
     */
    protected function sendViaKavenegar(string $phoneNumber, string $message): array
    {
        try {
            $apiKey = config('sms.kavenegar.api_key');
            $sender = config('sms.kavenegar.sender');

            if (!$apiKey || !$sender) {
                return [
                    'success' => false,
                    'message' => 'تنظیمات کاوه‌نگار ناقص است',
                    'error_code' => 'INCOMPLETE_CONFIG'
                ];
            }

            $response = Http::post("https://api.kavenegar.com/v1/{$apiKey}/sms/send.json", [
                'receptor' => $phoneNumber,
                'sender' => $sender,
                'message' => $message
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'پیامک با موفقیت ارسال شد',
                    'data' => [
                        'provider' => 'kavenegar',
                        'message_id' => $data['entries'][0]['messageid'] ?? null
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در ارسال پیامک از طریق کاوه‌نگار',
                    'error_code' => 'KAVENEGAR_ERROR'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در ارتباط با کاوه‌نگار',
                'error_code' => 'KAVENEGAR_CONNECTION_ERROR'
            ];
        }
    }

    /**
     * Send SMS via Melipayamak
     */
    protected function sendViaMelipayamak(string $phoneNumber, string $message): array
    {
        try {
            $username = config('sms.melipayamak.username');
            $password = config('sms.melipayamak.password');
            $sender = config('sms.melipayamak.sender');

            if (!$username || !$password || !$sender) {
                return [
                    'success' => false,
                    'message' => 'تنظیمات ملی‌پیامک ناقص است',
                    'error_code' => 'INCOMPLETE_CONFIG'
                ];
            }

            $response = Http::post('https://rest.payamak-resan.com/api/SendSMS/SendSMS', [
                'username' => $username,
                'password' => $password,
                'to' => $phoneNumber,
                'from' => $sender,
                'text' => $message
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'پیامک با موفقیت ارسال شد',
                    'data' => [
                        'provider' => 'melipayamak',
                        'message_id' => $data['RetStatus'] ?? null
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در ارسال پیامک از طریق ملی‌پیامک',
                    'error_code' => 'MELIPAYAMAK_ERROR'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در ارتباط با ملی‌پیامک',
                'error_code' => 'MELIPAYAMAK_CONNECTION_ERROR'
            ];
        }
    }

    /**
     * Send SMS via SMS.ir
     */
    protected function sendViaSmsir(string $phoneNumber, string $message): array
    {
        try {
            $apiKey = config('sms.smsir.api_key');
            $sender = config('sms.smsir.sender');

            if (!$apiKey || !$sender) {
                return [
                    'success' => false,
                    'message' => 'تنظیمات پیامک آی‌آر ناقص است',
                    'error_code' => 'INCOMPLETE_CONFIG'
                ];
            }

            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey
            ])->post('https://api.sms.ir/v1/send/verify', [
                'mobile' => $phoneNumber,
                'message' => $message
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'پیامک با موفقیت ارسال شد',
                    'data' => [
                        'provider' => 'smsir',
                        'message_id' => $data['data']['messageId'] ?? null
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در ارسال پیامک از طریق پیامک آی‌آر',
                    'error_code' => 'SMSIR_ERROR'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در ارتباط با پیامک آی‌آر',
                'error_code' => 'SMSIR_CONNECTION_ERROR'
            ];
        }
    }

    /**
     * Validate phone number
     */
    protected function validatePhoneNumber(string $phoneNumber): bool
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Check if it's a valid Iranian mobile number
        return preg_match('/^09[0-9]{9}$/', $cleaned) || preg_match('/^9[0-9]{9}$/', $cleaned);
    }

    /**
     * Clean phone number
     */
    protected function cleanPhoneNumber(string $phoneNumber): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if missing
        if (strlen($cleaned) === 10 && str_starts_with($cleaned, '9')) {
            $cleaned = '0' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Check rate limiting
     */
    protected function checkRateLimit(string $phoneNumber): bool
    {
        $key = "sms_rate_limit_{$phoneNumber}";
        $limit = Cache::get($key, 0);
        
        // Allow maximum 10 SMS per hour per phone number
        return $limit < 10;
    }

    /**
     * Update rate limiting
     */
    protected function updateRateLimit(string $phoneNumber): void
    {
        $key = "sms_rate_limit_{$phoneNumber}";
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, 3600); // 1 hour
    }

    /**
     * Get active provider
     */
    protected function getActiveProvider(string $provider = null): ?string
    {
        if ($provider && isset($this->providers[$provider]) && $this->providers[$provider]['enabled']) {
            return $provider;
        }

        // Find first enabled provider
        foreach ($this->providers as $key => $config) {
            if ($config['enabled']) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Build message from template
     */
    protected function buildMessageFromTemplate(array $template, array $variables): string
    {
        $message = $template['template'];
        
        foreach ($variables as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        
        return $message;
    }

    /**
     * Log SMS attempt
     */
    protected function logSmsAttempt(string $phoneNumber, string $message, string $provider, array $result): void
    {
        $logData = [
            'phone_number' => $phoneNumber,
            'message' => $message,
            'provider' => $provider,
            'success' => $result['success'],
            'timestamp' => now()
        ];

        Log::info('SMS attempt', $logData);

        // Update statistics
        $date = now()->format('Y-m-d');
        $month = now()->format('Y-m');
        
        if ($result['success']) {
            Cache::increment("sms_stats_sent_{$date}");
            Cache::increment("sms_stats_sent_{$month}");
        } else {
            Cache::increment("sms_stats_failed_{$date}");
            Cache::increment("sms_stats_failed_{$month}");
        }
    }

    /**
     * Calculate success rate
     */
    protected function calculateSuccessRate(string $period): float
    {
        $sent = Cache::get("sms_stats_sent_{$period}", 0);
        $failed = Cache::get("sms_stats_failed_{$period}", 0);
        $total = $sent + $failed;
        
        return $total > 0 ? round(($sent / $total) * 100, 2) : 0;
    }

    /**
     * Get provider statistics
     */
    protected function getProviderStats(): array
    {
        // This would be implemented based on your specific needs
        return [];
    }

    /**
     * Get template statistics
     */
    protected function getTemplateStats(): array
    {
        // This would be implemented based on your specific needs
        return [];
    }
}