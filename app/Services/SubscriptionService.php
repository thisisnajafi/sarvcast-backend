<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Payment;
use App\Events\SubscriptionRenewalEvent;
use App\Events\SubscriptionCancellationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Get available subscription plans from database
     */
    public function getPlans(): array
    {
        return SubscriptionPlan::active()->ordered()->get()->keyBy('slug')->toArray();
    }

    /**
     * Get a specific plan by slug
     */
    public function getPlan(string $slug): ?SubscriptionPlan
    {
        return SubscriptionPlan::active()->where('slug', $slug)->first();
    }

    /**
     * Create a new subscription
     */
    public function createSubscription(int $userId, string $type, array $options = []): Subscription
    {
        try {
            DB::beginTransaction();

            // Validate plan type
            $plan = $this->getPlan($type);
            if (!$plan) {
                throw new \InvalidArgumentException('Invalid subscription plan type');
            }

            $user = User::findOrFail($userId);

            // Check if user already has an active subscription
            $activeSubscription = $this->getActiveSubscription($userId);
            if ($activeSubscription) {
                throw new \InvalidArgumentException('User already has an active subscription');
            }

            // Calculate subscription dates
            $startDate = $options['start_date'] ?? now();
            $endDate = Carbon::parse($startDate)->addDays($plan->duration_days);

            // Create subscription
            $subscription = Subscription::create([
                'user_id' => $userId,
                'type' => $type,
                'status' => $options['status'] ?? 'pending',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'auto_renew' => $options['auto_renew'] ?? true,
                'payment_method' => $options['payment_method'] ?? null,
                'transaction_id' => $options['transaction_id'] ?? null
            ]);

            DB::commit();

            Log::info('Subscription created', [
                'user_id' => $userId,
                'subscription_id' => $subscription->id,
                'type' => $type
            ]);

            return $subscription;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create subscription', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Activate a subscription
     */
    public function activateSubscription(int $subscriptionId, string $transactionId = null): Subscription
    {
        try {
            DB::beginTransaction();

            $subscription = Subscription::findOrFail($subscriptionId);

            if ($subscription->status !== 'pending') {
                throw new \InvalidArgumentException('Subscription is not in pending status');
            }

            $subscription->update([
                'status' => 'active',
                'start_date' => now(),
                'transaction_id' => $transactionId ?? $subscription->transaction_id
            ]);

            DB::commit();

            Log::info('Subscription activated', [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription->user_id
            ]);

            return $subscription->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to activate subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(int $subscriptionId, string $reason = null): Subscription
    {
        try {
            DB::beginTransaction();

            $subscription = Subscription::findOrFail($subscriptionId);

            if (!in_array($subscription->status, ['active', 'trial'])) {
                throw new \InvalidArgumentException('Only active or trial subscriptions can be cancelled');
            }

            $subscription->update([
                'status' => 'cancelled',
                'auto_renew' => false,
                'cancellation_reason' => $reason,
                'cancelled_at' => now()
            ]);

            // Fire subscription cancellation event
            event(new SubscriptionCancellationEvent($subscription));

            DB::commit();

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription->user_id,
                'reason' => $reason
            ]);

            return $subscription->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Renew a subscription
     */
    public function renewSubscription(int $subscriptionId): Subscription
    {
        try {
            DB::beginTransaction();

            $subscription = Subscription::findOrFail($subscriptionId);

            if ($subscription->status !== 'active') {
                throw new \InvalidArgumentException('Only active subscriptions can be renewed');
            }

            if (!$subscription->auto_renew) {
                throw new \InvalidArgumentException('Auto-renew is disabled for this subscription');
            }

            $plan = $this->getPlan($subscription->type);
            if (!$plan) {
                throw new \InvalidArgumentException('Plan not found');
            }
            $newEndDate = Carbon::parse($subscription->end_date)->addDays($plan->duration_days);

            $subscription->update([
                'end_date' => $newEndDate
            ]);

            // Fire subscription renewal event
            event(new SubscriptionRenewalEvent($subscription));

            DB::commit();

            Log::info('Subscription renewed', [
                'subscription_id' => $subscriptionId,
                'user_id' => $subscription->user_id,
                'new_end_date' => $newEndDate
            ]);

            return $subscription->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to renew subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get active subscription for user
     */
    public function getActiveSubscription(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
                          ->where('status', 'active')
                          ->where('end_date', '>', now())
                          ->first();
    }

    /**
     * Get user's subscription history
     */
    public function getUserSubscriptions(int $userId, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Subscription::where('user_id', $userId)
                           ->orderBy('created_at', 'desc')
                           ->paginate($perPage);
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription(int $userId): bool
    {
        return $this->getActiveSubscription($userId) !== null;
    }

    /**
     * Get subscription status for user
     */
    public function getSubscriptionStatus(int $userId): array
    {
        $activeSubscription = $this->getActiveSubscription($userId);
        
        if (!$activeSubscription) {
            return [
                'has_subscription' => false,
                'status' => 'none',
                'expires_at' => null,
                'days_remaining' => 0
            ];
        }

        $daysRemaining = Carbon::parse($activeSubscription->end_date)->diffInDays(now(), false);

        return [
            'has_subscription' => true,
            'status' => $activeSubscription->status,
            'type' => $activeSubscription->type,
            'expires_at' => $activeSubscription->end_date,
            'days_remaining' => max(0, $daysRemaining),
            'auto_renew' => $activeSubscription->auto_renew,
            'plan_info' => $this->getPlan($activeSubscription->type)
        ];
    }

    /**
     * Get available subscription plans
     */
    public function getAvailablePlans(): array
    {
        return $this->getPlans();
    }

    /**
     * Calculate subscription price with discounts
     */
    public function calculatePrice(string $type, array $options = []): array
    {
        $plan = $this->getPlan($type);
        if (!$plan) {
            throw new \InvalidArgumentException('Invalid subscription plan type');
        }

        $basePrice = $plan->price;
        $discount = $plan->discount_percentage;
        $discountedPrice = $basePrice - ($basePrice * $discount / 100);

        return [
            'type' => $type,
            'name' => $plan->name,
            'base_price' => $basePrice,
            'discount_percentage' => $discount,
            'discounted_price' => $discountedPrice,
            'savings' => $basePrice - $discountedPrice,
            'currency' => $plan->currency,
            'duration_days' => $plan->duration_days,
            'description' => $plan->description
        ];
    }

    /**
     * Process subscription renewal (for cron jobs)
     */
    public function processRenewals(): array
    {
        try {
            $renewals = [];
            $expiredSubscriptions = Subscription::where('status', 'active')
                                               ->where('end_date', '<=', now())
                                               ->where('auto_renew', true)
                                               ->get();

            foreach ($expiredSubscriptions as $subscription) {
                try {
                    $this->renewSubscription($subscription->id);
                    $renewals[] = [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'status' => 'renewed'
                    ];
                } catch (\Exception $e) {
                    // Mark subscription as expired if renewal fails
                    $subscription->update(['status' => 'expired']);
                    $renewals[] = [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('Subscription renewals processed', [
                'total_processed' => count($renewals),
                'renewals' => $renewals
            ]);

            return $renewals;

        } catch (\Exception $e) {
            Log::error('Failed to process subscription renewals', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get subscription statistics
     */
    public function getSubscriptionStats(): array
    {
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $expiredSubscriptions = Subscription::where('status', 'expired')->count();
        $cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();
        $pendingSubscriptions = Subscription::where('status', 'pending')->count();

        $revenueByType = Subscription::selectRaw('type, SUM(price) as total_revenue, COUNT(*) as count')
                                   ->groupBy('type')
                                   ->get();

        $monthlyRevenue = Subscription::where('created_at', '>=', now()->subMonth())
                                   ->sum('price');

        return [
            'total_subscriptions' => $totalSubscriptions,
            'active_subscriptions' => $activeSubscriptions,
            'expired_subscriptions' => $expiredSubscriptions,
            'cancelled_subscriptions' => $cancelledSubscriptions,
            'pending_subscriptions' => $pendingSubscriptions,
            'revenue_by_type' => $revenueByType,
            'monthly_revenue' => $monthlyRevenue,
            'conversion_rate' => $totalSubscriptions > 0 ? round(($activeSubscriptions / $totalSubscriptions) * 100, 2) : 0
        ];
    }

    /**
     * Create trial subscription
     */
    public function createTrialSubscription(int $userId, int $trialDays = 7): Subscription
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($userId);

            // Check if user already had a trial
            $existingTrial = Subscription::where('user_id', $userId)
                                       ->where('status', 'trial')
                                       ->exists();

            if ($existingTrial) {
                throw new \InvalidArgumentException('User already used trial subscription');
            }

            $startDate = now();
            $endDate = $startDate->copy()->addDays($trialDays);

            $subscription = Subscription::create([
                'user_id' => $userId,
                'type' => 'monthly', // Trial uses monthly plan structure
                'status' => 'trial',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => 0, // Free trial
                'currency' => 'IRT',
                'auto_renew' => false
            ]);

            DB::commit();

            Log::info('Trial subscription created', [
                'user_id' => $userId,
                'subscription_id' => $subscription->id,
                'trial_days' => $trialDays
            ]);

            return $subscription;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create trial subscription', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Upgrade subscription
     */
    public function upgradeSubscription(int $subscriptionId, string $newType): Subscription
    {
        try {
            DB::beginTransaction();

            $subscription = Subscription::findOrFail($subscriptionId);

            if ($subscription->status !== 'active') {
                throw new \InvalidArgumentException('Only active subscriptions can be upgraded');
            }

            $newPlan = $this->getPlan($newType);
            if (!$newPlan) {
                throw new \InvalidArgumentException('Invalid subscription plan type');
            }

            $remainingDays = Carbon::parse($subscription->end_date)->diffInDays(now());
            
            // Calculate prorated price
            $currentPlan = $this->getPlan($subscription->type);
            if (!$currentPlan) {
                throw new \InvalidArgumentException('Current plan not found');
            }
            $currentDailyPrice = $currentPlan->price / $currentPlan->duration_days;
            $newDailyPrice = $newPlan->price / $newPlan->duration_days;
            
            $priceDifference = ($newDailyPrice - $currentDailyPrice) * $remainingDays;

            $subscription->update([
                'type' => $newType,
                'price' => $subscription->price + $priceDifference
            ]);

            DB::commit();

            Log::info('Subscription upgraded', [
                'subscription_id' => $subscriptionId,
                'old_type' => $subscription->type,
                'new_type' => $newType,
                'price_difference' => $priceDifference
            ]);

            return $subscription->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to upgrade subscription', [
                'subscription_id' => $subscriptionId,
                'new_type' => $newType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
