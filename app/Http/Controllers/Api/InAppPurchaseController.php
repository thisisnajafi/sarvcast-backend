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
            // Verify purchase with CafeBazaar
            $verification = $this->cafeBazaarService->verifyPurchase(
                $request->purchase_token,
                $request->product_id
            );

            if (!$verification['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $verification['message'] ?? 'تایید خرید ناموفق بود'
                ], 400);
            }

            // Check if payment already exists
            $existingPayment = Payment::where('purchase_token', $request->purchase_token)
                ->where('billing_platform', 'cafebazaar')
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => true,
                    'message' => 'این خرید قبلاً ثبت شده است',
                    'data' => [
                        'payment' => $existingPayment->load('subscription'),
                        'subscription' => $existingPayment->subscription
                    ]
                ]);
            }

            // Find subscription plan by cafebazaar_product_id first, then fallback to slug mapping
            $plan = SubscriptionPlan::where('cafebazaar_product_id', $request->product_id)->first();

            if (!$plan) {
                // Fallback: map product ID to slug via hardcoded mapping (legacy product IDs)
                $subscriptionType = $this->cafeBazaarService->mapProductIdToSubscriptionType($request->product_id);
                if ($subscriptionType) {
                    $plan = SubscriptionPlan::where('slug', $subscriptionType)->first();
                }
            }

            if (!$plan) {
                Log::warning('CafeBazaar verify: no plan found for product_id', [
                    'product_id' => $request->product_id,
                    'user_id' => $user->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'شناسه محصول نامعتبر است: ' . $request->product_id
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Create payment record
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'amount' => $plan->final_price,
                    'currency' => $plan->currency,
                    'payment_method' => 'in_app_purchase',
                    'payment_gateway' => 'cafebazaar',
                    'billing_platform' => 'cafebazaar',
                    'status' => 'completed',
                    'transaction_id' => $request->order_id ?? 'CB_' . time() . '_' . rand(1000, 9999),
                    'purchase_token' => $request->purchase_token,
                    'order_id' => $verification['order_id'] ?? $request->order_id,
                    'product_id' => $request->product_id,
                    'package_name' => config('services.cafebazaar.package_name'),
                    'purchase_state' => $verification['purchase_state'] ?? 'purchased',
                    'purchase_time' => $verification['purchase_time'] ?? now(),
                    'store_response' => $verification['raw_response'] ?? null,
                    'is_acknowledged' => false,
                    'processed_at' => now(),
                    'payment_metadata' => [
                        'verification_time' => now()->toISOString(),
                        'developer_payload' => $verification['developer_payload'] ?? null,
                    ]
                ]);

                // Create or update subscription
                $subscription = $this->subscriptionService->createOrUpdateSubscription(
                    $user->id,
                    $subscriptionType,
                    $plan->final_price,
                    $plan->currency,
                    [
                        'billing_platform' => 'cafebazaar',
                        'payment_id' => $payment->id,
                        'store_subscription_id' => $verification['order_id'] ?? null,
                    ]
                );

                $payment->update(['subscription_id' => $subscription->id]);

                // Acknowledge purchase (required for CafeBazaar)
                $this->cafeBazaarService->acknowledgePurchase(
                    $request->purchase_token,
                    $request->product_id
                );

                $payment->update(['is_acknowledged' => true, 'acknowledged_at' => now()]);

                DB::commit();

                Log::info('CafeBazaar purchase verified and subscription activated', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'product_id' => $request->product_id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'خرید با موفقیت تایید و اشتراک فعال شد',
                    'data' => [
                        'payment' => $payment->fresh()->load('subscription'),
                        'subscription' => $subscription->fresh()
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

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
            // Verify purchase with Myket
            $verification = $this->myketService->verifyPurchase(
                $request->purchase_token,
                $request->product_id
            );

            if (!$verification['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $verification['message'] ?? 'تایید خرید ناموفق بود'
                ], 400);
            }

            // Check if payment already exists
            $existingPayment = Payment::where('purchase_token', $request->purchase_token)
                ->where('billing_platform', 'myket')
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => true,
                    'message' => 'این خرید قبلاً ثبت شده است',
                    'data' => [
                        'payment' => $existingPayment->load('subscription'),
                        'subscription' => $existingPayment->subscription
                    ]
                ]);
            }

            // Map product ID to subscription type
            $subscriptionType = $this->myketService->mapProductIdToSubscriptionType($request->product_id);
            
            if (!$subscriptionType) {
                return response()->json([
                    'success' => false,
                    'message' => 'شناسه محصول نامعتبر است'
                ], 400);
            }

            // Get subscription plan
            $plan = SubscriptionPlan::where('slug', $subscriptionType)->first();
            
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'پلن اشتراک یافت نشد'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Create payment record
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'amount' => $plan->final_price,
                    'currency' => $plan->currency,
                    'payment_method' => 'in_app_purchase',
                    'payment_gateway' => 'myket',
                    'billing_platform' => 'myket',
                    'status' => 'completed',
                    'transaction_id' => $request->order_id ?? 'MK_' . time() . '_' . rand(1000, 9999),
                    'purchase_token' => $request->purchase_token,
                    'order_id' => $verification['order_id'] ?? $request->order_id,
                    'product_id' => $request->product_id,
                    'package_name' => config('services.myket.package_name'),
                    'purchase_state' => $verification['purchase_state'] ?? 'purchased',
                    'purchase_time' => $verification['purchase_time'] ?? now(),
                    'store_response' => $verification['raw_response'] ?? null,
                    'is_acknowledged' => true, // Myket doesn't require explicit acknowledgment
                    'acknowledged_at' => now(),
                    'processed_at' => now(),
                    'payment_metadata' => [
                        'verification_time' => now()->toISOString(),
                        'developer_payload' => $verification['developer_payload'] ?? null,
                    ]
                ]);

                // Create or update subscription
                $subscription = $this->subscriptionService->createOrUpdateSubscription(
                    $user->id,
                    $subscriptionType,
                    $plan->final_price,
                    $plan->currency,
                    [
                        'billing_platform' => 'myket',
                        'payment_id' => $payment->id,
                        'store_subscription_id' => $verification['order_id'] ?? null,
                    ]
                );

                $payment->update(['subscription_id' => $subscription->id]);

                DB::commit();

                Log::info('Myket purchase verified and subscription activated', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'product_id' => $request->product_id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'خرید با موفقیت تایید و اشتراک فعال شد',
                    'data' => [
                        'payment' => $payment->fresh()->load('subscription'),
                        'subscription' => $subscription->fresh()
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Myket purchase verification failed', [
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

