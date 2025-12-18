<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'institution_name',
        'institution_type',
        'teaching_subject',
        'years_of_experience',
        'certification_number',
        'certification_authority',
        'certification_date',
        'student_count',
        'max_student_licenses',
        'discount_rate',
        'commission_rate',
        'coupon_code',
        'coupon_usage_count',
        'total_commission_earned',
        'commission_settings',
        'status',
        'verification_documents',
        'is_verified',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        'verification_documents' => 'array',
        'commission_settings' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'certification_date' => 'date',
        'discount_rate' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'total_commission_earned' => 'decimal:2',
        'coupon_usage_count' => 'integer',
    ];

    /**
     * Get the user that owns the teacher account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the student licenses for this teacher
     */
    public function studentLicenses(): HasMany
    {
        return $this->hasMany(StudentLicense::class, 'teacher_account_id');
    }

    /**
     * Verify the teacher account
     */
    public function verify(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'status' => 'verified',
        ]);
    }

    /**
     * Suspend the teacher account
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Check if teacher account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'verified' && 
               $this->is_verified && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Get available student license slots
     */
    public function getAvailableSlots(): int
    {
        $usedSlots = $this->studentLicenses()->where('status', 'active')->count();
        return max(0, $this->max_student_licenses - $usedSlots);
    }

    /**
     * Check if teacher can create more student licenses
     */
    public function canCreateStudentLicense(): bool
    {
        return $this->isActive() && $this->getAvailableSlots() > 0;
    }

    /**
     * Calculate discounted price for student license
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
     * Get teacher account statistics
     */
    public function getStatistics(): array
    {
        $licenses = $this->studentLicenses();
        
        return [
            'total_licenses' => $licenses->count(),
            'active_licenses' => $licenses->where('status', 'active')->count(),
            'expired_licenses' => $licenses->where('status', 'expired')->count(),
            'available_slots' => $this->getAvailableSlots(),
            'total_revenue_generated' => $licenses->sum('discounted_price'),
            'total_discount_provided' => $licenses->sum('discount_amount'),
        ];
    }

    /**
     * Scope to get verified teachers
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true)->where('status', 'verified');
    }

    /**
     * Scope to get active teachers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'verified')
                    ->where('is_verified', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope to get teachers by institution type
     */
    public function scopeByInstitutionType($query, string $type)
    {
        return $query->where('institution_type', $type);
    }

    /**
     * Scope to get teachers by teaching subject
     */
    public function scopeByTeachingSubject($query, string $subject)
    {
        return $query->where('teaching_subject', $subject);
    }

    /**
     * Get institution types
     */
    public static function getInstitutionTypes(): array
    {
        return [
            'school' => 'مدرسه',
            'university' => 'دانشگاه',
            'private_center' => 'مرکز خصوصی',
            'cultural_center' => 'مرکز فرهنگی',
            'online_platform' => 'پلتفرم آنلاین',
        ];
    }

    /**
     * Get teaching subjects
     */
    public static function getTeachingSubjects(): array
    {
        return [
            'persian_language' => 'زبان فارسی',
            'literature' => 'ادبیات',
            'history' => 'تاریخ',
            'culture' => 'فرهنگ',
            'arts' => 'هنر',
            'general_education' => 'آموزش عمومی',
            'special_needs' => 'نیازهای ویژه',
        ];
    }

    /**
     * Get teacher program benefits
     */
    public static function getProgramBenefits(): array
    {
        return [
            'account_discount' => '50% تخفیف روی تمام اشتراک‌ها',
            'bulk_student_licenses' => 'تا 100 لایسنس دانش‌آموزی با 70% تخفیف',
            'referral_commission' => '25% کمیسیون برای 6 ماه',
            'educational_resources' => 'دسترسی به منابع آموزشی',
            'priority_support' => 'پشتیبانی اولویت',
            'professional_development' => 'دسترسی رایگان به کارگاه‌ها',
        ];
    }

    /**
     * Generate a unique coupon code for the teacher
     */
    public function generateCouponCode(): string
    {
        if (!$this->coupon_code) {
            $prefix = 'TCH';
            $teacherId = str_pad($this->id, 4, '0', STR_PAD_LEFT);
            $random = strtoupper(substr(md5(uniqid()), 0, 3));
            
            $this->coupon_code = $prefix . $teacherId . $random;
            $this->save();
        }
        
        return $this->coupon_code;
    }

    /**
     * Get the coupon code (generate if not exists)
     */
    public function getCouponCodeAttribute($value): string
    {
        if (!$value) {
            return $this->generateCouponCode();
        }
        return $value;
    }

    /**
     * Calculate commission for a given amount
     */
    public function calculateCommission(float $amount): float
    {
        return ($amount * $this->commission_rate) / 100;
    }

    /**
     * Add commission to total earned
     */
    public function addCommission(float $amount): void
    {
        $this->increment('total_commission_earned', $amount);
    }

    /**
     * Increment coupon usage count
     */
    public function incrementCouponUsage(): void
    {
        $this->increment('coupon_usage_count');
    }

    /**
     * Get commission statistics
     */
    public function getCommissionStats(): array
    {
        return [
            'commission_rate' => $this->commission_rate,
            'total_earned' => $this->total_commission_earned,
            'coupon_usage_count' => $this->coupon_usage_count,
            'coupon_code' => $this->coupon_code,
            'average_per_usage' => $this->coupon_usage_count > 0 
                ? $this->total_commission_earned / $this->coupon_usage_count 
                : 0,
        ];
    }

    /**
     * Check if teacher is eligible for commission
     */
    public function isEligibleForCommission(): bool
    {
        return $this->is_verified && 
               $this->status === 'verified' && 
               $this->commission_rate > 0;
    }

    /**
     * Get assigned school partnerships
     */
    public function assignedSchoolPartnerships(): HasMany
    {
        return $this->hasMany(SchoolPartnership::class, 'assigned_teacher_id');
    }

    /**
     * Get commission settings with defaults
     */
    public function getCommissionSettingsAttribute($value): array
    {
        $defaults = [
            'min_commission_amount' => 1000, // Minimum commission in IRR
            'max_commission_amount' => 100000, // Maximum commission in IRR
            'commission_payment_method' => 'bank_transfer',
            'commission_payment_frequency' => 'monthly',
            'auto_payment_enabled' => false,
            'commission_threshold' => 50000, // Minimum amount before payment
        ];

        if ($value) {
            return array_merge($defaults, json_decode($value, true));
        }

        return $defaults;
    }

    /**
     * Set commission settings
     */
    public function setCommissionSettings(array $settings): void
    {
        $this->commission_settings = $settings;
        $this->save();
    }
}
