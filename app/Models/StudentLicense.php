<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_account_id',
        'student_user_id',
        'license_type',
        'original_price',
        'discounted_price',
        'discount_amount',
        'start_date',
        'end_date',
        'status',
        'activated_at',
        'expired_at',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'activated_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /**
     * Get the teacher account that owns the license
     */
    public function teacherAccount(): BelongsTo
    {
        return $this->belongsTo(TeacherAccount::class);
    }

    /**
     * Get the student user that owns the license
     */
    public function studentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    /**
     * Activate the license
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    /**
     * Expire the license
     */
    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
            'expired_at' => now(),
        ]);
    }

    /**
     * Cancel the license
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if license is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    /**
     * Check if license is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Get license duration in days
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get remaining days
     */
    public function getRemainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->end_date));
    }

    /**
     * Scope to get active licenses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Scope to get expired licenses
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope to get licenses by teacher
     */
    public function scopeByTeacher($query, int $teacherAccountId)
    {
        return $query->where('teacher_account_id', $teacherAccountId);
    }

    /**
     * Scope to get licenses by student
     */
    public function scopeByStudent($query, int $studentUserId)
    {
        return $query->where('student_user_id', $studentUserId);
    }

    /**
     * Scope to get licenses expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '<=', now()->addDays($days))
                    ->where('end_date', '>=', now());
    }

    /**
     * Get license types
     */
    public static function getLicenseTypes(): array
    {
        return [
            'individual' => 'فردی',
            'bulk' => 'گروهی',
        ];
    }

    /**
     * Get license statistics for teacher
     */
    public static function getTeacherStatistics(int $teacherAccountId): array
    {
        $licenses = self::byTeacher($teacherAccountId);
        
        return [
            'total_licenses' => $licenses->count(),
            'active_licenses' => $licenses->active()->count(),
            'expired_licenses' => $licenses->expired()->count(),
            'expiring_soon' => $licenses->expiringSoon()->count(),
            'total_revenue' => $licenses->sum('discounted_price'),
            'total_discount_provided' => $licenses->sum('discount_amount'),
            'average_license_duration' => $licenses->avg('duration_in_days'),
        ];
    }

    /**
     * Get license statistics for student
     */
    public static function getStudentStatistics(int $studentUserId): array
    {
        $licenses = self::byStudent($studentUserId);
        
        return [
            'total_licenses' => $licenses->count(),
            'active_licenses' => $licenses->active()->count(),
            'expired_licenses' => $licenses->expired()->count(),
            'total_savings' => $licenses->sum('discount_amount'),
            'average_license_duration' => $licenses->avg('duration_in_days'),
        ];
    }

    /**
     * Get global license statistics
     */
    public static function getGlobalStatistics(): array
    {
        return [
            'total_licenses' => self::count(),
            'active_licenses' => self::active()->count(),
            'expired_licenses' => self::expired()->count(),
            'expiring_soon' => self::expiringSoon()->count(),
            'total_revenue' => self::sum('discounted_price'),
            'total_discount_provided' => self::sum('discount_amount'),
            'by_type' => self::groupBy('license_type')->selectRaw('license_type, count(*) as count')->get(),
        ];
    }
}
