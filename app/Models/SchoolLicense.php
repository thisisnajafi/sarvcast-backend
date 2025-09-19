<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_partnership_id',
        'user_id',
        'license_type',
        'user_role',
        'original_price',
        'discounted_price',
        'discount_amount',
        'start_date',
        'end_date',
        'status',
        'is_activated',
        'activated_at',
        'expired_at',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_activated' => 'boolean',
        'activated_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /**
     * Get the school partnership that owns the license
     */
    public function schoolPartnership(): BelongsTo
    {
        return $this->belongsTo(SchoolPartnership::class, 'school_partnership_id');
    }

    /**
     * Get the user that owns the license
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Activate the license
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'is_activated' => true,
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
               $this->is_activated && 
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
                    ->where('is_activated', true)
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
     * Scope to get licenses by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('license_type', $type);
    }

    /**
     * Scope to get licenses by school partnership
     */
    public function scopeBySchoolPartnership($query, int $schoolPartnershipId)
    {
        return $query->where('school_partnership_id', $schoolPartnershipId);
    }

    /**
     * Scope to get licenses by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get licenses expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 30)
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
            'student' => 'دانش‌آموز',
            'teacher' => 'معلم',
            'admin' => 'مدیر',
        ];
    }

    /**
     * Get user roles
     */
    public static function getUserRoles(): array
    {
        return [
            'student' => 'دانش‌آموز',
            'teacher' => 'معلم',
            'administrator' => 'مدیر',
            'coordinator' => 'هماهنگ‌کننده',
        ];
    }

    /**
     * Get license statistics for school partnership
     */
    public static function getSchoolPartnershipStatistics(int $schoolPartnershipId): array
    {
        $licenses = self::bySchoolPartnership($schoolPartnershipId);
        
        return [
            'total_licenses' => $licenses->count(),
            'active_licenses' => $licenses->active()->count(),
            'expired_licenses' => $licenses->expired()->count(),
            'expiring_soon' => $licenses->expiringSoon()->count(),
            'student_licenses' => $licenses->byType('student')->count(),
            'teacher_licenses' => $licenses->byType('teacher')->count(),
            'admin_licenses' => $licenses->byType('admin')->count(),
            'total_revenue' => $licenses->sum('discounted_price'),
            'total_discount_provided' => $licenses->sum('discount_amount'),
            'average_license_duration' => $licenses->avg('duration_in_days'),
        ];
    }

    /**
     * Get license statistics for user
     */
    public static function getUserStatistics(int $userId): array
    {
        $licenses = self::byUser($userId);
        
        return [
            'total_licenses' => $licenses->count(),
            'active_licenses' => $licenses->active()->count(),
            'expired_licenses' => $licenses->expired()->count(),
            'total_savings' => $licenses->sum('discount_amount'),
            'average_license_duration' => $licenses->avg('duration_in_days'),
            'by_type' => $licenses->groupBy('license_type')->count(),
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
            'student_licenses' => self::byType('student')->count(),
            'teacher_licenses' => self::byType('teacher')->count(),
            'admin_licenses' => self::byType('admin')->count(),
            'total_revenue' => self::sum('discounted_price'),
            'total_discount_provided' => self::sum('discount_amount'),
            'by_partnership_model' => self::join('school_partnerships', 'school_licenses.school_partnership_id', '=', 'school_partnerships.id')
                ->groupBy('school_partnerships.partnership_model')
                ->selectRaw('school_partnerships.partnership_model, count(*) as count')
                ->get(),
        ];
    }
}
