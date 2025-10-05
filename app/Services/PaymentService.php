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
    public function initiateZarinPalPayment(Payment $payment): array
    {
        try {
            // Determine API URL based on sandbox mode
            $apiUrl = $this->sandboxMode 
                ? 'https://sandbox.zarinpal.com/pg/v4/payment/request.json'
                : 'https://api.zarinpal.com/pg/v4/payment/request.json';

            $data = [
                'merchant_id' => $this->zarinpalMerchantId,
                'amount' => $payment->amount, // Amount should be in IRR (converted from IRT if needed)
                'description' => $payment->description ?? 'پرداخت اشتراک سروکست',
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
                'merchant_id' => $this->zarinpalMerchantId,
                'sandbox_mode' => $this->sandboxMode,
                'api_url' => $apiUrl
            ]);

            $response = Http::post($apiUrl, $data);

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
                    Log::error('ZarinPal payment initiation failed', [
                        'payment_id' => $payment->id,
                        'error_code' => $result['data']['code'],
                        'error_message' => $result['errors']['message'] ?? 'خطای نامشخص'
                    ]);

                    return [
                        'success' => false,
                        'message' => 'خطا در ایجاد درخواست پرداخت: ' . ($result['errors']['message'] ?? 'خطای نامشخص')
                    ];
                }
            } else {
                Log::error('ZarinPal API request failed', [
                    'payment_id' => $payment->id,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در ارتباط با درگاه پرداخت'
                ];
            }
        } catch (\Exception $e) {
            Log::error('ZarinPal payment initiation failed', [
                'payment_id' => $payment->id,
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
                
                if ($result['data']['code'] == 100) {
                    Log::info('ZarinPal payment verified successfully', [
                        'authority' => $authority,
                        'ref_id' => $result['data']['ref_id'],
                        'amount' => $result['data']['amount']
                    ]);

                    return [
                        'success' => true,
                        'ref_id' => $result['data']['ref_id'],
                        'amount' => $result['data']['amount']
                    ];
                } else {
                    Log::error('ZarinPal payment verification failed', [
                        'authority' => $authority,
                        'error_code' => $result['data']['code'],
                        'error_message' => $result['errors']['message'] ?? 'خطای نامشخص'
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
                    'response_body' => $response->body()
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
        
        if ($status === 'OK' && $authority) {
            $payment = Payment::where('transaction_id', $authority)
                ->where('payment_method', 'zarinpal')
                ->where('status', 'pending')
                ->first();
            
            if ($payment) {
                $verification = $this->verifyZarinPalPayment($authority, $payment->amount);
                
                if ($verification['success']) {
                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'gateway_response' => json_encode($verification),
                    ]);
                    
                    // Activate subscription
                    if ($payment->subscription) {
                        $payment->subscription->update([
                            'status' => 'active',
                            'start_date' => now()
                        ]);
                    }
                    
                    // Fire sales notification event
                    event(new SalesNotificationEvent($payment, $payment->subscription));
                    
                    return [
                        'success' => true,
                        'payment' => $payment,
                        'message' => 'پرداخت با موفقیت انجام شد'
                    ];
                } else {
                    $payment->update(['status' => 'failed']);
                    return [
                        'success' => false,
                        'message' => $verification['message']
                    ];
                }
            }
        }
        
        return [
            'success' => false,
            'message' => 'پرداخت یافت نشد یا نامعتبر است'
        ];
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
}
