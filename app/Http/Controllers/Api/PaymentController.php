<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initiate payment
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id'
        ]);

        $user = Auth::user();
        $subscription = Subscription::findOrFail($request->subscription_id);

        if ($subscription->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if ($subscription->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is not pending payment'
            ], 400);
        }

        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount,
            'currency' => 'IRR',
            'payment_method' => 'zarinpal',
            'status' => 'pending',
            'transaction_id' => $this->generateTransactionId(),
            'description' => 'پرداخت اشتراک ' . $subscription->type
        ]);

        // Initiate payment with ZarinPal
        $result = $this->paymentService->initiateZarinPalPayment($payment);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'درخواست پرداخت با موفقیت ایجاد شد',
                'data' => [
                    'payment' => $payment->fresh(),
                    'payment_url' => $result['payment_url'],
                    'authority' => $result['authority']
                ]
            ]);
        } else {
            $payment->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
    }

    /**
     * Verify payment
     */
    public function verify(Request $request)
    {
        $request->validate([
            'authority' => 'required|string',
            'status' => 'required|string'
        ]);

        $payment = Payment::where('transaction_id', $request->authority)
            ->where('payment_method', 'zarinpal')
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'پرداخت یافت نشد'
            ], 404);
        }

        if ($request->status !== 'OK') {
            $payment->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => 'پرداخت توسط کاربر لغو شد'
            ], 400);
        }

        // Verify payment with ZarinPal
        $verificationResult = $this->paymentService->verifyZarinPalPayment(
            $request->authority,
            $payment->amount
        );

        if ($verificationResult['success']) {
            // Update payment status
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_response' => json_encode($verificationResult)
            ]);

            // Activate subscription
            if ($payment->subscription) {
                $payment->subscription->update([
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => now()->addDays($this->getSubscriptionDays($payment->subscription->type))
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'پرداخت با موفقیت انجام شد',
                'data' => [
                    'payment' => $payment->fresh(),
                    'subscription' => $payment->subscription->fresh()
                ]
            ]);
        } else {
            $payment->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => $verificationResult['message']
            ], 400);
        }
    }

    /**
     * Get subscription days based on type
     */
    private function getSubscriptionDays(string $type): int
    {
        $days = [
            '1month' => 30,
            '3months' => 90,
            '6months' => 180,
            '1year' => 365,
        ];

        return $days[$type] ?? 30;
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId(): string
    {
        return 'PAY_' . time() . '_' . rand(1000, 9999);
    }

    /**
     * Get payment history
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        
        $payments = $user->payments()
            ->with('subscription')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total()
                ]
            ]
        ]);
    }

    /**
     * Generate payment URL
     */
    private function generatePaymentUrl(Payment $payment, string $method)
    {
        // This would integrate with actual payment gateways
        // For now, return a placeholder URL
        return route('payment.gateway', [
            'payment' => $payment->id,
            'method' => $method
        ]);
    }

    /**
     * Verify payment with gateway
     */
    private function verifyWithGateway(Payment $payment, string $method)
    {
        // This would integrate with actual payment gateways
        // For now, simulate successful verification
        return [
            'success' => true,
            'transaction_id' => 'GW_' . time() . '_' . rand(1000, 9999)
        ];
    }
}
