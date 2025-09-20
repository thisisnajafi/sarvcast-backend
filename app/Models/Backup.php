<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'include_files',
        'exclude_files',
        'compression',
        'encryption',
        'schedule',
        'status',
        'file_path',
        'size',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'include_files' => 'array',
        'exclude_files' => 'array',
        'compression' => 'boolean',
        'encryption' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user who created this backup
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for completed backups
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed backups
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for in progress backups
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for pending backups
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for backups by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) {
            return 'نامشخص';
        }

        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get duration of backup process
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        $start = Carbon::parse($this->started_at);
        $end = Carbon::parse($this->completed_at);
        
        return $start->diffForHumans($end, true);
    }

    /**
     * Check if backup is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->file_path && $this->size > 0;
    }

    /**
     * Check if backup is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if backup failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if backup is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get status label in Persian
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'در انتظار',
            'in_progress' => 'در حال انجام',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
            'cancelled' => 'لغو شده',
            default => 'نامشخص'
        };
    }

    /**
     * Get type label in Persian
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'database' => 'پایگاه داده',
            'files' => 'فایل‌ها',
            'config' => 'تنظیمات',
            'full' => 'کامل',
            default => 'نامشخص'
        };
    }

    /**
     * Get status color class for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Get type color class for UI
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'database' => 'bg-blue-100 text-blue-800',
            'files' => 'bg-purple-100 text-purple-800',
            'config' => 'bg-orange-100 text-orange-800',
            'full' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}