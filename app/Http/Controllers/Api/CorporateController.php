<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CorporateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CorporateController extends Controller
{
    protected $corporateService;

    public function __construct(CorporateService $corporateService)
    {
        $this->corporateService = $corporateService;
    }

    /**
     * Create corporate sponsorship
     */
    public function createSponsorship(Request $request): JsonResponse
    {
        $request->validate([
            'partner_id' => 'required|integer|exists:affiliate_partners,id',
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|string|in:tech,education,media,cultural,entertainment,healthcare,finance,retail,other',
            'industry' => 'required|string|in:technology,education,entertainment,cultural,healthcare,finance,retail,manufacturing,services,other',
            'company_size' => 'required|string|in:startup,small,medium,large,enterprise',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'website_url' => 'nullable|url|max:255',
            'company_description' => 'required|string|max:1000',
            'sponsorship_type' => 'required|string|in:content_sponsorship,brand_partnership,educational_initiative,cultural_preservation,technology_partnership',
            'sponsorship_amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|in:IRR,USD,EUR',
            'payment_frequency' => 'required|string|in:one_time,monthly,quarterly,annually',
            'sponsorship_start_date' => 'required|date',
            'sponsorship_end_date' => 'required|date|after:sponsorship_start_date',
            'sponsorship_benefits' => 'nullable|array',
            'content_requirements' => 'nullable|array',
            'target_audience' => 'nullable|array',
            'requires_content_approval' => 'nullable|boolean',
            'allows_brand_mention' => 'nullable|boolean',
            'requires_logo_display' => 'nullable|boolean',
            'special_requirements' => 'nullable|string|max:1000',
            'verification_documents' => 'nullable|array',
        ]);

        $result = $this->corporateService->createSponsorship($request->partner_id, $request->all());
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Create sponsored content
     */
    public function createContent(Request $request): JsonResponse
    {
        $request->validate([
            'sponsorship_id' => 'required|integer|exists:corporate_sponsorships,id',
            'story_id' => 'nullable|integer|exists:stories,id',
            'episode_id' => 'nullable|integer|exists:episodes,id',
            'content_type' => 'required|string|in:story,episode,banner,popup,notification,dedicated_content',
            'content_title' => 'required|string|max:255',
            'content_description' => 'required|string|max:1000',
            'sponsor_message' => 'nullable|string|max:500',
            'brand_logo_url' => 'nullable|url|max:255',
            'brand_website_url' => 'nullable|url|max:255',
            'content_media' => 'nullable|array',
            'placement_type' => 'required|string|in:pre_roll,mid_roll,post_roll,banner,popup,notification,dedicated_content',
            'display_duration' => 'nullable|integer|min:1',
            'display_frequency' => 'nullable|integer|min:1|max:10',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $result = $this->corporateService->createContent($request->sponsorship_id, $request->all());
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Approve sponsored content (Admin only)
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

        $result = $this->corporateService->approveContent($contentId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Reject sponsored content (Admin only)
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

        $result = $this->corporateService->rejectContent($contentId, $request->reason);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Record analytics event
     */
    public function recordEvent(Request $request, int $contentId): JsonResponse
    {
        $request->validate([
            'event_type' => 'required|string|in:impression,click,conversion,view_complete,skip,hover,focus',
            'user_id' => 'nullable|integer|exists:users,id',
            'metadata' => 'nullable|array',
        ]);

        $result = $this->corporateService->recordEvent(
            $contentId,
            $request->event_type,
            $request->user_id,
            $request->metadata ?? []
        );
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get sponsorship details
     */
    public function getSponsorship(int $sponsorshipId): JsonResponse
    {
        $result = $this->corporateService->getSponsorship($sponsorshipId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get sponsorship content
     */
    public function getSponsorshipContent(Request $request, int $sponsorshipId): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $result = $this->corporateService->getSponsorshipContent($sponsorshipId, $limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get content analytics
     */
    public function getContentAnalytics(Request $request, int $contentId): JsonResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $result = $this->corporateService->getContentAnalytics($contentId, $startDate, $endDate);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get sponsorship analytics
     */
    public function getSponsorshipAnalytics(Request $request, int $sponsorshipId): JsonResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        $result = $this->corporateService->getSponsorshipAnalytics($sponsorshipId, $startDate, $endDate);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get sponsorship types
     */
    public function getSponsorshipTypes(): JsonResponse
    {
        $result = $this->corporateService->getSponsorshipTypes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get company types
     */
    public function getCompanyTypes(): JsonResponse
    {
        $result = $this->corporateService->getCompanyTypes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get industries
     */
    public function getIndustries(): JsonResponse
    {
        $result = $this->corporateService->getIndustries();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get company sizes
     */
    public function getCompanySizes(): JsonResponse
    {
        $result = $this->corporateService->getCompanySizes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get payment frequencies
     */
    public function getPaymentFrequencies(): JsonResponse
    {
        $result = $this->corporateService->getPaymentFrequencies();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get sponsorship benefits
     */
    public function getSponsorshipBenefits(): JsonResponse
    {
        $result = $this->corporateService->getSponsorshipBenefits();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get content types
     */
    public function getContentTypes(): JsonResponse
    {
        $result = $this->corporateService->getContentTypes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get placement types
     */
    public function getPlacementTypes(): JsonResponse
    {
        $result = $this->corporateService->getPlacementTypes();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get global corporate statistics (Admin only)
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

        $result = $this->corporateService->getGlobalStatistics();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
