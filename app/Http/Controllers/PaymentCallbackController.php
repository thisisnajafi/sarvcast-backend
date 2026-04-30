<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
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
     * Append query string to a URL (handles existing ?).
     *
     * @param  array<string, scalar|null>  $params
     */
    private function appendQueryParams(string $url, array $params): string
    {
        $filtered = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        if ($filtered === []) {
            return $url;
        }
        $sep = str_contains($url, '?') ? '&' : '?';

        return $url.$sep.http_build_query($filtered);
    }

    /**
     * Handle ZarinPal payment callback
     */
    public function zarinpalCallback(Request $request)
    {
        $authority = $request->input('Authority') ?? $request->query('Authority');

        $paymentPrefetch = $authority
            ? Payment::with('subscription')->where('transaction_id', $authority)->where('payment_method', 'zarinpal')->first()
            : null;

        $prefetchMeta = $paymentPrefetch?->payment_metadata ?? [];

        $result = $this->paymentService->processCallback($request->all());

        $payment = $result['payment'] ?? $paymentPrefetch;
        $metadata = $payment?->payment_metadata ?? $prefetchMeta;
        $source = $metadata['source'] ?? null;
        $returnScheme = $metadata['return_scheme'] ?? config('services.sarvcast_app.return_scheme', 'sarvcast');
        $episodeId = $metadata['episode_id'] ?? null;

        $webBase = rtrim((string) config('services.sarvcast_web.app_url', 'https://app.sarvcast.ir'), '/');

        // Next.js web app: redirect to configured frontend (subscription activated / failed)
        if ($source === 'web') {
            $returnUrl = $metadata['return_url'] ?? $webBase.'/home';
            $failureUrl = $metadata['failure_url'] ?? $webBase.'/subscription/failure';

            if ($result['success'] && $payment) {
                $url = $this->appendQueryParams($returnUrl, [
                    'subscription' => 'activated',
                    'payment_id' => (string) $payment->id,
                ]);

                return redirect()->away($url);
            }

            $url = $this->appendQueryParams($failureUrl, array_filter([
                'subscription' => 'failed',
                'reason' => $result['message'] ?? null,
            ]));

            return redirect()->away($url);
        }

        // Non-web flows default to Flutter deep link (app).
        $subscription = $payment?->subscription;
        $timestamp = now()->toIso8601String();
        if ($result['success']) {
            $params = [
                'success' => 'true',
                'payment_id' => $payment?->id,
                'subscription_id' => $subscription?->id,
                'amount' => $payment ? (int) $payment->amount : null,
                'transaction_id' => $payment?->transaction_id,
                'timestamp' => $timestamp,
            ];

            if ($episodeId) {
                $params['episode_id'] = $episodeId;
            }

            $deepLink = $returnScheme . '://payment/success?' . http_build_query(array_filter($params, static fn ($v) => $v !== null && $v !== ''));
        } else {
            $deepLink = $returnScheme . '://payment/failure?' . http_build_query(array_filter([
                'success' => 'false',
                'payment_id' => $payment?->id,
                'subscription_id' => $subscription?->id,
                'error' => $result['message'] ?? 'پرداخت ناموفق بود',
                'timestamp' => $timestamp,
            ], static fn ($v) => $v !== null && $v !== ''));
        }

        return redirect()->away($deepLink);
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
