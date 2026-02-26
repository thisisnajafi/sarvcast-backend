<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\FlavorHelper;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\CafeBazaarService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * CafeBazaar Subscription Controller
 * 
 * Handles subscription receipt verification and status updates for CafeBazaar flavor only.
 * This controller is flavor-aware and will only process CafeBazaar purchases.
 */
class CafeBazaarSubscriptionController extends Controller
{
    protected $cafeBazaarService;
    protected $subscriptionService;

    public function __construct(
        CafeBazaarService $cafeBazaarService,
        SubscriptionService $subscriptionService
    ) {
        $this->cafeBazaarService = $cafeBazaarService;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Verify CafeBazaar subscription receipt
     * 
     * POST /api/v1/subscriptions/cafebazaar/verify
     * 
     * This endpoint is flavor-aware and only processes CafeBazaar purchases.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifySubscription(Request $request)
    {
        // Flavor-aware check: Only process CafeBazaar flavor requests
        if (!FlavorHelper::isCafeBazaar($request)) {
            Log::warning('CafeBazaar subscription verification rejected: Not CafeBazaar flavor', [
                'detected_flavor' => FlavorHelper::getFlavor($request),
                'billing_platform' => $request->input('billing_platform'),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'این endpoint فقط برای درخواست‌های کافه‌بازار قابل استفاده است.'
            ], 403);
        }
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'purchase_token' => 'required|string|max:500',
            'product_id' => 'required|string|max:100',
            'order_id' => 'nullable|string|max:100',
            'billing_platform' => 'nullable|string|in:cafebazaar',
        ], [
            'purchase_token.required' => 'توکن خرید الزامی است',
            'purchase_token.string' => 'توکن خرید باید رشته باشد',
            'purchase_token.max' => 'توکن خرید نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'product_id.required' => 'شناسه محصول الزامی است',
            'product_id.string' => 'شناسه محصول باید رشته باشد',
            'product_id.max' => 'شناسه محصول نمی‌تواند بیشتر از 100 کاراکتر باشد',
            'billing_platform.in' => 'پلتفرم پرداخت باید کافه‌بازار باشد',
        ]);

        if ($validator->fails()) {
            Log::warning('CafeBazaar subscription verification validation failed', [
                'errors' => $validator->errors()->toArray(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'داده‌های ورودی نامعتبر است',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Flavor-aware check: Ensure this is a CafeBazaar purchase
        $billingPlatform = $request->input('billing_platform', 'cafebazaar');
        if ($billingPlatform !== 'cafebazaar') {
            Log::warning('CafeBazaar subscription verification called with wrong platform', [
                'user_id' => $user->id,
                'billing_platform' => $billingPlatform,
                'product_id' => $request->product_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'این endpoint فقط برای خریدهای کافه‌بازار است',
            ], 400);
        }

        $purchaseToken = $request->input('purchase_token');
        $productId = $request->input('product_id');
        $orderId = $request->input('order_id');

        Log::info('CafeBazaar subscription verification started', [
            'user_id' => $user->id,
            'product_id' => $productId,
            'order_id' => $orderId,
            'purchase_token_preview' => substr($purchaseToken, 0, 20) . '...',
        ]);

        try {
            $result = $this->cafeBazaarService->verifyAndFulfillSubscription($user, $purchaseToken, $productId, $orderId);

            if (!$result['success']) {
                $statusCode = 400;
                if (isset($result['error_code']) && in_array($result['error_code'], [CafeBazaarService::ERROR_API_KEY_NOT_CONFIGURED, CafeBazaarService::ERROR_INVALID_CREDENTIALS], true)) {
                    $statusCode = 503;
                }
                Log::error('CafeBazaar subscription verification failed', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'message' => $result['message'] ?? null,
                    'error_code' => $result['error_code'] ?? null,
                ]);
                $message = $result['message'] ?? 'تایید خرید ناموفق بود';
                // Never send HTML to the client (e.g. if an upstream 404 was returned as body)
                if (is_string($message) && (str_contains($message, '<') || str_contains($message, 'DOCTYPE'))) {
                    $message = $result['error_code'] === CafeBazaarService::ERROR_INVALID_CREDENTIALS
                        ? 'توکن دسترسی کافه‌بازار نامعتبر است. لطفاً از پیشخوان توسعه‌دهنده توکن جدید بگیرید.'
                        : 'تایید خرید ناموفق بود. لطفاً بعداً تلاش کنید.';
                }
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => $result['error_code'] ?? null,
                ], $statusCode);
            }

            $message = !empty($result['is_duplicate'])
                ? 'این خرید قبلاً ثبت و تایید شده است'
                : 'خرید با موفقیت تایید و اشتراک فعال شد';

            $paymentArray = $result['payment']->toArray();
            $subscriptionArray = $result['subscription']->toArray();
            $this->normalizeNumericFieldsForApi($paymentArray, $subscriptionArray);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'payment' => $paymentArray,
                    'subscription' => $subscriptionArray,
                    'is_duplicate' => $result['is_duplicate'] ?? false,
                    'acknowledged' => $result['acknowledged'] ?? false,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation exception in CafeBazaar subscription verification', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('CafeBazaar subscription verification failed', [
                'user_id' => $user->id,
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پردازش خرید: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check subscription status
     * 
     * GET /api/v1/subscriptions/cafebazaar/status
     * 
     * Returns the current subscription status for the authenticated user.
     * Only returns subscriptions from CafeBazaar platform.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscriptionStatus(Request $request)
    {
        $user = Auth::user();

        try {
            $subscription = Subscription::where('user_id', $user->id)
                ->where('billing_platform', 'cafebazaar')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => true,
                    'message' => 'اشتراک کافه‌بازار یافت نشد',
                    'data' => [
                        'has_subscription' => false,
                        'subscription' => null,
                    ],
                ], 200);
            }

            $isActive = $subscription->status === 'active' && $subscription->end_date > now();
            $subscriptionArray = $subscription->toArray();
            if (array_key_exists('price', $subscriptionArray) && $subscriptionArray['price'] !== null) {
                $subscriptionArray['price'] = (float) $subscriptionArray['price'];
            }
            $billingPlatform = $subscriptionArray['billing_platform'] ?? null;
            $subscriptionArray['plan_source'] = $billingPlatform === 'cafebazaar' ? 'cafebazaar' : 'website';
            $subscriptionArray['source_label'] = $billingPlatform === 'cafebazaar' ? 'کافه‌بازار' : 'وب‌سایت';

            return response()->json([
                'success' => true,
                'data' => [
                    'has_subscription' => true,
                    'subscription' => $subscriptionArray,
                    'is_active' => $isActive,
                    'days_remaining' => $isActive ? max(0, now()->diffInDays($subscription->end_date, false)) : 0,
                    'status' => $subscription->status,
                    'end_date' => $subscription->end_date->toISOString(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get CafeBazaar subscription status', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت وضعیت اشتراک',
            ], 500);
        }
    }

    /**
     * Restore CafeBazaar purchases
     * 
     * POST /api/v1/subscriptions/cafebazaar/restore
     * 
     * This endpoint can be used to restore previous purchases.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function restorePurchases(Request $request)
    {
        // Flavor-aware check: Only process CafeBazaar flavor requests
        if (!FlavorHelper::isCafeBazaar($request)) {
            Log::warning('CafeBazaar restore purchases rejected: Not CafeBazaar flavor', [
                'detected_flavor' => FlavorHelper::getFlavor($request),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'این endpoint فقط برای درخواست‌های کافه‌بازار قابل استفاده است.'
            ], 403);
        }
        
        $user = Auth::user();

        try {
            // Get all CafeBazaar payments for this user
            $payments = Payment::where('user_id', $user->id)
                ->where('billing_platform', 'cafebazaar')
                ->where('status', 'completed')
                ->with('subscription')
                ->orderBy('created_at', 'desc')
                ->get();

            $restoredSubscriptions = [];
            
            foreach ($payments as $payment) {
                if ($payment->subscription) {
                    $restoredSubscriptions[] = [
                        'payment_id' => $payment->id,
                        'subscription_id' => $payment->subscription->id,
                        'product_id' => $payment->product_id,
                        'purchase_date' => $payment->created_at->toISOString(),
                        'subscription_status' => $payment->subscription->status,
                        'subscription_end_date' => $payment->subscription->end_date->toISOString(),
                    ];
                }
            }

            Log::info('CafeBazaar purchases restored', [
                'user_id' => $user->id,
                'restored_count' => count($restoredSubscriptions),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'خریدهای قبلی بازیابی شد',
                'data' => [
                    'restored_count' => count($restoredSubscriptions),
                    'purchases' => $restoredSubscriptions,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to restore CafeBazaar purchases', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بازیابی خریدها',
            ], 500);
        }
    }

    /**
     * Ensure payment and subscription arrays have numeric fields as numbers (not strings)
     * and include plan source (bazaar vs website) for clients.
     */
    private function normalizeNumericFieldsForApi(array &$payment, array &$subscription): void
    {
        $decimalKeys = ['amount', 'gateway_fee', 'net_amount', 'exchange_rate', 'refund_amount'];
        foreach ($decimalKeys as $key) {
            if (array_key_exists($key, $payment) && $payment[$key] !== null) {
                $payment[$key] = (float) $payment[$key];
            }
        }
        if (array_key_exists('price', $subscription) && $subscription['price'] !== null) {
            $subscription['price'] = (float) $subscription['price'];
        }
        $billingPlatform = $subscription['billing_platform'] ?? null;
        $subscription['plan_source'] = $billingPlatform === 'cafebazaar' ? 'cafebazaar' : 'website';
        $subscription['source_label'] = $billingPlatform === 'cafebazaar' ? 'کافه‌بازار' : 'وب‌سایت';
    }
}

