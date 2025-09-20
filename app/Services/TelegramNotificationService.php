<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Coupon;
use App\Models\AffiliateCommission;

class TelegramNotificationService
{
    protected $botToken;
    protected $chatId;
    protected $baseUrl;

    public function __construct()
    {
        $this->botToken = '7488407974:AAFl4Ek9IanbvlkKlRoikQAqdkDtFYbD0Gc';
        $this->chatId = '-1003099647147';
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send a message to Telegram
     */
    public function sendMessage(string $message, array $options = []): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
                ...$options
            ]);

            if ($response->successful()) {
                Log::info('Telegram message sent successfully', [
                    'chat_id' => $this->chatId,
                    'message_length' => strlen($message)
                ]);
                return true;
            } else {
                Log::error('Failed to send Telegram message', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Telegram notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send sales notification
     */
    public function sendSalesNotification(Payment $payment, Subscription $subscription = null): bool
    {
        $user = $payment->user;
        $coupon = $payment->coupon;
        
        $message = "🛒 <b>فروش جدید!</b>\n\n";
        $message .= "👤 <b>مشتری:</b> {$user->first_name} {$user->last_name}\n";
        $message .= "📧 <b>ایمیل:</b> {$user->email}\n";
        $message .= "📱 <b>تلفن:</b> {$user->phone_number}\n\n";
        
        $message .= "💰 <b>جزئیات پرداخت:</b>\n";
        $message .= "• مبلغ: " . number_format($payment->amount) . " تومان\n";
        $message .= "• روش پرداخت: {$payment->payment_method}\n";
        $message .= "• وضعیت: {$this->getPaymentStatusText($payment->status)}\n";
        $message .= "• تاریخ: " . $this->formatJalaliDate($payment->created_at) . "\n\n";
        
        if ($subscription) {
            $planName = \App\Services\SubscriptionService::PLANS[$subscription->type]['name'] ?? $subscription->type;
            $message .= "📋 <b>اشتراک:</b>\n";
            $message .= "• نوع: {$planName}\n";
            $message .= "• مدت: " . (\App\Services\SubscriptionService::PLANS[$subscription->type]['duration_days'] ?? 'نامشخص') . " روز\n";
            $message .= "• قیمت: " . number_format($subscription->price) . " تومان\n\n";
        }
        
        if ($coupon) {
            $message .= "🎫 <b>کوپن استفاده شده:</b>\n";
            $message .= "• کد: {$coupon->code}\n";
            $message .= "• نوع: {$this->getCouponTypeText($coupon->type)}\n";
            $message .= "• مقدار: {$coupon->value}" . ($coupon->type === 'percentage' ? '%' : ' تومان') . "\n";
            $message .= "• تخفیف اعمال شده: " . number_format($payment->discount_amount ?? 0) . " تومان\n\n";
        } else {
            $message .= "🎫 <b>کوپن:</b> استفاده نشده\n\n";
        }
        
        $message .= "📊 <b>آمار:</b>\n";
        $message .= "• تعداد کل پرداخت‌های کاربر: " . $user->payments()->where('status', 'completed')->count() . "\n";
        $message .= "• مجموع پرداخت‌های کاربر: " . number_format($user->payments()->where('status', 'completed')->sum('amount')) . " تومان\n";
        
        return $this->sendMessage($message);
    }

    /**
     * Send influencer commission notification
     */
    public function sendInfluencerCommissionNotification(AffiliateCommission $commission): bool
    {
        $influencer = $commission->affiliate;
        $user = $commission->user;
        $payment = $commission->payment;
        
        $message = "💸 <b>کمیسیون اینفلوئنسر!</b>\n\n";
        $message .= "👤 <b>اینفلوئنسر:</b> {$influencer->first_name} {$influencer->last_name}\n";
        $message .= "📧 <b>ایمیل:</b> {$influencer->email}\n";
        $message .= "📱 <b>تلفن:</b> {$influencer->phone_number}\n\n";
        
        $message .= "🛒 <b>مشتری معرفی شده:</b>\n";
        $message .= "• نام: {$user->first_name} {$user->last_name}\n";
        $message .= "• ایمیل: {$user->email}\n";
        $message .= "• تلفن: {$user->phone_number}\n\n";
        
        $message .= "💰 <b>جزئیات کمیسیون:</b>\n";
        $message .= "• مبلغ پرداخت: " . number_format($payment->amount) . " تومان\n";
        $message .= "• نرخ کمیسیون: {$commission->rate}%\n";
        $message .= "• مبلغ کمیسیون: " . number_format($commission->amount) . " تومان\n";
        $message .= "• وضعیت: {$this->getCommissionStatusText($commission->status)}\n";
        $message .= "• تاریخ: " . $this->formatJalaliDate($commission->created_at) . "\n\n";
        
        $message .= "📊 <b>آمار اینفلوئنسر:</b>\n";
        $message .= "• تعداد کل کمیسیون‌ها: " . $influencer->commissions()->count() . "\n";
        $message .= "• مجموع کمیسیون‌ها: " . number_format($influencer->commissions()->sum('commission_amount')) . " تومان\n";
        
        return $this->sendMessage($message);
    }

    /**
     * Send new user registration notification
     */
    public function sendNewUserNotification(User $user): bool
    {
        $message = "👋 <b>کاربر جدید!</b>\n\n";
        $message .= "👤 <b>نام:</b> {$user->first_name} {$user->last_name}\n";
        $message .= "📧 <b>ایمیل:</b> {$user->email}\n";
        $message .= "📱 <b>تلفن:</b> {$user->phone_number}\n";
        $message .= "👶 <b>نوع کاربر:</b> {$this->getUserRoleText($user->role)}\n";
        $message .= "📅 <b>تاریخ ثبت‌نام:</b> " . $this->formatJalaliDate($user->created_at) . "\n\n";
        
        if ($user->parent_id) {
            $parent = $user->parent;
            $message .= "👨‍👩‍👧‍👦 <b>والد:</b> {$parent->first_name} {$parent->last_name}\n";
        }
        
        $message .= "📊 <b>آمار کل:</b>\n";
        $message .= "• تعداد کل کاربران: " . User::count() . "\n";
        $message .= "• کاربران امروز: " . User::whereDate('created_at', today())->count() . "\n";
        
        return $this->sendMessage($message);
    }

    /**
     * Send subscription renewal notification
     */
    public function sendSubscriptionRenewalNotification(Subscription $subscription): bool
    {
        $user = $subscription->user;
        $planName = \App\Services\SubscriptionService::PLANS[$subscription->type]['name'] ?? $subscription->type;
        
        $message = "🔄 <b>تمدید اشتراک!</b>\n\n";
        $message .= "👤 <b>کاربر:</b> {$user->first_name} {$user->last_name}\n";
        $message .= "📧 <b>ایمیل:</b> {$user->email}\n\n";
        
        $message .= "📋 <b>اشتراک:</b>\n";
        $message .= "• نوع: {$planName}\n";
        $message .= "• مدت: " . (\App\Services\SubscriptionService::PLANS[$subscription->type]['duration_days'] ?? 'نامشخص') . " روز\n";
        $message .= "• قیمت: " . number_format($subscription->price) . " تومان\n";
        $message .= "• تاریخ شروع: " . $this->formatJalaliDate($subscription->start_date) . "\n";
        $message .= "• تاریخ پایان: " . $this->formatJalaliDate($subscription->end_date) . "\n";
        $message .= "• تمدید خودکار: " . ($subscription->auto_renew ? 'فعال' : 'غیرفعال') . "\n\n";
        
        $message .= "📊 <b>آمار کاربر:</b>\n";
        $message .= "• تعداد اشتراک‌ها: " . $user->subscriptions()->count() . "\n";
        $message .= "• مجموع پرداخت‌ها: " . number_format($user->payments()->where('status', 'completed')->sum('amount')) . " تومان\n";
        
        return $this->sendMessage($message);
    }

    /**
     * Send subscription cancellation notification
     */
    public function sendSubscriptionCancellationNotification(Subscription $subscription): bool
    {
        $user = $subscription->user;
        $planName = \App\Services\SubscriptionService::PLANS[$subscription->type]['name'] ?? $subscription->type;
        
        $message = "❌ <b>لغو اشتراک!</b>\n\n";
        $message .= "👤 <b>کاربر:</b> {$user->first_name} {$user->last_name}\n";
        $message .= "📧 <b>ایمیل:</b> {$user->email}\n\n";
        
        $message .= "📋 <b>اشتراک لغو شده:</b>\n";
        $message .= "• نوع: {$planName}\n";
        $message .= "• قیمت: " . number_format($subscription->price) . " تومان\n";
        $message .= "• تاریخ لغو: " . $this->formatJalaliDate($subscription->cancelled_at) . "\n";
        
        if ($subscription->cancellation_reason) {
            $message .= "• دلیل لغو: {$subscription->cancellation_reason}\n";
        }
        
        $message .= "\n📊 <b>آمار کاربر:</b>\n";
        $message .= "• تعداد اشتراک‌های لغو شده: " . $user->subscriptions()->where('status', 'cancelled')->count() . "\n";
        $message .= "• مجموع پرداخت‌ها: " . number_format($user->payments()->where('status', 'completed')->sum('amount')) . " تومان\n";
        
        return $this->sendMessage($message);
    }

    /**
     * Send daily sales summary
     */
    public function sendDailySalesSummary(): bool
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
        
        $message = "📊 <b>خلاصه فروش روزانه</b>\n\n";
        $message .= "📅 <b>امروز ({$this->formatJalaliDate($today)}):</b>\n";
        $message .= "• تعداد فروش: " . $todaySales->count() . "\n";
        $message .= "• مجموع درآمد: " . number_format($todayAmount) . " تومان\n\n";
        
        $message .= "📅 <b>دیروز ({$this->formatJalaliDate($yesterday)}):</b>\n";
        $message .= "• تعداد فروش: " . $yesterdaySales->count() . "\n";
        $message .= "• مجموع درآمد: " . number_format($yesterdayAmount) . " تومان\n\n";
        
        $growth = $yesterdayAmount > 0 ? (($todayAmount - $yesterdayAmount) / $yesterdayAmount) * 100 : 0;
        $growthIcon = $growth > 0 ? '📈' : ($growth < 0 ? '📉' : '➡️');
        $message .= "{$growthIcon} <b>رشد:</b> " . number_format($growth, 1) . "%\n\n";
        
        $message .= "📋 <b>جزئیات فروش امروز:</b>\n";
        foreach ($todaySales->take(5) as $sale) {
            $user = $sale->user;
            $message .= "• {$user->first_name} {$user->last_name}: " . number_format($sale->amount) . " تومان\n";
        }
        
        if ($todaySales->count() > 5) {
            $message .= "• و " . ($todaySales->count() - 5) . " فروش دیگر...\n";
        }
        
        return $this->sendMessage($message);
    }

    /**
     * Send system alert
     */
    public function sendSystemAlert(string $title, string $message, string $level = 'info'): bool
    {
        $icons = [
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'error' => '🚨',
            'success' => '✅'
        ];
        
        $icon = $icons[$level] ?? 'ℹ️';
        
        $alertMessage = "{$icon} <b>{$title}</b>\n\n";
        $alertMessage .= $message . "\n\n";
        $alertMessage .= "🕐 <b>زمان:</b> " . $this->formatJalaliDate(now()) . "\n";
        
        return $this->sendMessage($alertMessage);
    }

    /**
     * Helper methods
     */
    private function getPaymentStatusText(string $status): string
    {
        $statuses = [
            'pending' => 'در انتظار',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده'
        ];
        
        return $statuses[$status] ?? $status;
    }

    private function getCouponTypeText(string $type): string
    {
        $types = [
            'percentage' => 'درصدی',
            'fixed' => 'مبلغ ثابت'
        ];
        
        return $types[$type] ?? $type;
    }

    private function getCommissionStatusText(string $status): string
    {
        $statuses = [
            'pending' => 'در انتظار',
            'paid' => 'پرداخت شده',
            'cancelled' => 'لغو شده'
        ];
        
        return $statuses[$status] ?? $status;
    }

    private function getUserRoleText(string $role): string
    {
        $roles = [
            'parent' => 'والد',
            'child' => 'کودک',
            'admin' => 'مدیر'
        ];
        
        return $roles[$role] ?? $role;
    }

    private function formatJalaliDate($date): string
    {
        return \App\Helpers\JalaliHelper::formatForDisplay($date, 'Y/m/d H:i');
    }

    /**
     * Test the Telegram connection
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::get("{$this->baseUrl}/getMe");
            
            if ($response->successful()) {
                $botInfo = $response->json();
                Log::info('Telegram bot connection successful', $botInfo);
                return true;
            } else {
                Log::error('Telegram bot connection failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Telegram connection test error: ' . $e->getMessage());
            return false;
        }
    }
}
