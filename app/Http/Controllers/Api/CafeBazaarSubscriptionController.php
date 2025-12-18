<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\FlavorHelper;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\CafeBazaarService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
            // Check if payment already exists (idempotency check)
            $existingPayment = Payment::where('purchase_token', $purchaseToken)
                ->where('billing_platform', 'cafebazaar')
                ->first();

            if ($existingPayment) {
                Log::info('CafeBazaar subscription already verified', [
                    'user_id' => $user->id,
                    'payment_id' => $existingPayment->id,
                    'subscription_id' => $existingPayment->subscription_id,
                    'product_id' => $productId,
                ]);

                $subscription = $existingPayment->subscription;
                
                return response()->json([
                    'success' => true,
                    'message' => 'این خرید قبلاً ثبت و تایید شده است',
                    'data' => [
                        'payment' => $existingPayment->load('subscription'),
                        'subscription' => $subscription,
                        'is_duplicate' => true,
                    ],
                ], 200);
            }

            // Verify purchase with CafeBazaar API
            $verification = $this->cafeBazaarService->verifyPurchase(
                $purchaseToken,
                $productId
            );

            if (!$verification['success']) {
                Log::error('CafeBazaar purchase verification failed', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'error_message' => $verification['message'] ?? 'Unknown error',
                    'error_code' => $verification['error_code'] ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $verification['message'] ?? 'تایید خرید ناموفق بود',
                    'error_code' => $verification['error_code'] ?? null,
                ], 400);
            }

            // Map product ID to subscription type
            $subscriptionType = $this->cafeBazaarService->mapProductIdToSubscriptionType($productId);
            
            if (!$subscriptionType) {
                Log::error('Invalid product ID for CafeBazaar subscription', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'شناسه محصول نامعتبر است',
                ], 400);
            }

            // Get subscription plan
            $plan = SubscriptionPlan::where('slug', $subscriptionType)->first();
            
            if (!$plan) {
                Log::error('Subscription plan not found', [
                    'user_id' => $user->id,
                    'subscription_type' => $subscriptionType,
                    'product_id' => $productId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'پلن اشتراک یافت نشد',
                ], 404);
            }

            // Process subscription in database transaction
            DB::beginTransaction();

            try {
                // Create payment record
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'amount' => $plan->final_price,
                    'currency' => $plan->currency ?? 'IRR',
                    'payment_method' => 'in_app_purchase',
                    'payment_gateway' => 'cafebazaar',
                    'billing_platform' => 'cafebazaar',
                    'status' => 'completed',
                    'transaction_id' => $orderId ?? $verification['order_id'] ?? 'CB_' . time() . '_' . rand(1000, 9999),
                    'purchase_token' => $purchaseToken,
                    'order_id' => $verification['order_id'] ?? $orderId,
                    'product_id' => $productId,
                    'package_name' => config('services.cafebazaar.package_name'),
                    'purchase_state' => $verification['purchase_state'] ?? 'purchased',
                    'purchase_time' => $verification['purchase_time'] ?? now(),
                    'store_response' => $verification['raw_response'] ?? null,
                    'is_acknowledged' => false,
                    'processed_at' => now(),
                    'payment_metadata' => [
                        'verification_time' => now()->toISOString(),
                        'developer_payload' => $verification['developer_payload'] ?? null,
                        'purchase_type' => $verification['purchase_type'] ?? null,
                        'acknowledgement_state' => $verification['acknowledgement_state'] ?? null,
                        'billing_platform' => 'cafebazaar',
                    ],
                ]);

                Log::info('Payment record created for CafeBazaar subscription', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'product_id' => $productId,
                    'amount' => $payment->amount,
                ]);

                // Get or create subscription
                $existingSubscription = $this->subscriptionService->getActiveSubscription($user->id);
                
                if ($existingSubscription && $existingSubscription->billing_platform === 'cafebazaar') {
                    // Extend existing CafeBazaar subscription
                    $currentEndDate = Carbon::parse($existingSubscription->end_date);
                    $newEndDate = $currentEndDate->copy()->addDays($plan->duration_days);
                    
                    $existingSubscription->update([
                        'end_date' => $newEndDate,
                        'price' => $plan->final_price,
                        'currency' => $plan->currency ?? 'IRR',
                        'status' => 'active',
                        'auto_renew' => true,
                        'store_subscription_id' => $verification['order_id'] ?? $orderId,
                        'store_expiry_time' => $newEndDate->toISOString(),
                        'store_metadata' => [
                            'last_purchase_token' => $purchaseToken,
                            'last_product_id' => $productId,
                            'last_verification_time' => now()->toISOString(),
                        ],
                    ]);

                    $subscription = $existingSubscription->fresh();
                    
                    Log::info('Existing CafeBazaar subscription extended', [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'old_end_date' => $currentEndDate->toDateString(),
                        'new_end_date' => $newEndDate->toDateString(),
                    ]);
                } else {
                    // Create new subscription
                    $startDate = now();
                    $endDate = Carbon::parse($startDate)->addDays($plan->duration_days);

                    $subscription = Subscription::create([
                        'user_id' => $user->id,
                        'type' => $subscriptionType,
                        'status' => 'active',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'price' => $plan->final_price,
                        'currency' => $plan->currency ?? 'IRR',
                        'auto_renew' => true,
                        'payment_method' => 'in_app_purchase',
                        'transaction_id' => $payment->transaction_id,
                        'billing_platform' => 'cafebazaar',
                        'store_subscription_id' => $verification['order_id'] ?? $orderId,
                        'auto_renew_enabled' => true,
                        'store_expiry_time' => $endDate->toISOString(),
                        'store_metadata' => [
                            'purchase_token' => $purchaseToken,
                            'product_id' => $productId,
                            'verification_time' => now()->toISOString(),
                            'purchase_state' => $verification['purchase_state'] ?? 'purchased',
                        ],
                    ]);

                    Log::info('New CafeBazaar subscription created', [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'type' => $subscriptionType,
                        'end_date' => $endDate->toDateString(),
                    ]);
                }

                // Link payment to subscription
                $payment->update(['subscription_id' => $subscription->id]);

                // Acknowledge purchase with CafeBazaar (required)
                $acknowledgement = $this->cafeBazaarService->acknowledgePurchase(
                    $purchaseToken,
                    $productId
                );

                if ($acknowledgement['success']) {
                    $payment->update([
                        'is_acknowledged' => true,
                        'acknowledged_at' => now(),
                    ]);

                    Log::info('CafeBazaar purchase acknowledged', [
                        'payment_id' => $payment->id,
                        'product_id' => $productId,
                    ]);
                } else {
                    Log::warning('CafeBazaar purchase acknowledgement failed', [
                        'payment_id' => $payment->id,
                        'product_id' => $productId,
                        'error' => $acknowledgement['message'] ?? 'Unknown error',
                    ]);
                }

                DB::commit();

                Log::info('CafeBazaar subscription verified and activated successfully', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'product_id' => $productId,
                    'subscription_type' => $subscriptionType,
                    'end_date' => $subscription->end_date->toDateString(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'خرید با موفقیت تایید و اشتراک فعال شد',
                    'data' => [
                        'payment' => $payment->fresh()->load('subscription'),
                        'subscription' => $subscription->fresh(),
                        'acknowledged' => $acknowledgement['success'] ?? false,
                    ],
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Database transaction failed during CafeBazaar subscription verification', [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }

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

            return response()->json([
                'success' => true,
                'data' => [
                    'has_subscription' => true,
                    'subscription' => $subscription,
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
}

