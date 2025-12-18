<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MyketPlan;
use App\Models\MyketSubscription;
use App\Models\MyketUserSubscription;
use App\Models\Payment;
use App\Services\MyketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Myket Subscription Controller
 * 
 * Handles Myket subscription plan management, purchase verification, and status updates.
 */
class MyketSubscriptionController extends Controller
{
    protected $myketService;

    public function __construct(MyketService $myketService)
    {
        $this->myketService = $myketService;
    }

    /**
     * List available Myket plans
     * 
     * GET /api/plans
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listPlans(Request $request)
    {
        try {
            $plans = MyketPlan::active()
                ->orderBy('price', 'asc')
                ->get()
                ->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'price' => (float) $plan->price,
                        'duration_days' => $plan->duration_days,
                        'features' => $plan->features ?? [],
                    ];
                });

            Log::info('Myket plans listed', [
                'plans_count' => $plans->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'plans' => $plans,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to list Myket plans', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست پلن‌ها',
            ], 500);
        }
    }

    /**
     * User purchases a plan via Myket
     * 
     * POST /api/subscribe
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'purchase_token' => 'required|string|max:500',
            'plan_id' => 'required|integer|exists:myket_plans,id',
        ], [
            'purchase_token.required' => 'توکن خرید الزامی است',
            'purchase_token.string' => 'توکن خرید باید رشته باشد',
            'purchase_token.max' => 'توکن خرید نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'plan_id.required' => 'شناسه پلن الزامی است',
            'plan_id.exists' => 'پلن انتخابی یافت نشد',
        ]);

        if ($validator->fails()) {
            Log::warning('Myket subscription validation failed', [
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
        $purchaseToken = $request->input('purchase_token');
        $planId = $request->input('plan_id');

        Log::info('Myket subscription purchase started', [
            'user_id' => $user->id,
            'plan_id' => $planId,
            'purchase_token_preview' => substr($purchaseToken, 0, 20) . '...',
        ]);

        try {
            // Get plan
            $plan = MyketPlan::findOrFail($planId);

            // Check if payment already exists (idempotency check)
            $existingPayment = Payment::where('purchase_token', $purchaseToken)
                ->where('billing_platform', 'myket')
                ->first();

            if ($existingPayment) {
                Log::info('Myket subscription already verified', [
                    'user_id' => $user->id,
                    'payment_id' => $existingPayment->id,
                    'plan_id' => $planId,
                ]);

                $subscription = MyketSubscription::where('user_id', $user->id)
                    ->where('plan_id', $planId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'این خرید قبلاً ثبت و تایید شده است',
                    'data' => [
                        'subscription' => $subscription,
                        'is_duplicate' => true,
                    ],
                ], 200);
            }

            // Verify purchase with Myket API
            // Note: For Myket, we need a product_id. We'll use plan name as product_id mapping
            $productId = $this->mapPlanIdToProductId($planId);
            
            if (!$productId) {
                Log::error('Product ID mapping not found for plan', [
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'شناسه محصول برای این پلن یافت نشد',
                ], 400);
            }

            $verification = $this->myketService->verifyPurchase(
                $purchaseToken,
                $productId
            );

            if (!$verification['success']) {
                Log::error('Myket purchase verification failed', [
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                    'product_id' => $productId,
                    'error_message' => $verification['message'] ?? 'Unknown error',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $verification['message'] ?? 'تایید خرید ناموفق بود',
                    'error_code' => $verification['error_code'] ?? null,
                ], 400);
            }

            // Process subscription in database transaction
            DB::beginTransaction();

            try {
                // Create payment record
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'amount' => $plan->price,
                    'currency' => 'IRT',
                    'payment_method' => 'in_app_purchase',
                    'payment_gateway' => 'myket',
                    'billing_platform' => 'myket',
                    'status' => 'completed',
                    'transaction_id' => $verification['order_id'] ?? 'MYKET_' . time() . '_' . rand(1000, 9999),
                    'purchase_token' => $purchaseToken,
                    'order_id' => $verification['order_id'] ?? null,
                    'product_id' => $productId,
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
                        'purchase_type' => $verification['purchase_type'] ?? null,
                        'billing_platform' => 'myket',
                    ],
                ]);

                Log::info('Payment record created for Myket subscription', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'plan_id' => $planId,
                    'amount' => $payment->amount,
                ]);

                // Get or create subscription
                $existingSubscription = MyketSubscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->where('end_date', '>', now())
                    ->first();

                if ($existingSubscription) {
                    // Extend existing subscription
                    $currentEndDate = Carbon::parse($existingSubscription->end_date);
                    $newEndDate = $currentEndDate->copy()->addDays($plan->duration_days);

                    $existingSubscription->update([
                        'plan_id' => $planId,
                        'end_date' => $newEndDate,
                        'status' => 'active',
                    ]);

                    $subscription = $existingSubscription->fresh();

                    Log::info('Existing Myket subscription extended', [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'old_end_date' => $currentEndDate->toDateString(),
                        'new_end_date' => $newEndDate->toDateString(),
                    ]);
                } else {
                    // Create new subscription
                    $startDate = now();
                    $endDate = Carbon::parse($startDate)->addDays($plan->duration_days);

                    $subscription = MyketSubscription::create([
                        'user_id' => $user->id,
                        'plan_id' => $planId,
                        'status' => 'active',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]);

                    Log::info('New Myket subscription created', [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'plan_id' => $planId,
                        'end_date' => $endDate->toDateString(),
                    ]);
                }

                // Create or update user subscription record
                MyketUserSubscription::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                    ],
                    [
                        'status' => 'active',
                    ]
                );

                // Link payment to subscription (if Payment model has subscription_id)
                if ($payment->subscription_id) {
                    // Note: Payment model might not have subscription_id for Myket subscriptions
                    // This is handled separately
                }

                DB::commit();

                Log::info('Myket subscription verified and activated successfully', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $planId,
                    'end_date' => $subscription->end_date->toDateString(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'خرید با موفقیت تایید و اشتراک فعال شد',
                    'data' => [
                        'payment' => $payment->fresh(),
                        'subscription' => $subscription->fresh()->load('plan'),
                    ],
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Database transaction failed during Myket subscription verification', [
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Plan not found', [
                'user_id' => $user->id,
                'plan_id' => $planId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'پلن انتخابی یافت نشد',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Myket subscription verification failed', [
                'user_id' => $user->id,
                'plan_id' => $planId,
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
     * Check if user has an active subscription
     * 
     * GET /api/subscription-status
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscriptionStatus(Request $request)
    {
        $user = Auth::user();

        try {
            $subscription = MyketSubscription::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->with('plan')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'has_subscription' => false,
                        'subscription' => null,
                        'is_active' => false,
                    ],
                ], 200);
            }

            $isActive = $subscription->isActive();

            Log::info('Myket subscription status checked', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'is_active' => $isActive,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'has_subscription' => true,
                    'subscription' => [
                        'id' => $subscription->id,
                        'plan' => [
                            'id' => $subscription->plan->id,
                            'name' => $subscription->plan->name,
                            'price' => (float) $subscription->plan->price,
                            'duration_days' => $subscription->plan->duration_days,
                            'features' => $subscription->plan->features ?? [],
                        ],
                        'status' => $subscription->status,
                        'start_date' => $subscription->start_date->toISOString(),
                        'end_date' => $subscription->end_date->toISOString(),
                        'days_remaining' => $subscription->days_remaining,
                    ],
                    'is_active' => $isActive,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get Myket subscription status', [
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
     * Cancel active subscription
     * 
     * POST /api/cancel-subscription
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSubscription(Request $request)
    {
        $user = Auth::user();

        try {
            $subscription = MyketSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'اشتراک فعالی یافت نشد',
                ], 404);
            }

            // Update subscription status
            $subscription->update([
                'status' => 'cancelled',
            ]);

            // Update user subscription status
            MyketUserSubscription::where('user_id', $user->id)
                ->where('subscription_id', $subscription->id)
                ->update([
                    'status' => 'cancelled',
                ]);

            Log::info('Myket subscription cancelled', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'اشتراک با موفقیت لغو شد',
                'data' => [
                    'subscription' => $subscription->fresh()->load('plan'),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to cancel Myket subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در لغو اشتراک',
            ], 500);
        }
    }

    /**
     * Map plan ID to Myket product ID
     * 
     * @param int $planId
     * @return string|null
     */
    private function mapPlanIdToProductId(int $planId): ?string
    {
        $plan = MyketPlan::find($planId);
        
        if (!$plan) {
            return null;
        }

        // Map plan names to product IDs
        // This should match the product IDs configured in Myket developer console
        $mapping = [
            'Basic' => 'myket_subscription_basic',
            'Premium' => 'myket_subscription_premium',
            'Family' => 'myket_subscription_family',
        ];

        return $mapping[$plan->name] ?? null;
    }
}
