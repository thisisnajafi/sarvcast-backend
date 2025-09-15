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
            'gateway' => 'zarinpal',
            'status' => 'pending',
            'transaction_id' => $this->generateTransactionId(),
            'description' => 'پرداخت اشتراک ' . $subscription->plan_id
        ]);

        // Initiate payment with ZarinPal
        $result = $this->paymentService->initiateZarinPalPayment($payment);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'payment' => $payment->fresh(),
                    'payment_url' => $result['payment_url']
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
            'transaction_id' => 'required|string'
        ]);

        $payment = Payment::where('transaction_id', $request->transaction_id)
            ->where('gateway', 'zarinpal')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        // Verify payment with gateway
        $verificationResult = $this->verifyWithGateway($payment, $request->payment_method);

        if ($verificationResult['success']) {
            // Update payment status
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_transaction_id' => $verificationResult['gateway_transaction_id']
            ]);

            // Activate subscription
            $payment->subscription->update([
                'status' => 'active',
                'start_date' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'payment' => $payment->fresh(),
                    'subscription' => $payment->subscription->fresh()
                ]
            ]);
        } else {
            $payment->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'data' => [
                    'payment' => $payment->fresh()
                ]
            ], 400);
        }
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
     * Generate transaction ID
     */
    private function generateTransactionId()
    {
        return 'TXN_' . time() . '_' . rand(1000, 9999);
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
            'gateway_transaction_id' => 'GW_' . time() . '_' . rand(1000, 9999)
        ];
    }
}
