<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InfluencerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InfluencerController extends Controller
{
    protected $influencerService;

    public function __construct(InfluencerService $influencerService)
    {
        $this->influencerService = $influencerService;
    }

    /**
     * Create influencer campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        $request->validate([
            'partner_id' => 'required|integer|exists:affiliate_partners,id',
            'campaign_name' => 'required|string|max:255',
            'campaign_description' => 'required|string|max:1000',
            'campaign_type' => 'required|string|in:story_review,educational_content,cultural_preservation,brand_partnership',
            'content_type' => 'required|string|in:post,story,reel,video,live',
            'required_posts' => 'nullable|integer|min:1|max:50',
            'required_stories' => 'nullable|integer|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'content_guidelines' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'target_audience' => 'nullable|array',
            'requires_approval' => 'nullable|boolean',
        ]);

        $result = $this->influencerService->createCampaign($request->partner_id, $request->all());
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Submit content for campaign
     */
    public function submitContent(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_id' => 'required|integer|exists:influencer_campaigns,id',
            'partner_id' => 'required|integer|exists:affiliate_partners,id',
            'content_type' => 'required|string|in:post,story,reel,video,live',
            'platform' => 'required|string|in:instagram,telegram,youtube,tiktok,twitter,linkedin',
            'content_url' => 'nullable|url',
            'content_text' => 'nullable|string|max:2000',
            'media_urls' => 'nullable|array',
            'hashtags' => 'nullable|array',
        ]);

        $result = $this->influencerService->submitContent(
            $request->campaign_id,
            $request->partner_id,
            $request->all()
        );
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Approve content (Admin only)
     */
    public function approveContent(int $contentId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->influencerService->approveContent($contentId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Reject content (Admin only)
     */
    public function rejectContent(Request $request, int $contentId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $result = $this->influencerService->rejectContent($contentId, $request->reason);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Update content engagement metrics
     */
    public function updateContentMetrics(Request $request, int $contentId): JsonResponse
    {
        $request->validate([
            'views' => 'nullable|integer|min:0',
            'likes' => 'nullable|integer|min:0',
            'comments' => 'nullable|integer|min:0',
            'shares' => 'nullable|integer|min:0',
            'clicks' => 'nullable|integer|min:0',
            'conversions' => 'nullable|integer|min:0',
        ]);

        $result = $this->influencerService->updateContentMetrics($contentId, $request->all());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get campaign details
     */
    public function getCampaign(int $campaignId): JsonResponse
    {
        $result = $this->influencerService->getCampaign($campaignId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get partner's campaigns
     */
    public function getPartnerCampaigns(Request $request, int $partnerId): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $result = $this->influencerService->getPartnerCampaigns($partnerId, $limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get campaign content
     */
    public function getCampaignContent(Request $request, int $campaignId): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $result = $this->influencerService->getCampaignContent($campaignId, $limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get campaign types
     */
    public function getCampaignTypes(): JsonResponse
    {
        $result = $this->influencerService->getCampaignTypes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get content types
     */
    public function getContentTypes(): JsonResponse
    {
        $result = $this->influencerService->getContentTypes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get platforms
     */
    public function getPlatforms(): JsonResponse
    {
        $result = $this->influencerService->getPlatforms();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get tier compensation rates
     */
    public function getTierCompensationRates(): JsonResponse
    {
        $result = $this->influencerService->getTierCompensationRates();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get global influencer statistics (Admin only)
     */
    public function getGlobalStatistics(): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->influencerService->getGlobalStatistics();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
