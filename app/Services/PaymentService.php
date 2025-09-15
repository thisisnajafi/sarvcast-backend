<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $zarinpalMerchantId;
    protected $callbackUrl;

    public function __construct()
    {
        $this->zarinpalMerchantId = config('payment.zarinpal.merchant_id');
        $this->callbackUrl = config('payment.callback_url');
    }

    /**
     * Initiate payment with ZarinPal
     */
    public function initiateZarinPalPayment(Payment $payment): array
    {
        try {
            $data = [
                'merchant_id' => $this->zarinpalMerchantId,
                'amount' => $payment->amount,
                'description' => $payment->description ?? 'پرداخت اشتراک سروکست',
                'callback_url' => $this->callbackUrl . '/zarinpal/callback',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'subscription_id' => $payment->subscription_id,
                ]
            ];

            $response = Http::post('https://api.zarinpal.com/pg/v4/payment/request.json', $data);

            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['data']['code'] == 100) {
                    $payment->update([
                        'gateway_transaction_id' => $result['data']['authority'],
                        'gateway' => 'zarinpal',
                        'status' => 'pending'
                    ]);

                    return [
                        'success' => true,
                        'payment_url' => 'https://www.zarinpal.com/pg/StartPay/' . $result['data']['authority'],
                        'authority' => $result['data']['authority']
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'خطا در ایجاد درخواست پرداخت: ' . $result['errors']['message'] ?? 'خطای نامشخص'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در ارتباط با درگاه پرداخت'
                ];
            }
        } catch (\Exception $e) {
            Log::error('ZarinPal payment initiation failed: ' . $e->getMessage());
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
            $data = [
                'merchant_id' => $this->zarinpalMerchantId,
                'amount' => $amount,
                'authority' => $authority
            ];

            $response = Http::post('https://api.zarinpal.com/pg/v4/payment/verify.json', $data);

            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['data']['code'] == 100) {
                    return [
                        'success' => true,
                        'ref_id' => $result['data']['ref_id'],
                        'amount' => $result['data']['amount']
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'پرداخت ناموفق: ' . $result['errors']['message'] ?? 'خطای نامشخص'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در تایید پرداخت'
                ];
            }
        } catch (\Exception $e) {
            Log::error('ZarinPal payment verification failed: ' . $e->getMessage());
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
            $payment = Payment::where('gateway_transaction_id', $authority)
                ->where('gateway', 'zarinpal')
                ->where('status', 'pending')
                ->first();
            
            if ($payment) {
                $verification = $this->verifyZarinPalPayment($authority, $payment->amount);
                
                if ($verification['success']) {
                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'gateway_ref_id' => $verification['ref_id']
                    ]);
                    
                    // Activate subscription
                    if ($payment->subscription) {
                        $payment->subscription->update([
                            'status' => 'active',
                            'start_date' => now()
                        ]);
                    }
                    
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
            'gateway' => $payment->gateway,
            'created_at' => $payment->created_at,
            'paid_at' => $payment->paid_at,
            'gateway_transaction_id' => $payment->gateway_transaction_id,
            'gateway_ref_id' => $payment->gateway_ref_id
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
