<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AdminPushNotificationService
{
    public function __construct(
        protected InAppNotificationService $inAppNotificationService
    ) {}

    /**
     * Send a push + in-app alert to all active admins.
     */
    public function notifyAdmins(string $title, string $message, array $options = []): int
    {
        $admins = User::query()
            ->admins()
            ->where('is_active', true)
            ->get();

        if ($admins->isEmpty()) {
            Log::warning('Admin push skipped: no active admin users found');
            return 0;
        }

        $sent = 0;

        foreach ($admins as $admin) {
            try {
                $this->inAppNotificationService->createNotification(
                    $admin->id,
                    $options['type'] ?? 'system',
                    $title,
                    $message,
                    array_merge([
                        'category' => 'system',
                        'priority' => $options['priority'] ?? 'high',
                        'is_important' => true,
                        'send_push' => true,
                        'data' => array_merge(
                            ['type' => 'admin_alert', 'admin_alert' => true],
                            $options['data'] ?? []
                        ),
                    ], $options)
                );
                $sent++;
            } catch (\Exception $e) {
                Log::error('Failed to send admin push notification', [
                    'admin_id' => $admin->id,
                    'title' => $title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    public function sendSalesNotification(Payment $payment, ?Subscription $subscription = null): int
    {
        $user = $payment->user;
        $coupon = $payment->coupon;

        $message = "فروش جدید\n\n";
        $message .= "مشتری: {$user->first_name} {$user->last_name}\n";
        $message .= "تلفن: {$user->phone_number}\n";
        $message .= "شناسه کاربر: {$user->id}\n\n";
        $message .= "مبلغ: " . number_format($payment->amount) . " ریال\n";
        $message .= "روش پرداخت: {$payment->payment_method}\n";
        $message .= "وضعیت: {$this->getPaymentStatusText($payment->status)}\n";
        $message .= "تاریخ: " . $this->formatJalaliDate($payment->created_at) . "\n";

        if ($subscription) {
            $subscriptionService = app(SubscriptionService::class);
            $plans = $subscriptionService->getPlans();
            $planName = $plans[$subscription->type]['name'] ?? $subscription->type;
            $message .= "\nاشتراک: {$planName}";
        }

        if ($coupon) {
            $message .= "\nکوپن: {$coupon->code}";
        }

        return $this->notifyAdmins('فروش جدید', $message, [
            'type' => 'success',
            'priority' => 'high',
            'data' => [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'alert_kind' => 'sale',
            ],
        ]);
    }

    public function sendInfluencerCommissionNotification(AffiliateCommission $commission): int
    {
        $influencer = $commission->affiliate;
        $user = $commission->user;
        $payment = $commission->payment;

        $message = "کمیسیون اینفلوئنسر\n\n";
        $message .= "اینفلوئنسر: {$influencer->first_name} {$influencer->last_name}\n";
        $message .= "مشتری: {$user->first_name} {$user->last_name}\n";
        $message .= "مبلغ پرداخت: " . number_format($payment->amount) . " ریال\n";
        $message .= "کمیسیون: " . number_format($commission->amount) . " ریال\n";
        $message .= "تاریخ: " . $this->formatJalaliDate($commission->created_at);

        return $this->notifyAdmins('کمیسیون اینفلوئنسر', $message, [
            'type' => 'info',
            'data' => [
                'commission_id' => $commission->id,
                'alert_kind' => 'commission',
            ],
        ]);
    }

    public function sendNewUserNotification(User $user): int
    {
        $message = "کاربر جدید\n\n";
        $message .= "نام: {$user->first_name} {$user->last_name}\n";
        $message .= "تلفن: {$user->phone_number}\n";
        $message .= "نقش: {$this->getUserRoleText($user->role)}\n";
        $message .= "تاریخ ثبت‌نام: " . $this->formatJalaliDate($user->created_at) . "\n";
        $message .= "کل کاربران: " . User::count();

        return $this->notifyAdmins('کاربر جدید', $message, [
            'type' => 'info',
            'data' => [
                'user_id' => $user->id,
                'alert_kind' => 'registration',
            ],
        ]);
    }

    public function sendSubscriptionRenewalNotification(Subscription $subscription): int
    {
        $user = $subscription->user;
        $subscriptionService = app(SubscriptionService::class);
        $plans = $subscriptionService->getPlans();
        $planName = $plans[$subscription->type]['name'] ?? $subscription->type;

        $message = "تمدید اشتراک\n\n";
        $message .= "کاربر: {$user->first_name} {$user->last_name}\n";
        $message .= "تلفن: {$user->phone_number}\n";
        $message .= "اشتراک: {$planName}\n";
        $message .= "قیمت: " . number_format($subscription->price) . " ریال\n";
        $message .= "پایان: " . $this->formatJalaliDate($subscription->end_date);

        return $this->notifyAdmins('تمدید اشتراک', $message, [
            'type' => 'success',
            'data' => [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'alert_kind' => 'renewal',
            ],
        ]);
    }

    public function sendSubscriptionCancellationNotification(Subscription $subscription): int
    {
        $user = $subscription->user;
        $subscriptionService = app(SubscriptionService::class);
        $plans = $subscriptionService->getPlans();
        $planName = $plans[$subscription->type]['name'] ?? $subscription->type;

        $message = "لغو اشتراک\n\n";
        $message .= "کاربر: {$user->first_name} {$user->last_name}\n";
        $message .= "تلفن: {$user->phone_number}\n";
        $message .= "اشتراک: {$planName}\n";
        $message .= "تاریخ لغو: " . $this->formatJalaliDate($subscription->cancelled_at ?? now());

        if ($subscription->cancellation_reason) {
            $message .= "\nدلیل: {$subscription->cancellation_reason}";
        }

        return $this->notifyAdmins('لغو اشتراک', $message, [
            'type' => 'warning',
            'data' => [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'alert_kind' => 'cancellation',
            ],
        ]);
    }

    public function sendContactFormNotification(string $name, string $email, ?string $phone, string $body): int
    {
        $message = "پیام جدید از فرم تماس\n\n";
        $message .= "نام: {$name}\n";
        $message .= "ایمیل: {$email}\n";
        $message .= "تلفن: " . ($phone ?: 'ارائه نشده') . "\n\n";
        $message .= "پیام:\n{$body}\n\n";
        $message .= "زمان: " . $this->formatJalaliDate(now());

        return $this->notifyAdmins('پیام تماس جدید', $message, [
            'type' => 'info',
            'priority' => 'urgent',
            'data' => ['alert_kind' => 'contact'],
        ]);
    }

    public function sendDailySalesSummary(): int
    {
        $today = today();
        $yesterday = today()->subDay();

        $todaySales = Payment::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->get();

        $yesterdaySales = Payment::whereDate('created_at', $yesterday)
            ->where('status', 'completed')
            ->get();

        $todayAmount = $todaySales->sum('amount');
        $yesterdayAmount = $yesterdaySales->sum('amount');
        $growth = $yesterdayAmount > 0 ? (($todayAmount - $yesterdayAmount) / $yesterdayAmount) * 100 : 0;

        $message = "خلاصه فروش روزانه\n\n";
        $message .= "امروز ({$this->formatJalaliDate($today)}):\n";
        $message .= "تعداد فروش: {$todaySales->count()}\n";
        $message .= "درآمد: " . number_format($todayAmount) . " ریال\n\n";
        $message .= "دیروز ({$this->formatJalaliDate($yesterday)}):\n";
        $message .= "تعداد فروش: {$yesterdaySales->count()}\n";
        $message .= "درآمد: " . number_format($yesterdayAmount) . " ریال\n\n";
        $message .= "رشد: " . number_format($growth, 1) . "%\n";
        $message .= "کل کاربران: " . number_format(User::count());

        return $this->notifyAdmins('خلاصه فروش روزانه', $message, [
            'type' => 'info',
            'data' => ['alert_kind' => 'daily_sales'],
        ]);
    }

    private function getPaymentStatusText(string $status): string
    {
        return match ($status) {
            'pending' => 'در انتظار',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده',
            default => $status,
        };
    }

    private function getUserRoleText(string $role): string
    {
        return match ($role) {
            'parent' => 'والد',
            'child' => 'کودک',
            'admin' => 'مدیر',
            'super_admin' => 'مدیر ارشد',
            default => $role,
        };
    }

    private function formatJalaliDate($date): string
    {
        return \App\Helpers\JalaliHelper::formatForDisplay($date, 'Y/m/d H:i');
    }
}
