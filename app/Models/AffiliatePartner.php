<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliatePartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'type',
        'tier',
        'status',
        'commission_rate',
        'follower_count',
        'social_media_handle',
        'bio',
        'website',
        'verification_documents',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'verification_documents' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the commissions for this affiliate partner
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'affiliate_partner_id');
    }

    /**
     * Get the influencer campaigns for this affiliate partner
     */
    public function influencerCampaigns(): HasMany
    {
        return $this->hasMany(InfluencerCampaign::class, 'affiliate_partner_id');
    }

    /**
     * Verify the affiliate partner
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
     * Suspend the affiliate partner
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
        ]);
    }

    /**
     * Terminate the affiliate partner
     */
    public function terminate(string $reason = null): void
    {
        $this->update([
            'status' => 'terminated',
        ]);
    }

    /**
     * Get commission statistics
     */
    public function getCommissionStatistics(): array
    {
        $commissions = $this->commissions();
        
        return [
            'total_commissions' => $commissions->count(),
            'total_commission_amount' => $commissions->sum('commission_amount'),
            'paid_commissions' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'pending_commissions' => $commissions->where('status', 'pending')->sum('commission_amount'),
            'average_commission_rate' => $commissions->avg('commission_rate'),
        ];
    }

    /**
     * Scope to get active partners
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get verified partners
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get partners by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get partners by tier
     */
    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    /**
     * Get tier-specific commission rates
     */
    public static function getTierCommissionRates(): array
    {
        return [
            'micro' => 20.00,    // 20%
            'mid' => 25.00,      // 25%
            'macro' => 30.00,    // 30%
            'enterprise' => 35.00, // 35%
        ];
    }

    /**
     * Get type-specific requirements
     */
    public static function getTypeRequirements(): array
    {
        return [
            'teacher' => [
                'min_experience' => 2, // years
                'required_docs' => ['teaching_certificate', 'institution_affiliation'],
                'min_referrals' => 5, // per quarter
            ],
            'influencer' => [
                'min_followers' => 1000,
                'required_docs' => ['social_media_proof', 'engagement_metrics'],
                'min_posts' => 2, // per month
            ],
            'school' => [
                'min_students' => 50,
                'required_docs' => ['school_license', 'student_count_proof'],
                'min_commitment' => 1, // year
            ],
            'corporate' => [
                'min_budget' => 500000, // IRR
                'required_docs' => ['company_registration', 'budget_approval'],
                'min_campaigns' => 1, // per quarter
            ],
        ];
    }
}
