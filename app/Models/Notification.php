<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'action_type',
        'action_url',
        'action_text',
        'is_read',
        'is_important',
        'read_at',
        'expires_at',
        'priority',
        'category',
        'metadata'
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'is_read' => 'boolean',
        'is_important' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope to get important notifications
     */
    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    /**
     * Scope to get notifications by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get notifications by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get notifications by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get non-expired notifications
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get expired notifications
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to get recent notifications
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): bool
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
            return true;
        }
        return false;
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): bool
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null
            ]);
            return true;
        }
        return false;
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at <= now();
    }

    /**
     * Check if notification is urgent
     */
    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    /**
     * Check if notification is high priority
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    /**
     * Get notification type text in Persian
     */
    public function getTypeTextAttribute(): string
    {
        $types = [
            'info' => 'اطلاعات',
            'success' => 'موفقیت',
            'warning' => 'هشدار',
            'error' => 'خطا',
            'subscription' => 'اشتراک',
            'payment' => 'پرداخت',
            'content' => 'محتوا',
            'system' => 'سیستم'
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * Get priority text in Persian
     */
    public function getPriorityTextAttribute(): string
    {
        $priorities = [
            'low' => 'کم',
            'normal' => 'عادی',
            'high' => 'بالا',
            'urgent' => 'فوری'
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    /**
     * Get category text in Persian
     */
    public function getCategoryTextAttribute(): string
    {
        $categories = [
            'subscription' => 'اشتراک',
            'payment' => 'پرداخت',
            'content' => 'محتوا',
            'system' => 'سیستم',
            'security' => 'امنیت',
            'promotion' => 'تبلیغات'
        ];

        return $categories[$this->category] ?? $this->category ?? 'عمومی';
    }

    /**
     * Get time ago text
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get notification summary
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'action_type' => $this->action_type,
            'action_url' => $this->action_url,
            'action_text' => $this->action_text,
            'is_read' => $this->is_read,
            'is_important' => $this->is_important,
            'read_at' => $this->read_at,
            'expires_at' => $this->expires_at,
            'priority' => $this->priority,
            'priority_text' => $this->priority_text,
            'category' => $this->category,
            'category_text' => $this->category_text,
            'metadata' => $this->metadata,
            'time_ago' => $this->time_ago,
            'is_expired' => $this->isExpired(),
            'is_urgent' => $this->isUrgent(),
            'is_high_priority' => $this->isHighPriority(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    /**
     * Create a new notification
     */
    public static function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $options['data'] ?? null,
            'action_type' => $options['action_type'] ?? null,
            'action_url' => $options['action_url'] ?? null,
            'action_text' => $options['action_text'] ?? null,
            'is_important' => $options['is_important'] ?? false,
            'expires_at' => $options['expires_at'] ?? null,
            'priority' => $options['priority'] ?? 'normal',
            'category' => $options['category'] ?? null,
            'metadata' => $options['metadata'] ?? null
        ]);
    }

    /**
     * Create subscription notification
     */
    public static function createSubscriptionNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): self {
        return self::createNotification($userId, 'subscription', $title, $message, array_merge($options, [
            'category' => 'subscription',
            'priority' => 'high'
        ]));
    }

    /**
     * Create payment notification
     */
    public static function createPaymentNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): self {
        return self::createNotification($userId, 'payment', $title, $message, array_merge($options, [
            'category' => 'payment',
            'priority' => 'high'
        ]));
    }

    /**
     * Create content notification
     */
    public static function createContentNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): self {
        return self::createNotification($userId, 'content', $title, $message, array_merge($options, [
            'category' => 'content',
            'priority' => 'normal'
        ]));
    }

    /**
     * Create system notification
     */
    public static function createSystemNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): self {
        return self::createNotification($userId, 'system', $title, $message, array_merge($options, [
            'category' => 'system',
            'priority' => $options['priority'] ?? 'normal'
        ]));
    }

    /**
     * Get notification statistics
     */
    public static function getStatistics(): array
    {
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');

        return [
            'today' => [
                'total' => self::whereDate('created_at', $today)->count(),
                'unread' => self::whereDate('created_at', $today)->where('is_read', false)->count(),
                'read' => self::whereDate('created_at', $today)->where('is_read', true)->count(),
                'important' => self::whereDate('created_at', $today)->where('is_important', true)->count()
            ],
            'this_month' => [
                'total' => self::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'unread' => self::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('is_read', false)->count(),
                'read' => self::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('is_read', true)->count(),
                'important' => self::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('is_important', true)->count()
            ],
            'by_type' => self::selectRaw('type, COUNT(*) as count')
                            ->groupBy('type')
                            ->get(),
            'by_category' => self::selectRaw('category, COUNT(*) as count')
                               ->groupBy('category')
                               ->get(),
            'by_priority' => self::selectRaw('priority, COUNT(*) as count')
                               ->groupBy('priority')
                               ->get()
        ];
    }

    /**
     * Clean up expired notifications
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<=', now())->delete();
    }

    /**
     * Mark all notifications as read for user
     */
    public static function markAllAsRead(int $userId): int
    {
        return self::where('user_id', $userId)
                  ->where('is_read', false)
                  ->update([
                      'is_read' => true,
                      'read_at' => now()
                  ]);
    }

    /**
     * Get unread count for user
     */
    public static function getUnreadCount(int $userId): int
    {
        return self::where('user_id', $userId)
                  ->where('is_read', false)
                  ->notExpired()
                  ->count();
    }
}