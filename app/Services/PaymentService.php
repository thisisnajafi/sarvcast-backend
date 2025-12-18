<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Events\SalesNotificationEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $zarinpalMerchantId;
    protected $callbackUrl;
    protected $sandboxMode;

    public function __construct()
    {
        // Get Zarinpal configuration from config
        $this->zarinpalMerchantId = config('services.zarinpal.merchant_id', '77751ff3-c1cc-411b-869d-2ac7d7b02f88');
        $this->callbackUrl = config('services.zarinpal.callback_url', 'https://my.sarvcast.ir');
        $this->sandboxMode = config('services.zarinpal.sandbox', false);
    }

    /**
     * Initiate payment with ZarinPal
     */
    public function initiateZarinPalPayment(Payment $payment, string $description = null): array
    {
        try {
            // Determine API URL based on sandbox mode
            $apiUrl = $this->sandboxMode
                ? 'https://sandbox.zarinpal.com/pg/v4/payment/request.json'
                : 'https://api.zarinpal.com/pg/v4/payment/request.json';

            $data = [
                'merchant_id' => $this->zarinpalMerchantId,
                'amount' => (int) $payment->amount, // Convert to integer as required by Zarinpal
                'description' => $description ?? 'پرداخت اشتراک سروکست',
                'callback_url' => $this->callbackUrl . '/payment/zarinpal/callback',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'subscription_id' => $payment->subscription_id,
                ]
            ];

            Log::info('Initiating ZarinPal payment', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'merchant_id' => $this->zarinpalMerchantId,
                'sandbox_mode' => $this->sandboxMode,
                'api_url' => $apiUrl,
                'callback_url' => $this->callbackUrl . '/payment/zarinpal/callback',
                'description' => $payment->description ?? 'پرداخت اشتراک سروکست'
            ]);

            // Log the exact request data being sent
            Log::debug('ZarinPal request data', [
                'payment_id' => $payment->id,
                'request_data' => $data,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $response = Http::post($apiUrl, $data);

            // Log response details for debugging
            Log::debug('ZarinPal response received', [
                'payment_id' => $payment->id,
                'status_code' => $response->status(),
                'successful' => $response->successful(),
                'response_body' => $response->body(),
                'response_headers' => $response->headers()
            ]);

            if ($response->successful()) {
                $result = $response->json();

                if ($result['data']['code'] == 100) {
                    $payment->update([
                        'transaction_id' => $result['data']['authority'],
                        'payment_method' => 'zarinpal',
                        'status' => 'pending'
                    ]);

                    $paymentUrl = $this->sandboxMode
                        ? 'https://sandbox.zarinpal.com/pg/StartPay/' . $result['data']['authority']
                        : 'https://www.zarinpal.com/pg/StartPay/' . $result['data']['authority'];

                    Log::info('ZarinPal payment initiated successfully', [
                        'payment_id' => $payment->id,
                        'authority' => $result['data']['authority'],
                        'payment_url' => $paymentUrl
                    ]);

                    return [
                        'success' => true,
                        'payment_url' => $paymentUrl,
                        'authority' => $result['data']['authority']
                    ];
                } else {
                    $errorCode = $result['data']['code'] ?? 'unknown';
                    $errorMessage = $result['errors']['message'] ?? 'خطای نامشخص';

                    // Map common ZarinPal error codes to Persian messages
                    $errorMessages = [
                        -1 => 'اطلاعات ارسال شده ناقص است',
                        -2 => 'IP یا مرچنت کد پذیرنده صحیح نیست',
                        -3 => 'با توجه به محدودیت‌های شاپرک، امکان پردازش وجود ندارد',
                        -4 => 'سطح تایید پذیرنده پایین‌تر از سطح نقره‌ای است',
                        -11 => 'درخواست مورد نظر یافت نشد',
                        -12 => 'امکان ویرایش درخواست میسر نیست',
                        -21 => 'هیچ نوع عملیات مالی برای این تراکنش یافت نشد',
                        -22 => 'تراکنش ناموفق است',
                        -33 => 'رقم تراکنش با رقم پرداخت شده مطابقت ندارد',
                        -34 => 'سقف تقسیم تراکنش از لحاظ تعداد یا رقم عبور نموده است',
                        -40 => 'اجازه دسترسی به متد مورد نظر وجود ندارد',
                        -41 => 'اطلاعات ارسال شده مربوط به AdditionalData غیرمعتبر است',
                        -42 => 'مدت زمان معتبر طول عمر شناسه پرداخت باید بین 30 دقیقه تا 45 روز باشد',
                        -54 => 'درخواست مورد نظر آرشیو شده است',
                        100 => 'عملیات با موفقیت انجام شد',
                        101 => 'عملیات پرداخت قبلاً انجام شده است'
                    ];

                    $persianMessage = $errorMessages[$errorCode] ?? $errorMessage;

                    Log::error('ZarinPal payment initiation failed', [
                        'payment_id' => $payment->id,
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage,
                        'persian_message' => $persianMessage,
                        'request_data' => $data,
                        'response_data' => $result
                    ]);

                    return [
                        'success' => false,
                        'message' => 'خطا در ایجاد درخواست پرداخت: ' . $persianMessage,
                        'debug_info' => [
                            'error_code' => $errorCode,
                            'original_message' => $errorMessage,
                            'sandbox_mode' => $this->sandboxMode
                        ]
                    ];
                }
            } else {
                $errorDetails = [
                    'payment_id' => $payment->id,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'request_data' => $data,
                    'api_url' => $apiUrl,
                    'sandbox_mode' => $this->sandboxMode,
                    'merchant_id' => $this->zarinpalMerchantId
                ];

                Log::error('ZarinPal API request failed', $errorDetails);

                // Try to parse error response
                $responseBody = $response->body();
                $errorMessage = 'خطا در ارتباط با درگاه پرداخت';

                try {
                    $errorData = json_decode($responseBody, true);
                    if (isset($errorData['errors']['message'])) {
                        $errorMessage = 'خطا در درگاه پرداخت: ' . $errorData['errors']['message'];
                    } elseif (isset($errorData['message'])) {
                        $errorMessage = 'خطا در درگاه پرداخت: ' . $errorData['message'];
                    }
                } catch (\Exception $parseError) {
                    Log::warning('Failed to parse ZarinPal error response', [
                        'response_body' => $responseBody,
                        'parse_error' => $parseError->getMessage()
                    ]);
                }

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'debug_info' => [
                        'status_code' => $response->status(),
                        'api_url' => $apiUrl,
                        'sandbox_mode' => $this->sandboxMode
                    ]
                ];
            }
        } catch (\Exception $e) {
            $errorDetails = [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data ?? null,
                'api_url' => $apiUrl ?? null,
                'sandbox_mode' => $this->sandboxMode,
                'merchant_id' => $this->zarinpalMerchantId
            ];

            Log::error('ZarinPal payment initiation failed with exception', $errorDetails);

            return [
                'success' => false,
                'message' => 'خطا در ارتباط با درگاه پرداخت: ' . $e->getMessage(),
                'debug_info' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'sandbox_mode' => $this->sandboxMode
                ]
            ];
        }
    }


    /**
     * Verify ZarinPal payment
     */
    public function verifyZarinPalPayment(string $authority, int $amount): array
    {
        try {
            // Determine API URL based on sandbox mode
            $apiUrl = $this->sandboxMode
                ? 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json'
                : 'https://api.zarinpal.com/pg/v4/payment/verify.json';

            $data = [
                'merchant_id' => $this->zarinpalMerchantId,
                'amount' => $amount,
                'authority' => $authority
            ];

            Log::info('Verifying ZarinPal payment', [
                'authority' => $authority,
                'amount' => $amount,
                'merchant_id' => $this->zarinpalMerchantId,
                'sandbox_mode' => $this->sandboxMode,
                'api_url' => $apiUrl
            ]);

            $response = Http::post($apiUrl, $data);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('ZarinPal verification response', [
                    'authority' => $authority,
                    'response_code' => $result['data']['code'] ?? 'No code',
                    'response_data' => $result['data'] ?? 'No data',
                    'response_errors' => $result['errors'] ?? 'No errors'
                ]);

                if ($result['data']['code'] == 100) {
                    Log::info('ZarinPal payment verified successfully', [
                        'authority' => $authority,
                        'ref_id' => $result['data']['ref_id'],
                        'amount' => $amount, // Use the amount we sent for verification
                        'card_pan' => $result['data']['card_pan'] ?? null,
                        'fee' => $result['data']['fee'] ?? null
                    ]);

                    return [
                        'success' => true,
                        'ref_id' => $result['data']['ref_id'],
                        'amount' => $amount, // Use the amount we sent for verification
                        'card_pan' => $result['data']['card_pan'] ?? null,
                        'fee' => $result['data']['fee'] ?? null
                    ];
                } else {
                    Log::error('ZarinPal payment verification failed', [
                        'authority' => $authority,
                        'error_code' => $result['data']['code'],
                        'error_message' => $result['errors']['message'] ?? 'خطای نامشخص',
                        'full_response' => $result
                    ]);

                    return [
                        'success' => false,
                        'message' => 'پرداخت ناموفق: ' . ($result['errors']['message'] ?? 'خطای نامشخص')
                    ];
                }
            } else {
                Log::error('ZarinPal verification API request failed', [
                    'authority' => $authority,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'request_data' => $data
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در تایید پرداخت'
                ];
            }
        } catch (\Exception $e) {
            Log::error('ZarinPal payment verification failed', [
                'authority' => $authority,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در سیستم پرداخت'
            ];
        }
    }


    /**
     * Process payment callback
     */
    public function processCallback(array $data): array
    {
        $authority = $data['Authority'] ?? null;
        $status = $data['Status'] ?? null;

        Log::info('Payment callback received', [
            'authority' => $authority,
            'status' => $status,
            'all_data' => $data
        ]);

        if ($status === 'OK' && $authority) {
            $payment = Payment::where('transaction_id', $authority)
                ->where('payment_method', 'zarinpal')
                ->where('status', 'pending')
                ->first();

            Log::info('Payment lookup result', [
                'authority' => $authority,
                'payment_found' => $payment ? true : false,
                'payment_id' => $payment?->id,
                'payment_amount' => $payment?->amount,
                'payment_status' => $payment?->status
            ]);

            if ($payment) {
                $verification = $this->verifyZarinPalPayment($authority, (int) $payment->amount);

                Log::info('Payment verification result', [
                    'authority' => $authority,
                    'verification_success' => $verification['success'],
                    'verification_message' => $verification['message'] ?? 'No message',
                    'payment_id' => $payment->id
                ]);

                if ($verification['success']) {
                    // Merge existing metadata (source, return_scheme, episode_id, etc.)
                    $existingMetadata = $payment->payment_metadata ?? [];
                    $newMetadata = array_merge($existingMetadata, [
                        'ref_id' => $verification['ref_id'] ?? null,
                        'card_pan' => $verification['card_pan'] ?? null,
                        'verification_time' => now()->toISOString()
                    ]);

                    $payment->updateStatus('completed', [
                        'gateway_response' => json_encode($verification),
                        'gateway_fee' => $verification['fee'] ?? 0,
                        'net_amount' => $payment->amount - ($verification['fee'] ?? 0),
                        'payment_metadata' => $newMetadata,
                    ]);

                    Log::info('Payment marked as completed', [
                        'payment_id' => $payment->id,
                        'authority' => $authority,
                        'amount' => $payment->amount
                    ]);

                    // Activate subscription
                    if ($payment->subscription) {
                        $subscription = $payment->subscription;

                        Log::info('Starting subscription activation', [
                            'subscription_id' => $subscription->id,
                            'current_status' => $subscription->status,
                            'current_start_date' => $subscription->start_date,
                            'current_end_date' => $subscription->end_date,
                            'subscription_type' => $subscription->type,
                            'user_id' => $subscription->user_id
                        ]);

                        // Calculate end date based on subscription type
                        $startDate = now();
                        $endDate = $this->calculateSubscriptionEndDate($subscription->type, $startDate);

                        Log::info('Calculated subscription dates', [
                            'subscription_id' => $subscription->id,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'duration_days' => $startDate->diffInDays($endDate)
                        ]);

                        // Update subscription with transaction to ensure data integrity
                        \DB::transaction(function() use ($subscription, $startDate, $endDate) {
                            $subscription->update([
                                'status' => 'active',
                                'start_date' => $startDate,
                                'end_date' => $endDate
                            ]);

                            Log::info('Subscription updated in database', [
                                'subscription_id' => $subscription->id,
                                'new_status' => $subscription->fresh()->status,
                                'new_start_date' => $subscription->fresh()->start_date,
                                'new_end_date' => $subscription->fresh()->end_date
                            ]);
                        });

                        // Verify the subscription is now active
                        $updatedSubscription = $subscription->fresh();
                        $isActive = $updatedSubscription->status === 'active' && $updatedSubscription->end_date > now();

                        Log::info('Subscription activation verification', [
                            'subscription_id' => $updatedSubscription->id,
                            'status' => $updatedSubscription->status,
                            'end_date' => $updatedSubscription->end_date,
                            'current_time' => now(),
                            'is_active' => $isActive,
                            'days_remaining' => $isActive ? max(0, now()->diffInDays($updatedSubscription->end_date, false)) : 0
                        ]);

                        if (!$isActive) {
                            Log::error('Subscription activation failed - not properly activated', [
                                'subscription_id' => $updatedSubscription->id,
                                'status' => $updatedSubscription->status,
                                'end_date' => $updatedSubscription->end_date,
                                'current_time' => now()
                            ]);
                        }
                    } else {
                        Log::error('No subscription found for payment', [
                            'payment_id' => $payment->id,
                            'subscription_id' => $payment->subscription_id
                        ]);
                    }

                    // Fire sales notification event
                    event(new SalesNotificationEvent($payment, $payment->subscription));

                    Log::info('Payment completed successfully', [
                        'payment_id' => $payment->id,
                        'authority' => $authority,
                        'subscription_id' => $payment->subscription?->id
                    ]);

                    return [
                        'success' => true,
                        'payment' => $payment,
                        'message' => 'پرداخت با موفقیت انجام شد'
                    ];
                } else {
                    $payment->updateStatus('failed', [
                        'gateway_response' => json_encode([
                            'verification_failed' => true,
                            'error_message' => $verification['message'] ?? 'Unknown error',
                            'authority' => $authority,
                            'failed_at' => now()->toISOString()
                        ])
                    ]);

                    Log::error('Payment verification failed', [
                        'payment_id' => $payment->id,
                        'authority' => $authority,
                        'verification_message' => $verification['message'] ?? 'No message'
                    ]);

                    return [
                        'success' => false,
                        'message' => $verification['message']
                    ];
                }
            } else {
                Log::warning('Payment not found for callback', [
                    'authority' => $authority,
                    'status' => $status
                ]);
            }
        } else {
            Log::warning('Invalid callback data', [
                'authority' => $authority,
                'status' => $status,
                'expected_status' => 'OK'
            ]);
        }

        return [
            'success' => false,
            'message' => 'پرداخت یافت نشد یا نامعتبر است'
        ];
    }

    /**
     * Calculate subscription end date based on type
     */
    private function calculateSubscriptionEndDate(string $subscriptionType, $startDate): \Carbon\Carbon
    {
        $durationDays = $this->getSubscriptionDurationDays($subscriptionType);
        return $startDate->copy()->addDays($durationDays);
    }

    /**
     * Get subscription duration in days based on type
     */
    private function getSubscriptionDurationDays(string $subscriptionType): int
    {
        $durations = [
            '1month' => 30,
            '3months' => 90,
            '6months' => 180,
            '1year' => 365
        ];

        return $durations[$subscriptionType] ?? 30; // Default to 30 days if type not found
    }

    /**
     * Manually activate a subscription (for debugging/fixing)
     */
    public function manuallyActivateSubscription(int $subscriptionId): array
    {
        try {
            $subscription = \App\Models\Subscription::find($subscriptionId);

            if (!$subscription) {
                return [
                    'success' => false,
                    'message' => 'اشتراک یافت نشد'
                ];
            }

            Log::info('Manual subscription activation started', [
                'subscription_id' => $subscriptionId,
                'current_status' => $subscription->status,
                'current_start_date' => $subscription->start_date,
                'current_end_date' => $subscription->end_date,
                'user_id' => $subscription->user_id
            ]);

            // Calculate dates
            $startDate = now();
            $endDate = $this->calculateSubscriptionEndDate($subscription->type, $startDate);

            // Update subscription
            \DB::transaction(function() use ($subscription, $startDate, $endDate) {
                $subscription->update([
                    'status' => 'active',
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);
            });

            // Verify activation
            $updatedSubscription = $subscription->fresh();
            $isActive = $updatedSubscription->status === 'active' && $updatedSubscription->end_date > now();

            Log::info('Manual subscription activation completed', [
                'subscription_id' => $subscriptionId,
                'new_status' => $updatedSubscription->status,
                'new_start_date' => $updatedSubscription->start_date,
                'new_end_date' => $updatedSubscription->end_date,
                'is_active' => $isActive,
                'days_remaining' => $isActive ? max(0, now()->diffInDays($updatedSubscription->end_date, false)) : 0
            ]);

            return [
                'success' => true,
                'message' => 'اشتراک با موفقیت فعال شد',
                'subscription' => $updatedSubscription,
                'is_active' => $isActive
            ];

        } catch (\Exception $e) {
            Log::error('Manual subscription activation failed', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در فعال‌سازی اشتراک: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
            'created_at' => $payment->created_at,
            'paid_at' => $payment->paid_at,
            'transaction_id' => $payment->transaction_id,
            'gateway_response' => $payment->gateway_response
        ];
    }

    /**
     * Refund payment
     */
    public function refundPayment(Payment $payment): array
    {
        // This would integrate with gateway refund APIs
        // For now, just mark as refunded
        $payment->update(['status' => 'refunded']);

        return [
            'success' => true,
            'message' => 'پرداخت بازگردانده شد'
        ];
    }

    /**
     * Check ZarinPal configuration and connectivity
     */
    public function checkZarinPalConfiguration(): array
    {
        try {
            $config = [
                'merchant_id' => $this->zarinpalMerchantId,
                'sandbox_mode' => $this->sandboxMode,
                'callback_url' => $this->callbackUrl . '/payment/zarinpal/callback',
                'api_url' => $this->sandboxMode
                    ? 'https://sandbox.zarinpal.com/pg/v4/payment/request.json'
                    : 'https://api.zarinpal.com/pg/v4/payment/request.json'
            ];

            Log::info('ZarinPal configuration check', $config);

            // Test basic connectivity
            $testUrl = $this->sandboxMode
                ? 'https://sandbox.zarinpal.com/pg/v4/payment/request.json'
                : 'https://api.zarinpal.com/pg/v4/payment/request.json';

            $testResponse = Http::timeout(10)->get($testUrl);

            return [
                'success' => true,
                'config' => $config,
                'connectivity_test' => [
                    'url' => $testUrl,
                    'status_code' => $testResponse->status(),
                    'reachable' => $testResponse->status() !== 0
                ]
            ];

        } catch (\Exception $e) {
            Log::error('ZarinPal configuration check failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'config' => [
                    'merchant_id' => $this->zarinpalMerchantId,
                    'sandbox_mode' => $this->sandboxMode,
                    'callback_url' => $this->callbackUrl . '/payment/zarinpal/callback'
                ]
            ];
        }
    }
}
