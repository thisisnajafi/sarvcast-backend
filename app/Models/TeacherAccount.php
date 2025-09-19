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
        'status',
        'verification_documents',
        'is_verified',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        'verification_documents' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'certification_date' => 'date',
        'discount_rate' => 'decimal:2',
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
}
