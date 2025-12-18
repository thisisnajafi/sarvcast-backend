<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorporateSponsorship extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_partner_id',
        'company_name',
        'company_type',
        'industry',
        'company_size',
        'contact_person',
        'contact_email',
        'contact_phone',
        'website_url',
        'company_description',
        'sponsorship_type',
        'sponsorship_amount',
        'currency',
        'payment_frequency',
        'sponsorship_start_date',
        'sponsorship_end_date',
        'status',
        'sponsorship_benefits',
        'content_requirements',
        'target_audience',
        'requires_content_approval',
        'allows_brand_mention',
        'requires_logo_display',
        'special_requirements',
        'verification_documents',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'sponsorship_benefits' => 'array',
        'content_requirements' => 'array',
        'target_audience' => 'array',
        'requires_content_approval' => 'boolean',
        'allows_brand_mention' => 'boolean',
        'requires_logo_display' => 'boolean',
        'verification_documents' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'sponsorship_start_date' => 'date',
        'sponsorship_end_date' => 'date',
        'sponsorship_amount' => 'decimal:2',
    ];

    /**
     * Get the affiliate partner that owns the sponsorship
     */
    public function affiliatePartner(): BelongsTo
    {
        return $this->belongsTo(AffiliatePartner::class, 'affiliate_partner_id');
    }

    /**
     * Get the sponsored content for this sponsorship
     */
    public function sponsoredContent(): HasMany
    {
        return $this->hasMany(SponsoredContent::class, 'sponsorship_id');
    }

    /**
     * Approve the sponsorship
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Activate the sponsorship
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Suspend the sponsorship
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Complete the sponsorship
     */
    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Cancel the sponsorship
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if sponsorship is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->is_verified && 
               $this->sponsorship_start_date <= now() && 
               $this->sponsorship_end_date >= now();
    }

    /**
     * Check if sponsorship is expired
     */
    public function isExpired(): bool
    {
        return $this->sponsorship_end_date < now();
    }

    /**
     * Get sponsorship duration in days
     */
    public function getDurationInDays(): int
    {
        return $this->sponsorship_start_date->diffInDays($this->sponsorship_end_date);
    }

    /**
     * Get remaining days
     */
    public function getRemainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->sponsorship_end_date));
    }

    /**
     * Calculate total sponsorship value
     */
    public function getTotalValue(): float
    {
        $duration = $this->getDurationInDays();
        
        switch ($this->payment_frequency) {
            case 'monthly':
                return $this->sponsorship_amount * ceil($duration / 30);
            case 'quarterly':
                return $this->sponsorship_amount * ceil($duration / 90);
            case 'annually':
                return $this->sponsorship_amount * ceil($duration / 365);
            default: // one_time
                return $this->sponsorship_amount;
        }
    }

    /**
     * Get sponsorship statistics
     */
    public function getStatistics(): array
    {
        $content = $this->sponsoredContent();
        
        return [
            'total_content' => $content->count(),
            'active_content' => $content->where('status', 'active')->count(),
            'pending_content' => $content->where('status', 'pending_approval')->count(),
            'approved_content' => $content->where('status', 'approved')->count(),
            'rejected_content' => $content->where('status', 'rejected')->count(),
            'total_impressions' => $content->sum('impressions'),
            'total_clicks' => $content->sum('clicks'),
            'total_conversions' => $content->sum('conversions'),
            'average_ctr' => $content->avg('ctr'),
            'average_conversion_rate' => $content->avg('conversion_rate'),
            'total_sponsorship_value' => $this->getTotalValue(),
        ];
    }

    /**
     * Scope to get active sponsorships
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('is_verified', true)
                    ->where('sponsorship_start_date', '<=', now())
                    ->where('sponsorship_end_date', '>=', now());
    }

    /**
     * Scope to get expired sponsorships
     */
    public function scopeExpired($query)
    {
        return $query->where('sponsorship_end_date', '<', now());
    }

    /**
     * Scope to get sponsorships by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('sponsorship_type', $type);
    }

    /**
     * Scope to get sponsorships by company type
     */
    public function scopeByCompanyType($query, string $type)
    {
        return $query->where('company_type', $type);
    }

    /**
     * Scope to get sponsorships by industry
     */
    public function scopeByIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    /**
     * Get sponsorship types
     */
    public static function getSponsorshipTypes(): array
    {
        return [
            'content_sponsorship' => [
                'name' => 'حمایت محتوا',
                'description' => 'حمایت مالی برای تولید محتوای آموزشی',
                'min_amount' => 5000000, // IRR
                'benefits' => ['نمایش لوگو', 'ذکر نام برند', 'لینک به وبسایت'],
            ],
            'brand_partnership' => [
                'name' => 'همکاری برند',
                'description' => 'همکاری استراتژیک با برندها',
                'min_amount' => 10000000, // IRR
                'benefits' => ['نمایش لوگو', 'ذکر نام برند', 'محتوای اختصاصی'],
            ],
            'educational_initiative' => [
                'name' => 'ابتکار آموزشی',
                'description' => 'حمایت از برنامه‌های آموزشی',
                'min_amount' => 20000000, // IRR
                'benefits' => ['نمایش لوگو', 'محتوای آموزشی اختصاصی', 'گزارش‌های پیشرفت'],
            ],
            'cultural_preservation' => [
                'name' => 'حفظ فرهنگ',
                'description' => 'حمایت از حفظ فرهنگ و زبان فارسی',
                'min_amount' => 15000000, // IRR
                'benefits' => ['نمایش لوگو', 'محتوای فرهنگی', 'گزارش‌های تأثیر'],
            ],
            'technology_partnership' => [
                'name' => 'همکاری فناوری',
                'description' => 'همکاری در زمینه فناوری و نوآوری',
                'min_amount' => 30000000, // IRR
                'benefits' => ['نمایش لوگو', 'محتوای فناوری', 'همکاری فنی'],
            ],
        ];
    }

    /**
     * Get company types
     */
    public static function getCompanyTypes(): array
    {
        return [
            'tech' => 'فناوری',
            'education' => 'آموزش',
            'media' => 'رسانه',
            'cultural' => 'فرهنگی',
            'entertainment' => 'سرگرمی',
            'healthcare' => 'سلامت',
            'finance' => 'مالی',
            'retail' => 'خرده‌فروشی',
            'other' => 'سایر',
        ];
    }

    /**
     * Get industries
     */
    public static function getIndustries(): array
    {
        return [
            'technology' => 'فناوری',
            'education' => 'آموزش',
            'entertainment' => 'سرگرمی',
            'cultural' => 'فرهنگی',
            'healthcare' => 'سلامت',
            'finance' => 'مالی',
            'retail' => 'خرده‌فروشی',
            'manufacturing' => 'تولید',
            'services' => 'خدمات',
            'other' => 'سایر',
        ];
    }

    /**
     * Get company sizes
     */
    public static function getCompanySizes(): array
    {
        return [
            'startup' => 'استارتاپ (1-10 نفر)',
            'small' => 'کوچک (11-50 نفر)',
            'medium' => 'متوسط (51-200 نفر)',
            'large' => 'بزرگ (201-1000 نفر)',
            'enterprise' => 'سازمانی (1000+ نفر)',
        ];
    }

    /**
     * Get payment frequencies
     */
    public static function getPaymentFrequencies(): array
    {
        return [
            'one_time' => 'یکباره',
            'monthly' => 'ماهانه',
            'quarterly' => 'فصلی',
            'annually' => 'سالانه',
        ];
    }

    /**
     * Get sponsorship benefits
     */
    public static function getSponsorshipBenefits(): array
    {
        return [
            'logo_display' => 'نمایش لوگو در محتوا',
            'brand_mention' => 'ذکر نام برند',
            'website_link' => 'لینک به وبسایت',
            'dedicated_content' => 'محتوای اختصاصی',
            'analytics_report' => 'گزارش‌های تحلیلی',
            'priority_support' => 'پشتیبانی اولویت‌دار',
            'custom_integration' => 'ادغام سفارشی',
            'exclusive_access' => 'دسترسی انحصاری',
        ];
    }
}
