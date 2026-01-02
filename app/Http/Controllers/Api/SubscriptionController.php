<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Services\NotificationService;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected $subscriptionService;
    protected $notificationService;

    public function __construct(SubscriptionService $subscriptionService, NotificationService $notificationService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $perPage = min($perPage, 100); // Max 100 per page

            $subscriptions = $this->subscriptionService->getUserSubscriptions($user->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscriptions' => $subscriptions->items(),
                    'pagination' => [
                        'current_page' => $subscriptions->currentPage(),
                        'last_page' => $subscriptions->lastPage(),
                        'per_page' => $subscriptions->perPage(),
                        'total' => $subscriptions->total(),
                        'has_more' => $subscriptions->hasMorePages()
                    ]
                ],
                'message' => 'اشتراک‌های شما دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user subscriptions', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اشتراک‌ها'
            ], 500);
        }
    }

    /**
     * Get user's current subscription
     */
    public function current(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentSubscription = $this->subscriptionService->getActiveSubscription($user->id);

            if (!$currentSubscription) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'subscription' => null,
                        'has_active_subscription' => false
                    ],
                    'message' => 'اشتراک فعالی یافت نشد'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $currentSubscription->summary,
                    'has_active_subscription' => true,
                    'days_remaining' => $currentSubscription->days_remaining,
                    'is_expired' => $currentSubscription->isExpired()
                ],
                'message' => 'اشتراک فعلی دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get current subscription', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اشتراک فعلی'
            ], 500);
        }
    }

    /**
     * Get subscription status
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $status = $this->subscriptionService->getSubscriptionStatus($user->id);

            return response()->json([
                'success' => true,
                'data' => $status,
                'message' => 'وضعیت اشتراک دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get subscription status', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت وضعیت اشتراک'
            ], 500);
        }
    }

    /**
     * Get available subscription plans
     */
    public function plans(Request $request): JsonResponse
    {
        try {
            // Detect flavor from request
            $flavor = \App\Helpers\FlavorHelper::getFlavor($request);
            
            // Get all active plans
            $plans = SubscriptionPlan::active()->ordered()->get();
            
            // Format plans with flavor-specific data
            $formattedPlans = $plans->map(function($plan) use ($flavor) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'duration_days' => $plan->duration_days,
                    'duration_text' => $plan->duration_text,
                    'currency' => $plan->currency,
                    'discount_percentage' => $plan->discount_percentage,
                    'is_featured' => $plan->is_featured,
                    'features' => $plan->features ?? [],
                    // Flavor-specific pricing
                    'price' => $plan->getPriceForFlavor($flavor),
                    'final_price' => $plan->getFinalPriceForFlavor($flavor),
                    'product_id' => $plan->getProductIdForFlavor($flavor),
                    'available' => $plan->isAvailableForFlavor($flavor),
                    // All flavor data
                    'flavors' => $plan->getFlavorData(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'plans' => $formattedPlans,
                    'current_flavor' => $flavor,
                ],
                'message' => 'پلن‌های اشتراک دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get subscription plans', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پلن‌های اشتراک'
            ], 500);
        }
    }

    /**
     * Calculate subscription price
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'nullable|integer|exists:subscription_plans,id',
            'type' => 'nullable|string|in:monthly,quarterly,yearly,family',
            'plan_slug' => 'nullable|string|in:1month,3months,6months,1year',
            'coupon_code' => 'nullable|string|max:50'
        ], [
            'plan_id.integer' => 'شناسه پلن اشتراک باید عدد باشد',
            'plan_id.exists' => 'پلن اشتراک یافت نشد',
            'type.in' => 'نوع اشتراک نامعتبر است',
            'plan_slug.in' => 'نوع اشتراک نامعتبر است',
            'coupon_code.max' => 'کد کوپن نمی‌تواند بیشتر از 50 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $planId = $request->input('plan_id');
            $type = $request->input('type');
            $planSlug = $request->input('plan_slug');
            $couponCode = $request->input('coupon_code');

            // Determine the plan type to calculate price for
            $planType = null;
            $plan = null;

            if ($planId) {
                $plan = \App\Models\SubscriptionPlan::find($planId);
                if (!$plan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'پلن اشتراک یافت نشد'
                    ], 404);
                }
                $planType = $plan->slug ?? $plan->type;
            } elseif ($planSlug) {
                $planType = $planSlug;
                // Try to get the plan object for additional info
                $plan = \App\Models\SubscriptionPlan::where('slug', $planSlug)->first();
            } elseif ($type) {
                $planType = $type;
                // Try to get the plan object for additional info
                $plan = \App\Models\SubscriptionPlan::where('type', $type)->first();
            }

            if (!$planType) {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع پلن اشتراک مشخص نشده است'
                ], 400);
            }

            // If we have a plan object, use it directly for more accurate pricing
            if ($plan) {
                $priceInfo = $this->calculatePriceFromPlan($plan);
            } else {
                $priceInfo = $this->subscriptionService->calculatePrice($planType);
            }

            // Apply coupon discount if provided
            $couponInfo = null;
            if ($couponCode) {
                $user = $request->user();
                $baseAmount = $priceInfo['discounted_price'] ?? $priceInfo['base_price'];

                $couponValidation = app(\App\Services\CouponService::class)->validateCouponCode(
                    $couponCode,
                    $user,
                    $baseAmount
                );

                if ($couponValidation['success']) {
                    $couponInfo = $couponValidation['data'];
                    $priceInfo['original_price'] = $baseAmount;
                    $priceInfo['coupon_discount'] = $couponInfo['discount_amount'];
                    $priceInfo['final_price'] = $couponInfo['final_amount'];
                    $priceInfo['coupon_code'] = $couponCode;
                    $priceInfo['coupon_info'] = $couponInfo['coupon'];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $couponValidation['message']
                    ], 400);
                }
            } else {
                $priceInfo['final_price'] = $priceInfo['discounted_price'] ?? $priceInfo['base_price'];
            }

            // Convert currency if needed
            if ($plan && $plan->currency === 'IRT') {
                $priceInfo['original_amount'] = $priceInfo['final_price'];
                $priceInfo['original_currency'] = 'IRT';
                $priceInfo['amount'] = $this->convertCurrency($priceInfo['final_price'], 'IRT', 'IRR');
                $priceInfo['currency'] = 'IRR';
                $priceInfo['conversion_rate'] = 10;
                $priceInfo['conversion_note'] = 'قیمت از ریال تومان به ریال تبدیل شده است';
            }

            return response()->json([
                'success' => true,
                'data' => $priceInfo,
                'message' => 'محاسبه قیمت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to calculate subscription price', [
                'plan_id' => $request->input('plan_id'),
                'type' => $request->input('type'),
                'plan_slug' => $request->input('plan_slug'),
                'coupon_code' => $request->input('coupon_code'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در محاسبه قیمت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new subscription (alias for create)
     */
    public function store(Request $request): JsonResponse
    {
        return $this->create($request);
    }

    /**
     * Create new subscription
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:subscription_plans,id',
            'plan_slug' => 'nullable|string|in:1month,3months,6months,1year',
            'coupon_code' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'auto_renew' => 'nullable|boolean'
        ], [
            'plan_id.required' => 'شناسه پلن اشتراک الزامی است',
            'plan_id.integer' => 'شناسه پلن اشتراک باید عدد باشد',
            'plan_id.exists' => 'پلن اشتراک یافت نشد',
            'plan_slug.in' => 'نوع اشتراک نامعتبر است',
            'coupon_code.max' => 'کد کوپن نمی‌تواند بیشتر از 50 کاراکتر باشد',
            'payment_method.max' => 'روش پرداخت نمی‌تواند بیشتر از 50 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $planId = $request->input('plan_id');
            $planSlug = $request->input('plan_slug');
            $couponCode = $request->input('coupon_code');

            // Get plan details - try plan_id first, then plan_slug
            $plan = null;
            if ($planId) {
                $plan = \App\Models\SubscriptionPlan::find($planId);
            } elseif ($planSlug) {
                $plan = $this->subscriptionService->getPlan($planSlug);
            }

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'پلن اشتراک یافت نشد'
                ], 404);
            }

            // Calculate final amount
            $amount = $plan->final_price;
            $planCurrency = $plan->currency ?? 'IRT';

            // Apply coupon if provided
            if ($couponCode) {
                $couponValidation = app(\App\Services\CouponService::class)->validateCouponCode(
                    $couponCode,
                    $user,
                    $amount
                );

                if ($couponValidation['success']) {
                    $amount = $couponValidation['data']['final_amount'];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $couponValidation['message']
                    ], 400);
                }
            }

            // Convert currency from IRT to IRR if needed
            $convertedAmount = $this->convertCurrency($amount, $planCurrency, 'IRR');
            $finalCurrency = 'IRR';

            // Map plan slug to correct ENUM value
            $subscriptionType = $this->mapPlanSlugToEnum($planSlug ?: $plan->slug);

            // Create subscription
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'type' => $subscriptionType,
                'price' => (int) $convertedAmount, // Ensure integer for consistency
                'currency' => $finalCurrency,
                'status' => 'pending',
                'start_date' => null,
                'end_date' => null,
                'auto_renew' => $request->input('auto_renew', true),
            ]);

            // Send subscription created notification
            $this->notificationService->sendSubscriptionNotification(
                $user,
                'subscription_created',
                ['subscription_id' => $subscription->id]
            );

            // Get plan's first feature for description
            $planFeatures = $plan->features ?? [];
            $firstFeature = !empty($planFeatures) ? $planFeatures[0] : ($plan->name ?? $plan->title ?? 'اشتراک');

            // Create payment record
            $payment = \App\Models\Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => (int) $convertedAmount, // Ensure integer for Zarinpal compatibility
                'currency' => $finalCurrency,
                'payment_method' => $request->input('payment_method', 'zarinpal'),
                'status' => 'pending',
                'transaction_id' => $this->generateTransactionId()
            ]);

            // Set description for payment service (since it's not in database)
            // $payment->description = $firstFeature; // Removed - not a database column

            // Initiate Zarinpal payment
            $paymentService = app(\App\Services\PaymentService::class);
            $paymentResult = $paymentService->initiateZarinPalPayment($payment, $firstFeature);

            if (!$paymentResult['success']) {
                // If payment initiation fails, clean up the subscription and payment
                $subscription->delete();
                $payment->delete();

                return response()->json([
                    'success' => false,
                    'message' => $paymentResult['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'اشتراک ایجاد شد. لطفاً پرداخت را تکمیل کنید.',
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'type' => $subscription->type,
                        'price' => $subscription->price,
                        'currency' => $subscription->currency,
                        'status' => $subscription->status,
                        'start_date' => $subscription->start_date,
                        'end_date' => $subscription->end_date,
                        'created_at' => $subscription->created_at
                    ],
                    'payment' => [
                        'id' => $payment->id,
                        'user_id' => $payment->user_id,
                        'subscription_id' => $payment->subscription_id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'payment_method' => $payment->payment_method,
                        'status' => $payment->status,
                        'transaction_id' => $payment->transaction_id,
                        'description' => $firstFeature,
                        'created_at' => $payment->created_at
                    ],
                    'payment_url' => $paymentResult['payment_url'],
                    'authority' => $paymentResult['authority']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create subscription', [
                'user_id' => $request->user()->id,
                'plan_slug' => $request->input('plan_slug'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد اشتراک: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId(): string
    {
        return 'SUB_' . time() . '_' . rand(1000, 9999);
    }

    /**
     * Activate subscription
     */
    public function activate(Request $request, int $subscriptionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'nullable|string|max:100'
        ], [
            'transaction_id.max' => 'شناسه تراکنش نمی‌تواند بیشتر از 100 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transactionId = $request->input('transaction_id');
            $subscription = $this->subscriptionService->activateSubscription($subscriptionId, $transactionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->summary
                ],
                'message' => 'اشتراک فعال شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to activate subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در فعال‌سازی اشتراک: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel subscription (cancels current user's active subscription)
     */
    public function cancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ], [
            'reason.max' => 'دلیل لغو نمی‌تواند بیشتر از 500 کاراکتر باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $reason = $request->input('reason');
            
            // Get current active subscription
            $currentSubscription = $this->subscriptionService->getActiveSubscription($user->id);
            
            if (!$currentSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'اشتراک فعالی یافت نشد'
                ], 404);
            }
            
            $subscription = $this->subscriptionService->cancelSubscription($currentSubscription->id, $reason);

            // Send subscription cancelled notification
            $this->notificationService->sendSubscriptionNotification(
                $user,
                'subscription_cancelled',
                ['subscription_id' => $subscription->id, 'reason' => $reason]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->summary
                ],
                'message' => 'اشتراک لغو شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در لغو اشتراک: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renew subscription
     */
    public function renew(Request $request, int $subscriptionId): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->renewSubscription($subscriptionId);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->summary
                ],
                'message' => 'اشتراک تمدید شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to renew subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در تمدید اشتراک: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upgrade subscription
     */
    public function upgrade(Request $request, int $subscriptionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_type' => 'required|string|in:monthly,quarterly,yearly,family'
        ], [
            'new_type.required' => 'نوع جدید اشتراک الزامی است',
            'new_type.in' => 'نوع جدید اشتراک نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $newType = $request->input('new_type');
            $subscription = $this->subscriptionService->upgradeSubscription($subscriptionId, $newType);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->summary
                ],
                'message' => 'اشتراک ارتقا یافت'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to upgrade subscription', [
                'subscription_id' => $subscriptionId,
                'new_type' => $request->input('new_type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارتقای اشتراک: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create trial subscription
     */
    public function createTrial(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trial_days' => 'nullable|integer|min:1|max:30'
        ], [
            'trial_days.min' => 'روزهای آزمایشی نمی‌تواند کمتر از 1 باشد',
            'trial_days.max' => 'روزهای آزمایشی نمی‌تواند بیشتر از 30 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $trialDays = $request->input('trial_days', 7);

            $subscription = $this->subscriptionService->createTrialSubscription($user->id, $trialDays);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->summary
                ],
                'message' => 'اشتراک آزمایشی ایجاد شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create trial subscription', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد اشتراک آزمایشی: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscription details
     */
    public function show(Request $request, int $subscriptionId): JsonResponse
    {
        try {
            $user = $request->user();
            $subscription = Subscription::where('id', $subscriptionId)
                                      ->where('user_id', $user->id)
                                      ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'اشتراک یافت نشد'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->summary
                ],
                'message' => 'جزئیات اشتراک دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get subscription details', [
                'subscription_id' => $subscriptionId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت جزئیات اشتراک'
            ], 500);
        }
    }

    /**
     * Get subscription statistics (admin only)
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $stats = $this->subscriptionService->getSubscriptionStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار اشتراک‌ها دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get subscription stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار اشتراک‌ها'
            ], 500);
        }
    }

    /**
     * Calculate price directly from plan object
     */
    private function calculatePriceFromPlan(\App\Models\SubscriptionPlan $plan): array
    {
        $basePrice = $plan->price;
        $discount = $plan->discount_percentage ?? 0;
        $discountedPrice = $basePrice - ($basePrice * $discount / 100);

        return [
            'type' => $plan->slug ?? $plan->type,
            'name' => $plan->name,
            'base_price' => $basePrice,
            'discount_percentage' => $discount,
            'discounted_price' => $discountedPrice,
            'savings' => $basePrice - $discountedPrice,
            'currency' => $plan->currency ?? 'IRT',
            'duration_days' => $plan->duration_days ?? 30,
            'description' => $plan->description,
            'plan_id' => $plan->id
        ];
    }

    /**
     * Convert currency amount
     */
    private function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): int
    {
        // If currencies are the same, return original amount as integer
        if ($fromCurrency === $toCurrency) {
            return (int) $amount;
        }

        // Convert IRT to IRR (multiply by 10)
        if ($fromCurrency === 'IRT' && $toCurrency === 'IRR') {
            return (int) ($amount * 10);
        }

        // Convert IRR to IRT (divide by 10)
        if ($fromCurrency === 'IRR' && $toCurrency === 'IRT') {
            return (int) ($amount / 10);
        }

        // For other currency conversions, you can add more logic here
        // For now, return original amount if conversion is not supported
        Log::warning('Currency conversion not supported', [
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'amount' => $amount
        ]);

        return (int) $amount;
    }

    /**
     * Map plan slug to correct ENUM value for subscriptions table
     */
    private function mapPlanSlugToEnum(string $planSlug): string
    {
        // Mapping from various possible slug formats to ENUM values
        $slugMapping = [
            // Direct matches
            '1month' => '1month',
            '3months' => '3months',
            '6months' => '6months',
            '1year' => '1year',

            // Singular forms
            '3month' => '3months',
            '6month' => '6months',

            // Alternative formats
            'monthly' => '1month',
            'quarterly' => '3months',
            'semi-annual' => '6months',
            'annual' => '1year',
            'yearly' => '1year',

            // Numeric formats
            '1' => '1month',
            '2' => '3months',
            '3' => '6months',
            '4' => '1year',
        ];

        $mappedSlug = $slugMapping[$planSlug] ?? $planSlug;

        // Validate that the mapped slug is a valid ENUM value
        $validEnumValues = ['1month', '3months', '6months', '1year'];

        if (!in_array($mappedSlug, $validEnumValues)) {
            Log::warning('Invalid subscription type mapped', [
                'original_slug' => $planSlug,
                'mapped_slug' => $mappedSlug,
                'valid_values' => $validEnumValues
            ]);

            // Default to 1month if mapping fails
            return '1month';
        }

        return $mappedSlug;
    }

    /**
     * Debug subscription status for a user
     */
    public function debugSubscription(Request $request)
    {
        $user = $request->user();

        $subscriptions = Subscription::where('user_id', $user->id)->get();
        $activeSubscription = $user->activeSubscription;

        $debugInfo = [
            'user_id' => $user->id,
            'total_subscriptions' => $subscriptions->count(),
            'active_subscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'type' => $activeSubscription->type,
                'status' => $activeSubscription->status,
                'start_date' => $activeSubscription->start_date,
                'end_date' => $activeSubscription->end_date,
                'days_remaining' => max(0, now()->diffInDays($activeSubscription->end_date, false)),
                'is_active' => $activeSubscription->status === 'active' && $activeSubscription->end_date > now()
            ] : null,
            'all_subscriptions' => $subscriptions->map(function($sub) {
                return [
                    'id' => $sub->id,
                    'type' => $sub->type,
                    'status' => $sub->status,
                    'start_date' => $sub->start_date,
                    'end_date' => $sub->end_date,
                    'is_active' => $sub->status === 'active' && $sub->end_date > now(),
                    'created_at' => $sub->created_at,
                    'updated_at' => $sub->updated_at
                ];
            }),
            'current_time' => now(),
            'timezone' => config('app.timezone')
        ];

        return response()->json([
            'success' => true,
            'debug_info' => $debugInfo
        ]);
    }

    /**
     * Manually activate a subscription (admin/debug only)
     */
    public function manuallyActivateSubscription(Request $request, int $subscriptionId)
    {
        // Only allow for admin users or subscription owner
        $user = $request->user();
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'اشتراک یافت نشد'
            ], 404);
        }

        if ($user->role !== 'admin' && $subscription->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $paymentService = app(\App\Services\PaymentService::class);
        $result = $paymentService->manuallyActivateSubscription($subscriptionId);

        return response()->json($result);
    }

    /**
     * Debug ZarinPal configuration
     */
    public function debugZarinPal(Request $request): JsonResponse
    {
        try {
            $paymentService = app(\App\Services\PaymentService::class);
            $configCheck = $paymentService->checkZarinPalConfiguration();

            return response()->json([
                'success' => true,
                'data' => $configCheck,
                'message' => 'اطلاعات پیکربندی زرین‌پال دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to debug ZarinPal configuration', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات پیکربندی زرین‌پال',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
