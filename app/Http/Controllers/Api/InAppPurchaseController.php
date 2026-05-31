<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\CafeBazaarService;
use App\Services\MyketService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InAppPurchaseController extends Controller
{
    protected $cafeBazaarService;
    protected $myketService;
    protected $subscriptionService;

    public function __construct(
        CafeBazaarService $cafeBazaarService,
        MyketService $myketService,
        SubscriptionService $subscriptionService
    ) {
        $this->cafeBazaarService = $cafeBazaarService;
        $this->myketService = $myketService;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Verify CafeBazaar in-app purchase
     * 
     * POST /api/v1/payments/cafebazaar/verify
     */
    public function verifyCafeBazaarPurchase(Request $request)
    {
        $request->validate([
            'purchase_token' => 'required|string',
            'product_id' => 'required|string',
            'order_id' => 'nullable|string',
        ], [
            'purchase_token.required' => 'توکن خرید الزامی است',
            'product_id.required' => 'شناسه محصول الزامی است',
        ]);

        $user = Auth::user();

        try {
            $result = $this->cafeBazaarService->verifyAndFulfillSubscription(
                $user,
                $request->purchase_token,
                $request->product_id,
                $request->order_id
            );

            if (!$result['success']) {
                $statusCode = isset($result['error_code']) && $result['error_code'] === CafeBazaarService::ERROR_API_KEY_NOT_CONFIGURED ? 503 : 400;
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'تایید خرید ناموفق بود',
                    'error_code' => $result['error_code'] ?? null,
                ], $statusCode);
            }

            $message = !empty($result['is_duplicate']) ? 'این خرید قبلاً ثبت شده است' : 'خرید با موفقیت تایید و اشتراک فعال شد';
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'payment' => $result['payment'],
                    'subscription' => $result['subscription'],
                    'is_duplicate' => $result['is_duplicate'] ?? false,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CafeBazaar purchase verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پردازش خرید: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify Myket in-app purchase
     * 
     * POST /api/v1/payments/myket/verify
     */
    public function verifyMyketPurchase(Request $request)
    {
        $request->validate([
            'purchase_token' => 'required|string',
            'product_id' => 'required|string',
            'order_id' => 'nullable|string',
        ], [
            'purchase_token.required' => 'توکن خرید الزامی است',
            'product_id.required' => 'شناسه محصول الزامی است',
        ]);

        $user = Auth::user();

        try {
            $request->merge(['billing_platform' => 'myket']);
            $result = $this->myketService->verifyAndFulfillSubscription(
                $user,
                $request->purchase_token,
                $request->product_id,
                $request->order_id
            );

            if (!$result['success']) {
                $statusCode = 400;
                if (isset($result['error_code']) && $result['error_code'] === MyketService::ERROR_API_KEY_NOT_CONFIGURED) {
                    $statusCode = 503;
                }
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'تایید خرید ناموفق بود',
                    'error_code' => $result['error_code'] ?? null,
                ], $statusCode);
            }

            return response()->json([
                'success' => true,
                'message' => !empty($result['is_duplicate'])
                    ? 'این خرید قبلاً ثبت شده است'
                    : 'خرید با موفقیت تایید و اشتراک فعال شد',
                'data' => [
                    'payment' => $result['payment'],
                    'subscription' => $result['subscription'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Myket purchase verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در پردازش خرید: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get billing platform configuration for app
     * 
     * GET /api/v1/billing/platform-config
     */
    public function getPlatformConfig(Request $request)
    {
        $request->validate([
            'app_version' => 'nullable|string',
            'platform' => 'nullable|string|in:android,ios',
        ]);

        // Get billing platform from app version or default
        $billingPlatform = 'website'; // Default
        
        if ($request->app_version) {
            $appVersion = \App\Models\AppVersion::where('version', $request->app_version)
                ->where('platform', $request->platform ?? 'android')
                ->first();
            
            if ($appVersion && $appVersion->billing_platform) {
                $billingPlatform = $appVersion->billing_platform;
            }
        }

        $config = [
            'billing_platform' => $billingPlatform,
            'supported_platforms' => ['website', 'cafebazaar', 'myket'],
            'platform_config' => [
                'website' => [
                    'name' => 'پرداخت از وب‌سایت',
                    'gateway' => 'zarinpal',
                    'requires_webview' => true,
                ],
                'cafebazaar' => [
                    'name' => 'کافه‌بازار',
                    'package_name' => config('services.cafebazaar.package_name'),
                    'requires_in_app_purchase' => true,
                ],
                'myket' => [
                    'name' => 'مایکت',
                    'package_name' => config('services.myket.package_name'),
                    'requires_in_app_purchase' => true,
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }
}

