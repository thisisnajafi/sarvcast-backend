<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'message',
        'template_key',
        'variables',
        'provider',
        'status',
        'message_id',
        'error_message',
        'error_code',
        'sent_at',
        'delivered_at',
        'response_data'
    ];

    protected $casts = [
        'variables' => 'array',
        'response_data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Scope to get sent SMS
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get failed SMS
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get SMS by provider
     */
    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get SMS by template
     */
    public function scopeByTemplate($query, $templateKey)
    {
        return $query->where('template_key', $templateKey);
    }

    /**
     * Scope to get SMS by phone number
     */
    public function scopeByPhoneNumber($query, $phoneNumber)
    {
        return $query->where('phone_number', $phoneNumber);
    }

    /**
     * Scope to get SMS by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's SMS
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get this month's SMS
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    /**
     * Check if SMS was sent successfully
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if SMS failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if SMS was delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneNumberAttribute(): string
    {
        return $this->phone_number;
    }

    /**
     * Get provider name in Persian
     */
    public function getProviderNameAttribute(): string
    {
        $providers = [
            'kavenegar' => 'کاوه‌نگار',
            'melipayamak' => 'ملی‌پیامک',
            'smsir' => 'پیامک آی‌آر'
        ];

        return $providers[$this->provider] ?? $this->provider;
    }

    /**
     * Get status text in Persian
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'pending' => 'در انتظار',
            'sent' => 'ارسال شده',
            'failed' => 'ناموفق',
            'delivered' => 'تحویل شده'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get template name
     */
    public function getTemplateNameAttribute(): string
    {
        if (!$this->template_key) {
            return 'پیامک معمولی';
        }

        $templates = [
            'verification' => 'کد تایید',
            'welcome' => 'خوش‌آمدگویی',
            'subscription_activated' => 'فعال‌سازی اشتراک',
            'subscription_expiring' => 'انقضای اشتراک',
            'subscription_expired' => 'انقضای اشتراک',
            'payment_success' => 'پرداخت موفق',
            'payment_failed' => 'پرداخت ناموفق',
            'new_episode' => 'قسمت جدید',
            'new_story' => 'داستان جدید',
            'password_reset' => 'بازیابی رمز عبور'
        ];

        return $templates[$this->template_key] ?? $this->template_key;
    }

    /**
     * Get delivery time in seconds
     */
    public function getDeliveryTimeAttribute(): ?int
    {
        if ($this->sent_at && $this->delivered_at) {
            return $this->sent_at->diffInSeconds($this->delivered_at);
        }

        return null;
    }

    /**
     * Get SMS summary
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'phone_number' => $this->phone_number,
            'formatted_phone_number' => $this->formatted_phone_number,
            'message' => $this->message,
            'template_key' => $this->template_key,
            'template_name' => $this->template_name,
            'variables' => $this->variables,
            'provider' => $this->provider,
            'provider_name' => $this->provider_name,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'message_id' => $this->message_id,
            'error_message' => $this->error_message,
            'error_code' => $this->error_code,
            'sent_at' => $this->sent_at,
            'delivered_at' => $this->delivered_at,
            'delivery_time' => $this->delivery_time,
            'response_data' => $this->response_data,
            'is_sent' => $this->isSent(),
            'is_failed' => $this->isFailed(),
            'is_delivered' => $this->isDelivered(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Get SMS statistics
     */
    public static function getStatistics(): array
    {
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

        return [
            'today' => [
                'total' => self::whereDate('created_at', $today)->count(),
                'sent' => self::whereDate('created_at', $today)->where('status', 'sent')->count(),
                'failed' => self::whereDate('created_at', $today)->where('status', 'failed')->count(),
                'delivered' => self::whereDate('created_at', $today)->where('status', 'delivered')->count()
            ],
            'this_month' => [
                'total' => self::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'sent' => self::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'sent')->count(),
                'failed' => self::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'failed')->count(),
                'delivered' => self::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'delivered')->count()
            ],
            'last_month' => [
                'total' => self::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count(),
                'sent' => self::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->where('status', 'sent')->count(),
                'failed' => self::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->where('status', 'failed')->count(),
                'delivered' => self::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->where('status', 'delivered')->count()
            ],
            'providers' => self::selectRaw('provider, COUNT(*) as count, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count')
                              ->groupBy('provider')
                              ->get(),
            'templates' => self::selectRaw('template_key, COUNT(*) as count, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count')
                              ->whereNotNull('template_key')
                              ->groupBy('template_key')
                              ->get()
        ];
    }
}
