<?php

namespace App\Services;

use App\Models\CorporateSponsorship;
use App\Models\SponsoredContent;
use App\Models\SponsorshipAnalytics;
use App\Models\AffiliatePartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CorporateService
{
    /**
     * Create corporate sponsorship
     */
    public function createSponsorship(int $partnerId, array $sponsorshipData): array
    {
        try {
            DB::beginTransaction();

            $partner = AffiliatePartner::findOrFail($partnerId);
            
            // Check if partner is a corporate sponsor
            if ($partner->type !== 'corporate') {
                return [
                    'success' => false,
                    'message' => 'فقط شرکت‌های تجاری می‌توانند حمایت ایجاد کنند'
                ];
            }

            // Check if partner is active
            if ($partner->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'شرکت فعال نیست'
                ];
            }

            // Check minimum sponsorship amount
            $sponsorshipTypes = CorporateSponsorship::getSponsorshipTypes();
            $type = $sponsorshipData['sponsorship_type'];
            $minAmount = $sponsorshipTypes[$type]['min_amount'] ?? 0;
            
            if ($sponsorshipData['sponsorship_amount'] < $minAmount) {
                return [
                    'success' => false,
                    'message' => "مبلغ حمایت باید حداقل {$minAmount} ریال باشد"
                ];
            }

            $sponsorship = CorporateSponsorship::create([
                'affiliate_partner_id' => $partnerId,
                'company_name' => $sponsorshipData['company_name'],
                'company_type' => $sponsorshipData['company_type'],
                'industry' => $sponsorshipData['industry'],
                'company_size' => $sponsorshipData['company_size'],
                'contact_person' => $sponsorshipData['contact_person'],
                'contact_email' => $sponsorshipData['contact_email'],
                'contact_phone' => $sponsorshipData['contact_phone'],
                'website_url' => $sponsorshipData['website_url'] ?? null,
                'company_description' => $sponsorshipData['company_description'],
                'sponsorship_type' => $type,
                'sponsorship_amount' => $sponsorshipData['sponsorship_amount'],
                'currency' => $sponsorshipData['currency'] ?? 'IRR',
                'payment_frequency' => $sponsorshipData['payment_frequency'],
                'sponsorship_start_date' => $sponsorshipData['sponsorship_start_date'],
                'sponsorship_end_date' => $sponsorshipData['sponsorship_end_date'],
                'sponsorship_benefits' => $sponsorshipData['sponsorship_benefits'] ?? null,
                'content_requirements' => $sponsorshipData['content_requirements'] ?? null,
                'target_audience' => $sponsorshipData['target_audience'] ?? null,
                'requires_content_approval' => $sponsorshipData['requires_content_approval'] ?? true,
                'allows_brand_mention' => $sponsorshipData['allows_brand_mention'] ?? true,
                'requires_logo_display' => $sponsorshipData['requires_logo_display'] ?? true,
                'special_requirements' => $sponsorshipData['special_requirements'] ?? null,
                'verification_documents' => $sponsorshipData['verification_documents'] ?? null,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'حمایت شرکتی با موفقیت ایجاد شد',
                'data' => [
                    'sponsorship_id' => $sponsorship->id,
                    'company_name' => $sponsorship->company_name,
                    'sponsorship_type' => $sponsorship->sponsorship_type,
                    'sponsorship_amount' => $sponsorship->sponsorship_amount,
                    'currency' => $sponsorship->currency,
                    'payment_frequency' => $sponsorship->payment_frequency,
                    'sponsorship_start_date' => $sponsorship->sponsorship_start_date,
                    'sponsorship_end_date' => $sponsorship->sponsorship_end_date,
                    'status' => $sponsorship->status,
                    'total_value' => $sponsorship->getTotalValue(),
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Corporate sponsorship creation failed', [
                'partner_id' => $partnerId,
                'sponsorship_data' => $sponsorshipData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد حمایت شرکتی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create sponsored content
     */
    public function createContent(int $sponsorshipId, array $contentData): array
    {
        try {
            DB::beginTransaction();

            $sponsorship = CorporateSponsorship::findOrFail($sponsorshipId);
            
            // Check if sponsorship is active
            if (!$sponsorship->isActive()) {
                return [
                    'success' => false,
                    'message' => 'حمایت فعال نیست'
                ];
            }

            $content = SponsoredContent::create([
                'sponsorship_id' => $sponsorshipId,
                'story_id' => $contentData['story_id'] ?? null,
                'episode_id' => $contentData['episode_id'] ?? null,
                'content_type' => $contentData['content_type'],
                'content_title' => $contentData['content_title'],
                'content_description' => $contentData['content_description'],
                'sponsor_message' => $contentData['sponsor_message'] ?? null,
                'brand_logo_url' => $contentData['brand_logo_url'] ?? null,
                'brand_website_url' => $contentData['brand_website_url'] ?? null,
                'content_media' => $contentData['content_media'] ?? null,
                'placement_type' => $contentData['placement_type'],
                'display_duration' => $contentData['display_duration'] ?? null,
                'display_frequency' => $contentData['display_frequency'] ?? 1,
                'start_date' => $contentData['start_date'],
                'end_date' => $contentData['end_date'],
                'status' => $sponsorship->requires_content_approval ? 'pending_approval' : 'active',
                'published_at' => $sponsorship->requires_content_approval ? null : now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => $sponsorship->requires_content_approval ? 'محتوا برای بررسی ارسال شد' : 'محتوا منتشر شد',
                'data' => [
                    'content_id' => $content->id,
                    'content_type' => $content->content_type,
                    'placement_type' => $content->placement_type,
                    'status' => $content->status,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sponsored content creation failed', [
                'sponsorship_id' => $sponsorshipId,
                'content_data' => $contentData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد محتوای حمایت شده: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Approve sponsored content
     */
    public function approveContent(int $contentId): array
    {
        try {
            $content = SponsoredContent::findOrFail($contentId);
            $content->approve();

            return [
                'success' => true,
                'message' => 'محتوا با موفقیت تأیید شد',
                'data' => [
                    'content_id' => $content->id,
                    'status' => $content->status,
                    'approved_at' => $content->approved_at,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Content approval failed', [
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تأیید محتوا: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reject sponsored content
     */
    public function rejectContent(int $contentId, string $reason): array
    {
        try {
            $content = SponsoredContent::findOrFail($contentId);
            $content->reject($reason);

            return [
                'success' => true,
                'message' => 'محتوا رد شد',
                'data' => [
                    'content_id' => $content->id,
                    'status' => $content->status,
                    'rejection_reason' => $content->rejection_reason,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Content rejection failed', [
                'content_id' => $contentId,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در رد محتوا: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Record analytics event
     */
    public function recordEvent(int $contentId, string $eventType, int $userId = null, array $metadata = []): array
    {
        try {
            $content = SponsoredContent::findOrFail($contentId);
            $content->recordEvent($eventType, $userId, $metadata);

            return [
                'success' => true,
                'message' => 'رویداد با موفقیت ثبت شد',
                'data' => [
                    'content_id' => $contentId,
                    'event_type' => $eventType,
                    'user_id' => $userId,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Analytics event recording failed', [
                'content_id' => $contentId,
                'event_type' => $eventType,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت رویداد: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get sponsorship details
     */
    public function getSponsorship(int $sponsorshipId): array
    {
        $sponsorship = CorporateSponsorship::with(['affiliatePartner.user:id,name'])
            ->findOrFail($sponsorshipId);

        return [
            'success' => true,
            'message' => 'جزئیات حمایت شرکتی دریافت شد',
            'data' => [
                'id' => $sponsorship->id,
                'company_name' => $sponsorship->company_name,
                'company_type' => $sponsorship->company_type,
                'industry' => $sponsorship->industry,
                'company_size' => $sponsorship->company_size,
                'contact_person' => $sponsorship->contact_person,
                'contact_email' => $sponsorship->contact_email,
                'contact_phone' => $sponsorship->contact_phone,
                'website_url' => $sponsorship->website_url,
                'company_description' => $sponsorship->company_description,
                'sponsorship_type' => $sponsorship->sponsorship_type,
                'sponsorship_amount' => $sponsorship->sponsorship_amount,
                'currency' => $sponsorship->currency,
                'payment_frequency' => $sponsorship->payment_frequency,
                'sponsorship_start_date' => $sponsorship->sponsorship_start_date,
                'sponsorship_end_date' => $sponsorship->sponsorship_end_date,
                'status' => $sponsorship->status,
                'sponsorship_benefits' => $sponsorship->sponsorship_benefits,
                'content_requirements' => $sponsorship->content_requirements,
                'target_audience' => $sponsorship->target_audience,
                'requires_content_approval' => $sponsorship->requires_content_approval,
                'allows_brand_mention' => $sponsorship->allows_brand_mention,
                'requires_logo_display' => $sponsorship->requires_logo_display,
                'special_requirements' => $sponsorship->special_requirements,
                'is_verified' => $sponsorship->is_verified,
                'verified_at' => $sponsorship->verified_at,
                'remaining_days' => $sponsorship->getRemainingDays(),
                'is_active' => $sponsorship->isActive(),
                'total_value' => $sponsorship->getTotalValue(),
                'partner' => [
                    'id' => $sponsorship->affiliatePartner->id,
                    'name' => $sponsorship->affiliatePartner->user->name,
                ],
                'statistics' => $sponsorship->getStatistics(),
            ]
        ];
    }

    /**
     * Get sponsorship content
     */
    public function getSponsorshipContent(int $sponsorshipId, int $limit = 20, int $offset = 0): array
    {
        $content = SponsoredContent::bySponsorship($sponsorshipId)
            ->with(['story:id,title', 'episode:id,title'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'content_type' => $item->content_type,
                    'content_title' => $item->content_title,
                    'content_description' => $item->content_description,
                    'sponsor_message' => $item->sponsor_message,
                    'brand_logo_url' => $item->brand_logo_url,
                    'brand_website_url' => $item->brand_website_url,
                    'placement_type' => $item->placement_type,
                    'display_duration' => $item->display_duration,
                    'display_frequency' => $item->display_frequency,
                    'start_date' => $item->start_date,
                    'end_date' => $item->end_date,
                    'status' => $item->status,
                    'remaining_days' => $item->getRemainingDays(),
                    'is_active' => $item->isActive(),
                    'impressions' => $item->impressions,
                    'clicks' => $item->clicks,
                    'conversions' => $item->conversions,
                    'ctr' => $item->ctr,
                    'conversion_rate' => $item->conversion_rate,
                    'story' => $item->story ? ['id' => $item->story->id, 'title' => $item->story->title] : null,
                    'episode' => $item->episode ? ['id' => $item->episode->id, 'title' => $item->episode->title] : null,
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست محتوای حمایت شده دریافت شد',
            'data' => [
                'content' => $content,
                'total' => SponsoredContent::bySponsorship($sponsorshipId)->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Get content analytics
     */
    public function getContentAnalytics(int $contentId, $startDate = null, $endDate = null): array
    {
        $analytics = SponsorshipAnalytics::getContentAnalytics($contentId, $startDate, $endDate);

        return [
            'success' => true,
            'message' => 'آمار محتوا دریافت شد',
            'data' => $analytics
        ];
    }

    /**
     * Get sponsorship analytics
     */
    public function getSponsorshipAnalytics(int $sponsorshipId, $startDate = null, $endDate = null): array
    {
        $analytics = SponsorshipAnalytics::getSponsorshipAnalytics($sponsorshipId, $startDate, $endDate);

        return [
            'success' => true,
            'message' => 'آمار حمایت دریافت شد',
            'data' => $analytics
        ];
    }

    /**
     * Get sponsorship types
     */
    public function getSponsorshipTypes(): array
    {
        return [
            'success' => true,
            'message' => 'انواع حمایت دریافت شد',
            'data' => CorporateSponsorship::getSponsorshipTypes()
        ];
    }

    /**
     * Get company types
     */
    public function getCompanyTypes(): array
    {
        return [
            'success' => true,
            'message' => 'انواع شرکت‌ها دریافت شد',
            'data' => CorporateSponsorship::getCompanyTypes()
        ];
    }

    /**
     * Get industries
     */
    public function getIndustries(): array
    {
        return [
            'success' => true,
            'message' => 'صنایع دریافت شد',
            'data' => CorporateSponsorship::getIndustries()
        ];
    }

    /**
     * Get company sizes
     */
    public function getCompanySizes(): array
    {
        return [
            'success' => true,
            'message' => 'اندازه‌های شرکت دریافت شد',
            'data' => CorporateSponsorship::getCompanySizes()
        ];
    }

    /**
     * Get payment frequencies
     */
    public function getPaymentFrequencies(): array
    {
        return [
            'success' => true,
            'message' => 'فرکانس‌های پرداخت دریافت شد',
            'data' => CorporateSponsorship::getPaymentFrequencies()
        ];
    }

    /**
     * Get sponsorship benefits
     */
    public function getSponsorshipBenefits(): array
    {
        return [
            'success' => true,
            'message' => 'مزایای حمایت دریافت شد',
            'data' => CorporateSponsorship::getSponsorshipBenefits()
        ];
    }

    /**
     * Get content types
     */
    public function getContentTypes(): array
    {
        return [
            'success' => true,
            'message' => 'انواع محتوا دریافت شد',
            'data' => SponsoredContent::getContentTypes()
        ];
    }

    /**
     * Get placement types
     */
    public function getPlacementTypes(): array
    {
        return [
            'success' => true,
            'message' => 'انواع قرارگیری دریافت شد',
            'data' => SponsoredContent::getPlacementTypes()
        ];
    }

    /**
     * Get global corporate statistics
     */
    public function getGlobalStatistics(): array
    {
        $cacheKey = 'global_corporate_statistics';
        
        $stats = Cache::remember($cacheKey, 1800, function() {
            $sponsorships = CorporateSponsorship::query();
            $content = SponsoredContent::query();
            
            return [
                'total_sponsorships' => $sponsorships->count(),
                'active_sponsorships' => $sponsorships->active()->count(),
                'expired_sponsorships' => $sponsorships->expired()->count(),
                'total_content' => $content->count(),
                'active_content' => $content->active()->count(),
                'expired_content' => $content->expired()->count(),
                'total_impressions' => $content->sum('impressions'),
                'total_clicks' => $content->sum('clicks'),
                'total_conversions' => $content->sum('conversions'),
                'average_ctr' => $content->avg('ctr'),
                'average_conversion_rate' => $content->avg('conversion_rate'),
                'by_sponsorship_type' => $sponsorships->groupBy('sponsorship_type')->count(),
                'by_company_type' => $sponsorships->groupBy('company_type')->count(),
                'by_industry' => $sponsorships->groupBy('industry')->count(),
                'by_content_type' => $content->groupBy('content_type')->count(),
                'by_placement_type' => $content->groupBy('placement_type')->count(),
            ];
        });

        return [
            'success' => true,
            'message' => 'آمار کلی شرکتی دریافت شد',
            'data' => $stats
        ];
    }
}
