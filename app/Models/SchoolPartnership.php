<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolPartnership extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_partner_id',
        'assigned_teacher_id',
        'school_name',
        'school_type',
        'school_level',
        'location',
        'contact_person',
        'contact_email',
        'contact_phone',
        'student_count',
        'teacher_count',
        'partnership_model',
        'discount_rate',
        'revenue_share_rate',
        'annual_license_fee',
        'max_student_capacity',
        'partnership_start_date',
        'partnership_end_date',
        'status',
        'verification_documents',
        'is_verified',
        'verified_at',
        'teacher_assigned_at',
        'teacher_assignment_notes',
        'notes',
    ];

    protected $casts = [
        'verification_documents' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'teacher_assigned_at' => 'datetime',
        'partnership_start_date' => 'date',
        'partnership_end_date' => 'date',
        'discount_rate' => 'decimal:2',
        'revenue_share_rate' => 'decimal:2',
        'annual_license_fee' => 'decimal:2',
    ];

    /**
     * Get the affiliate partner that owns the partnership
     */
    public function affiliatePartner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'affiliate_partner_id');
    }

    /**
     * Get the assigned teacher for this school partnership
     */
    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(TeacherAccount::class, 'assigned_teacher_id');
    }

    /**
     * Get the licenses for this school partnership
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(SchoolLicense::class, 'school_partnership_id');
    }

    /**
     * Verify the school partnership
     */
    public function verify(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * Suspend the school partnership
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Terminate the school partnership
     */
    public function terminate(): void
    {
        $this->update(['status' => 'terminated']);
    }

    /**
     * Check if partnership is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->is_verified && 
               $this->partnership_start_date <= now() && 
               $this->partnership_end_date >= now();
    }

    /**
     * Check if partnership is expired
     */
    public function isExpired(): bool
    {
        return $this->partnership_end_date < now();
    }

    /**
     * Get partnership duration in days
     */
    public function getDurationInDays(): int
    {
        return $this->partnership_start_date->diffInDays($this->partnership_end_date);
    }

    /**
     * Get remaining days
     */
    public function getRemainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->partnership_end_date));
    }

    /**
     * Get available license slots
     */
    public function getAvailableSlots(): int
    {
        $usedSlots = $this->licenses()->where('status', 'active')->count();
        return max(0, $this->max_student_capacity - $usedSlots);
    }

    /**
     * Check if school can create more licenses
     */
    public function canCreateLicense(): bool
    {
        return $this->isActive() && $this->getAvailableSlots() > 0;
    }

    /**
     * Calculate discounted price for license
     */
    public function calculateDiscountedPrice(float $originalPrice): array
    {
        $discountAmount = $originalPrice * ($this->discount_rate / 100);
        $discountedPrice = $originalPrice - $discountAmount;

        return [
            'original_price' => $originalPrice,
            'discount_rate' => $this->discount_rate,
            'discount_amount' => $discountAmount,
            'discounted_price' => $discountedPrice,
        ];
    }

    /**
     * Get partnership statistics
     */
    public function getStatistics(): array
    {
        $licenses = $this->licenses();
        
        return [
            'total_licenses' => $licenses->count(),
            'active_licenses' => $licenses->where('status', 'active')->count(),
            'expired_licenses' => $licenses->where('status', 'expired')->count(),
            'available_slots' => $this->getAvailableSlots(),
            'student_licenses' => $licenses->where('license_type', 'student')->count(),
            'teacher_licenses' => $licenses->where('license_type', 'teacher')->count(),
            'admin_licenses' => $licenses->where('license_type', 'admin')->count(),
            'total_revenue_generated' => $licenses->sum('discounted_price'),
            'total_discount_provided' => $licenses->sum('discount_amount'),
            'revenue_share_amount' => $this->revenue_share_rate ? 
                $licenses->sum('discounted_price') * ($this->revenue_share_rate / 100) : 0,
        ];
    }

    /**
     * Scope to get active partnerships
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('is_verified', true)
                    ->where('partnership_start_date', '<=', now())
                    ->where('partnership_end_date', '>=', now());
    }

    /**
     * Scope to get expired partnerships
     */
    public function scopeExpired($query)
    {
        return $query->where('partnership_end_date', '<', now());
    }

    /**
     * Scope to get partnerships by model
     */
    public function scopeByModel($query, string $model)
    {
        return $query->where('partnership_model', $model);
    }

    /**
     * Scope to get partnerships by school type
     */
    public function scopeBySchoolType($query, string $type)
    {
        return $query->where('school_type', $type);
    }

    /**
     * Scope to get partnerships by school level
     */
    public function scopeBySchoolLevel($query, string $level)
    {
        return $query->where('school_level', $level);
    }

    /**
     * Get partnership models
     */
    public static function getPartnershipModels(): array
    {
        return [
            'revenue_sharing' => [
                'name' => 'اشتراک درآمد',
                'description' => '60% تخفیف + 20% اشتراک درآمد',
                'discount_rate' => 60.00,
                'revenue_share_rate' => 20.00,
                'min_commitment' => 50, // students
            ],
            'licensing' => [
                'name' => 'لایسنس سالانه',
                'description' => 'لایسنس نامحدود برای تا 500 دانش‌آموز',
                'annual_license_fee' => 2000000, // IRR
                'max_capacity' => 500,
                'min_commitment' => 1, // year
            ],
            'pilot' => [
                'name' => 'برنامه آزمایشی',
                'description' => '3 ماه دسترسی رایگان برای ارزیابی',
                'discount_rate' => 100.00, // Free
                'duration_months' => 3,
                'max_capacity' => 100,
            ],
        ];
    }

    /**
     * Get school types
     */
    public static function getSchoolTypes(): array
    {
        return [
            'public' => 'مدرسه دولتی',
            'private' => 'مدرسه خصوصی',
            'international' => 'مدرسه بین‌المللی',
            'cultural_center' => 'مرکز فرهنگی',
            'language_institute' => 'مؤسسه زبان',
        ];
    }

    /**
     * Get school levels
     */
    public static function getSchoolLevels(): array
    {
        return [
            'elementary' => 'ابتدایی',
            'middle' => 'متوسطه اول',
            'high' => 'متوسطه دوم',
            'university' => 'دانشگاه',
            'mixed' => 'ترکیبی',
        ];
    }

    /**
     * Get partnership benefits
     */
    public static function getPartnershipBenefits(): array
    {
        return [
            'revenue_sharing' => [
                '60% تخفیف روی تمام اشتراک‌ها',
                '20% اشتراک درآمد',
                'داشبورد آموزشی',
                'ردیابی پیشرفت',
                'همراستایی با برنامه درسی',
            ],
            'licensing' => [
                'لایسنس نامحدود',
                'تا 500 دانش‌آموز',
                'آموزش معلمان',
                'ادغام برنامه درسی',
                'گزارش‌های پیشرفت',
                'تیم پشتیبانی اختصاصی',
            ],
            'pilot' => [
                '3 ماه دسترسی رایگان',
                'تا 100 دانش‌آموز',
                'ارزیابی و مطالعه موردی',
                'پشتیبانی کامل',
                'گزارش‌های تحلیلی',
            ],
        ];
    }
}
