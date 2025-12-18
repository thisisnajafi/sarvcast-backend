<?php

namespace App\Services;

use App\Models\InfluencerCampaign;
use App\Models\InfluencerContent;
use App\Models\AffiliatePartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InfluencerService
{
    /**
     * Create influencer campaign
     */
    public function createCampaign(int $partnerId, array $campaignData): array
    {
        try {
            DB::beginTransaction();

            $partner = AffiliatePartner::findOrFail($partnerId);
            
            // Check if partner is an influencer
            if ($partner->type !== 'influencer') {
                return [
                    'success' => false,
                    'message' => 'فقط اینفلوئنسرها می‌توانند کمپین ایجاد کنند'
                ];
            }

            // Check if partner is active
            if ($partner->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'اینفلوئنسر فعال نیست'
                ];
            }

            // Set compensation based on tier
            $tierRates = InfluencerCampaign::getTierCompensationRates();
            $tierRate = $tierRates[$partner->tier] ?? $tierRates['micro'];
            
            $campaign = InfluencerCampaign::create([
                'affiliate_partner_id' => $partnerId,
                'campaign_name' => $campaignData['campaign_name'],
                'campaign_description' => $campaignData['campaign_description'],
                'campaign_type' => $campaignData['campaign_type'],
                'content_type' => $campaignData['content_type'],
                'required_posts' => $campaignData['required_posts'] ?? 1,
                'required_stories' => $campaignData['required_stories'] ?? 0,
                'compensation_per_post' => $tierRate['per_post'],
                'commission_rate' => $tierRate['commission_rate'],
                'start_date' => $campaignData['start_date'],
                'end_date' => $campaignData['end_date'],
                'content_guidelines' => $campaignData['content_guidelines'] ?? null,
                'hashtags' => $campaignData['hashtags'] ?? null,
                'target_audience' => $campaignData['target_audience'] ?? null,
                'requires_approval' => $campaignData['requires_approval'] ?? true,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'کمپین اینفلوئنسر با موفقیت ایجاد شد',
                'data' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->campaign_name,
                    'campaign_type' => $campaign->campaign_type,
                    'compensation_per_post' => $campaign->compensation_per_post,
                    'commission_rate' => $campaign->commission_rate,
                    'start_date' => $campaign->start_date,
                    'end_date' => $campaign->end_date,
                    'status' => $campaign->status,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Influencer campaign creation failed', [
                'partner_id' => $partnerId,
                'campaign_data' => $campaignData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد کمپین اینفلوئنسر: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Submit content for campaign
     */
    public function submitContent(int $campaignId, int $partnerId, array $contentData): array
    {
        try {
            DB::beginTransaction();

            $campaign = InfluencerCampaign::findOrFail($campaignId);
            
            // Check if campaign is active
            if (!$campaign->isActive()) {
                return [
                    'success' => false,
                    'message' => 'کمپین فعال نیست'
                ];
            }

            // Check if partner is assigned to campaign
            if ($campaign->affiliate_partner_id !== $partnerId) {
                return [
                    'success' => false,
                    'message' => 'شما به این کمپین دسترسی ندارید'
                ];
            }

            $content = InfluencerContent::create([
                'campaign_id' => $campaignId,
                'affiliate_partner_id' => $partnerId,
                'content_type' => $contentData['content_type'],
                'platform' => $contentData['platform'],
                'content_url' => $contentData['content_url'] ?? null,
                'content_text' => $contentData['content_text'] ?? null,
                'media_urls' => $contentData['media_urls'] ?? null,
                'hashtags' => $contentData['hashtags'] ?? null,
                'status' => $campaign->requires_approval ? 'pending' : 'published',
                'published_at' => $campaign->requires_approval ? null : now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => $campaign->requires_approval ? 'محتوا برای بررسی ارسال شد' : 'محتوا منتشر شد',
                'data' => [
                    'content_id' => $content->id,
                    'content_type' => $content->content_type,
                    'platform' => $content->platform,
                    'status' => $content->status,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Content submission failed', [
                'campaign_id' => $campaignId,
                'partner_id' => $partnerId,
                'content_data' => $contentData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال محتوا: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Approve content
     */
    public function approveContent(int $contentId): array
    {
        try {
            $content = InfluencerContent::findOrFail($contentId);
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
     * Reject content
     */
    public function rejectContent(int $contentId, string $reason): array
    {
        try {
            $content = InfluencerContent::findOrFail($contentId);
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
     * Update content engagement metrics
     */
    public function updateContentMetrics(int $contentId, array $metrics): array
    {
        try {
            $content = InfluencerContent::findOrFail($contentId);
            $content->updateEngagementMetrics($metrics);

            return [
                'success' => true,
                'message' => 'آمار محتوا به‌روزرسانی شد',
                'data' => [
                    'content_id' => $content->id,
                    'metrics' => $content->getPerformanceMetrics(),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Content metrics update failed', [
                'content_id' => $contentId,
                'metrics' => $metrics,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی آمار محتوا: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get campaign details
     */
    public function getCampaign(int $campaignId): array
    {
        $campaign = InfluencerCampaign::with(['affiliatePartner.user:id,name'])
            ->findOrFail($campaignId);

        return [
            'success' => true,
            'message' => 'جزئیات کمپین دریافت شد',
            'data' => [
                'id' => $campaign->id,
                'campaign_name' => $campaign->campaign_name,
                'campaign_description' => $campaign->campaign_description,
                'campaign_type' => $campaign->campaign_type,
                'content_type' => $campaign->content_type,
                'required_posts' => $campaign->required_posts,
                'required_stories' => $campaign->required_stories,
                'compensation_per_post' => $campaign->compensation_per_post,
                'commission_rate' => $campaign->commission_rate,
                'start_date' => $campaign->start_date,
                'end_date' => $campaign->end_date,
                'status' => $campaign->status,
                'content_guidelines' => $campaign->content_guidelines,
                'hashtags' => $campaign->hashtags,
                'target_audience' => $campaign->target_audience,
                'requires_approval' => $campaign->requires_approval,
                'remaining_days' => $campaign->getRemainingDays(),
                'is_active' => $campaign->isActive(),
                'partner' => [
                    'id' => $campaign->affiliatePartner->id,
                    'name' => $campaign->affiliatePartner->user->name,
                    'tier' => $campaign->affiliatePartner->tier,
                ],
                'statistics' => $campaign->getStatistics(),
            ]
        ];
    }

    /**
     * Get partner's campaigns
     */
    public function getPartnerCampaigns(int $partnerId, int $limit = 20, int $offset = 0): array
    {
        $campaigns = InfluencerCampaign::byPartner($partnerId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($campaign) {
                return [
                    'id' => $campaign->id,
                    'campaign_name' => $campaign->campaign_name,
                    'campaign_type' => $campaign->campaign_type,
                    'content_type' => $campaign->content_type,
                    'compensation_per_post' => $campaign->compensation_per_post,
                    'start_date' => $campaign->start_date,
                    'end_date' => $campaign->end_date,
                    'status' => $campaign->status,
                    'remaining_days' => $campaign->getRemainingDays(),
                    'is_active' => $campaign->isActive(),
                    'statistics' => $campaign->getStatistics(),
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست کمپین‌های اینفلوئنسر دریافت شد',
            'data' => [
                'campaigns' => $campaigns,
                'total' => InfluencerCampaign::byPartner($partnerId)->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Get campaign content
     */
    public function getCampaignContent(int $campaignId, int $limit = 20, int $offset = 0): array
    {
        $content = InfluencerContent::byCampaign($campaignId)
            ->with(['affiliatePartner.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'content_type' => $item->content_type,
                    'platform' => $item->platform,
                    'content_url' => $item->content_url,
                    'content_text' => $item->content_text,
                    'media_urls' => $item->media_urls,
                    'hashtags' => $item->hashtags,
                    'status' => $item->status,
                    'published_at' => $item->published_at,
                    'approved_at' => $item->approved_at,
                    'rejection_reason' => $item->rejection_reason,
                    'metrics' => $item->getPerformanceMetrics(),
                    'partner' => [
                        'id' => $item->affiliatePartner->id,
                        'name' => $item->affiliatePartner->user->name,
                    ],
                ];
            });

        return [
            'success' => true,
            'message' => 'لیست محتوای کمپین دریافت شد',
            'data' => [
                'content' => $content,
                'total' => InfluencerContent::byCampaign($campaignId)->count(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];
    }

    /**
     * Get global influencer statistics
     */
    public function getGlobalStatistics(): array
    {
        $cacheKey = 'global_influencer_statistics';
        
        $stats = Cache::remember($cacheKey, 1800, function() {
            $campaigns = InfluencerCampaign::query();
            $content = InfluencerContent::query();
            
            return [
                'total_campaigns' => $campaigns->count(),
                'active_campaigns' => $campaigns->active()->count(),
                'completed_campaigns' => $campaigns->where('status', 'completed')->count(),
                'total_content' => $content->count(),
                'published_content' => $content->published()->count(),
                'pending_content' => $content->pending()->count(),
                'total_views' => $content->sum('views'),
                'total_likes' => $content->sum('likes'),
                'total_comments' => $content->sum('comments'),
                'total_shares' => $content->sum('shares'),
                'total_clicks' => $content->sum('clicks'),
                'total_conversions' => $content->sum('conversions'),
                'average_engagement_rate' => $content->avg('engagement_rate'),
                'by_campaign_type' => $campaigns->groupBy('campaign_type')->count(),
                'by_platform' => $content->groupBy('platform')->count(),
            ];
        });

        return [
            'success' => true,
            'message' => 'آمار کلی اینفلوئنسر دریافت شد',
            'data' => $stats
        ];
    }

    /**
     * Get campaign types
     */
    public function getCampaignTypes(): array
    {
        return [
            'success' => true,
            'message' => 'انواع کمپین دریافت شد',
            'data' => InfluencerCampaign::getCampaignTypes()
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
            'data' => InfluencerCampaign::getContentTypes()
        ];
    }

    /**
     * Get platforms
     */
    public function getPlatforms(): array
    {
        return [
            'success' => true,
            'message' => 'پلتفرم‌ها دریافت شد',
            'data' => InfluencerCampaign::getPlatforms()
        ];
    }

    /**
     * Get tier compensation rates
     */
    public function getTierCompensationRates(): array
    {
        return [
            'success' => true,
            'message' => 'نرخ‌های پرداخت دریافت شد',
            'data' => InfluencerCampaign::getTierCompensationRates()
        ];
    }
}
