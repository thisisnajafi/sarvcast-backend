<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Subscription;
use App\Services\InAppNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionNotifications extends Command
{
    protected $signature = 'notifications:check-subscriptions';

    protected $description = 'Send subscription expiring and expired push notifications';

    public function __construct(
        protected InAppNotificationService $inAppNotificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $daysBefore = (int) config('notification.subscription_expiring_days', 3);
        $expiringCount = $this->notifyExpiringSubscriptions($daysBefore);
        $expiredCount = $this->notifyExpiredSubscriptions();

        $this->info("Expiring reminders sent: {$expiringCount}");
        $this->info("Expired notifications sent: {$expiredCount}");

        return self::SUCCESS;
    }

    protected function notifyExpiringSubscriptions(int $daysBefore): int
    {
        $count = 0;

        $subscriptions = Subscription::active()
            ->whereBetween('end_date', [now(), now()->addDays($daysBefore)])
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            if (!$user) {
                continue;
            }

            $daysRemaining = max(1, (int) now()->diffInDays($subscription->end_date, false));

            $alreadyNotified = Notification::where('user_id', $user->id)
                ->where('category', 'subscription')
                ->where('title', 'اشتراک در حال انقضا')
                ->where('created_at', '>=', now()->subDays($daysBefore))
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            try {
                $this->inAppNotificationService->createSubscriptionExpiringNotification(
                    $user->id,
                    $daysRemaining
                );
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to send expiring subscription notification', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function notifyExpiredSubscriptions(): int
    {
        $count = 0;

        $subscriptions = Subscription::where('status', 'active')
            ->where('end_date', '<=', now())
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            if (!$user) {
                continue;
            }

            $alreadyNotified = Notification::where('user_id', $user->id)
                ->where('category', 'subscription')
                ->where('title', 'اشتراک منقضی شد')
                ->where('created_at', '>=', $subscription->end_date)
                ->exists();

            $subscription->update(['status' => 'expired']);

            if ($alreadyNotified) {
                continue;
            }

            try {
                $this->inAppNotificationService->createSubscriptionExpiredNotification($user->id);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to send expired subscription notification', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
