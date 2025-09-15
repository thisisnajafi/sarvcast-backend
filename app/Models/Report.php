<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'content_type',
        'content_id',
        'type',
        'description',
        'evidence',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution',
        'action_taken',
        'priority',
        'is_anonymous',
        'metadata'
    ];

    protected $casts = [
        'evidence' => 'array',
        'metadata' => 'array',
        'resolved_at' => 'datetime',
        'is_anonymous' => 'boolean'
    ];

    /**
     * Get the reporter (user who made the report)
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the moderator who resolved the report
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the reported content (polymorphic)
     */
    public function content(): MorphTo
    {
        return $this->morphTo('content', 'content_type', 'content_id');
    }

    /**
     * Scope for pending reports
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for resolved reports
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for high priority reports
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 3);
    }

    /**
     * Scope for reports by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for reports by content type
     */
    public function scopeByContentType($query, $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    /**
     * Scope for recent reports
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            1 => 'کم',
            2 => 'متوسط',
            3 => 'بالا',
            4 => 'فوری',
            default => 'نامشخص'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'در انتظار',
            'investigating' => 'در حال بررسی',
            'resolved' => 'حل شده',
            'dismissed' => 'رد شده',
            default => 'نامشخص'
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'inappropriate_content' => 'محتوای نامناسب',
            'spam' => 'اسپم',
            'harassment' => 'آزار و اذیت',
            'copyright' => 'نقض کپی‌رایت',
            'violence' => 'خشونت',
            'sexual_content' => 'محتوای جنسی',
            'hate_speech' => 'سخن نفرت‌آمیز',
            'fake_content' => 'محتوای جعلی',
            'other' => 'سایر',
            default => 'نامشخص'
        };
    }

    /**
     * Get action taken label
     */
    public function getActionTakenLabelAttribute(): string
    {
        return match($this->action_taken) {
            'no_action' => 'هیچ اقدامی انجام نشد',
            'warning' => 'هشدار به کاربر',
            'remove_content' => 'حذف محتوا',
            'suspend_user' => 'معلق کردن کاربر',
            'ban_user' => 'مسدود کردن کاربر',
            'other' => 'سایر اقدامات',
            default => 'نامشخص'
        };
    }

    /**
     * Check if report is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if report is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if report is high priority
     */
    public function isHighPriority(): bool
    {
        return $this->priority >= 3;
    }

    /**
     * Get time since report was created
     */
    public function getTimeSinceCreatedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get time since report was resolved
     */
    public function getTimeSinceResolvedAttribute(): ?string
    {
        return $this->resolved_at ? $this->resolved_at->diffForHumans() : null;
    }

    /**
     * Get resolution time in hours
     */
    public function getResolutionTimeAttribute(): ?float
    {
        if (!$this->resolved_at) {
            return null;
        }

        return round($this->created_at->diffInHours($this->resolved_at), 2);
    }

    /**
     * Mark report as resolved
     */
    public function markAsResolved(User $resolver, string $resolution, string $actionTaken): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
            'resolution' => $resolution,
            'action_taken' => $actionTaken
        ]);
    }

    /**
     * Mark report as dismissed
     */
    public function markAsDismissed(User $resolver, string $reason): bool
    {
        return $this->update([
            'status' => 'dismissed',
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
            'resolution' => $reason,
            'action_taken' => 'no_action'
        ]);
    }

    /**
     * Get report statistics
     */
    public static function getStatistics(): array
    {
        return [
            'total_reports' => self::count(),
            'pending_reports' => self::pending()->count(),
            'resolved_reports' => self::resolved()->count(),
            'high_priority_reports' => self::highPriority()->count(),
            'reports_by_type' => self::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'reports_by_status' => self::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'reports_by_content_type' => self::selectRaw('content_type, COUNT(*) as count')
                ->groupBy('content_type')
                ->get(),
            'avg_resolution_time' => self::resolved()
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
            'recent_reports' => self::recent(7)->count(),
            'reports_trend' => self::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
        ];
    }
}