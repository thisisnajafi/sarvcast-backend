<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
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
    public function plans(): JsonResponse
    {
        try {
            $plans = $this->subscriptionService->getAvailablePlans();

            return response()->json([
                'success' => true,
                'data' => [
                    'plans' => $plans
                ],
                'message' => 'پلن‌های اشتراک دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get subscription plans', [
                'error' => $e->getMessage()
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
            'type' => 'required|string|in:monthly,quarterly,yearly,family'
        ], [
            'type.required' => 'نوع اشتراک الزامی است',
            'type.in' => 'نوع اشتراک نامعتبر است'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type = $request->input('type');
            $priceInfo = $this->subscriptionService->calculatePrice($type);

            return response()->json([
                'success' => true,
                'data' => $priceInfo,
                'message' => 'محاسبه قیمت انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to calculate subscription price', [
                'type' => $request->input('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در محاسبه قیمت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new subscription
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:monthly,quarterly,yearly,family',
            'payment_method' => 'nullable|string|max:50',
            'auto_renew' => 'nullable|boolean'
        ], [
            'type.required' => 'نوع اشتراک الزامی است',
            'type.in' => 'نوع اشتراک نامعتبر است',
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
            $type = $request->input('type');
            $options = [
                'payment_method' => $request->input('payment_method'),
                'auto_renew' => $request->input('auto_renew', true),
                'status' => 'pending'
            ];

            $subscription = $this->subscriptionService->createSubscription($user->id, $type, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->summary,
                    'payment_required' => true,
                    'next_step' => 'payment'
                ],
                'message' => 'اشتراک ایجاد شد. لطفاً پرداخت را تکمیل کنید'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create subscription', [
                'user_id' => $request->user()->id,
                'type' => $request->input('type'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد اشتراک: ' . $e->getMessage()
            ], 500);
        }
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
     * Cancel subscription
     */
    public function cancel(Request $request, int $subscriptionId): JsonResponse
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
            $reason = $request->input('reason');
            $subscription = $this->subscriptionService->cancelSubscription($subscriptionId, $reason);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => $subscription->summary
                ],
                'message' => 'اشتراک لغو شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscriptionId,
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
}