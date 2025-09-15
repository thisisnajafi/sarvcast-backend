<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Get available subscription plans
     */
    public function plans()
    {
        $plans = [
            [
                'id' => 'monthly',
                'name' => 'اشتراک ماهانه',
                'price' => 50000, // in Toman
                'duration_days' => 30,
                'features' => [
                    'دسترسی به تمام داستان‌ها',
                    'اپیزودهای پولی',
                    'بدون تبلیغات',
                    'دانلود آفلاین'
                ]
            ],
            [
                'id' => 'yearly',
                'name' => 'اشتراک سالانه',
                'price' => 500000, // in Toman
                'duration_days' => 365,
                'features' => [
                    'دسترسی به تمام داستان‌ها',
                    'اپیزودهای پولی',
                    'بدون تبلیغات',
                    'دانلود آفلاین',
                    '20% تخفیف ویژه'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'plans' => $plans
            ]
        ]);
    }

    /**
     * Create new subscription
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:zarinpal,payir'
        ]);

        $user = Auth::user();

        // Check if user already has active subscription
        if ($user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'User already has an active subscription'
            ], 400);
        }

        $plans = [
            'monthly' => ['price' => 50000, 'duration_days' => 30],
            'yearly' => ['price' => 500000, 'duration_days' => 365]
        ];

        $plan = $plans[$request->plan_id];

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $request->plan_id,
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays($plan['duration_days']),
            'amount' => $plan['price'],
            'payment_method' => $request->payment_method
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'data' => [
                'subscription' => $subscription,
                'payment_url' => $this->generatePaymentUrl($subscription, $request->payment_method)
            ]
        ], 201);
    }

    /**
     * Get current subscription
     */
    public function current()
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription,
                'has_active_subscription' => $user->hasActiveSubscription()
            ]
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel()
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    /**
     * Generate payment URL
     */
    private function generatePaymentUrl(Subscription $subscription, string $method)
    {
        // This would integrate with actual payment gateways
        // For now, return a placeholder URL
        return route('payment.process', [
            'subscription' => $subscription->id,
            'method' => $method
        ]);
    }
}
