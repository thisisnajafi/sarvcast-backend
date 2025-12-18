<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle ZarinPal payment callback
     */
    public function zarinpalCallback(Request $request)
    {
        $result = $this->paymentService->processCallback($request->all());

        $payment = $result['payment'] ?? null;
        $metadata = $payment?->payment_metadata ?? [];
        $source = $metadata['source'] ?? null;
        $returnScheme = $metadata['return_scheme'] ?? null;
        $episodeId = $metadata['episode_id'] ?? null;

        // If flow originated from the app, redirect back via deep link
        if ($payment && $source === 'app' && $returnScheme) {
            $subscription = $payment->subscription;
            $timestamp = now()->toIso8601String();

            if ($result['success']) {
                // Use parameter names compatible with Flutter deep link parsing
                $params = [
                    'success' => 'true',
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription?->id,
                    'amount' => (int) $payment->amount,
                    'transaction_id' => $payment->transaction_id,
                    'timestamp' => $timestamp,
                ];

                if ($episodeId) {
                    $params['episode_id'] = $episodeId;
                }

                $deepLink = $returnScheme . '://payment/success?' . http_build_query($params);
            } else {
                $deepLink = $returnScheme . '://payment/failure?' . http_build_query([
                    'success' => 'false',
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription?->id,
                    'error' => $result['message'] ?? 'پرداخت ناموفق بود',
                    'timestamp' => $timestamp,
                ]);
            }

            return redirect()->away($deepLink);
        }

        // Default web behaviour
        if ($result['success']) {
            return redirect()->route('payment.success', [
                'payment_id' => $payment?->id
            ])->with('success', $result['message']);
        }

        return redirect()->route('payment.failure')->with('error', $result['message']);
    }

    /**
     * Show payment success page
     */
    public function success(Request $request)
    {
        $paymentId = $request->get('payment_id');

        if ($paymentId) {
            $payment = \App\Models\Payment::with(['user', 'subscription'])->find($paymentId);

            if ($payment) {
                return view('payment.success', compact('payment'));
            }
        }

        return view('payment.success');
    }

    /**
     * Show payment failure page
     */
    public function failure(Request $request)
    {
        return view('payment.failure');
    }

    /**
     * Show payment retry page
     */
    public function retry(Request $request)
    {
        // This would typically redirect to the payment initiation page
        // or show a retry form. For now, we'll redirect to a generic page
        return redirect()->route('payment.failure')->with('message', 'لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.');
    }
}
