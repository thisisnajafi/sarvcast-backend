<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AffiliateController extends Controller
{
    protected $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Create new affiliate partner
     */
    public function createPartner(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:affiliate_partners,email',
            'phone' => 'nullable|string|max:20',
            'type' => 'required|string|in:teacher,influencer,school,corporate',
            'tier' => 'nullable|string|in:micro,mid,macro,enterprise',
            'follower_count' => 'nullable|integer|min:0',
            'social_media_handle' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'verification_documents' => 'nullable|array',
        ]);

        $result = $this->affiliateService->createAffiliatePartner($request->all());
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Verify affiliate partner (Admin only)
     */
    public function verifyPartner(int $partnerId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->affiliateService->verifyAffiliatePartner($partnerId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Suspend affiliate partner (Admin only)
     */
    public function suspendPartner(Request $request, int $partnerId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->affiliateService->suspendAffiliatePartner($partnerId, $request->reason);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Create commission for subscription
     */
    public function createCommission(Request $request): JsonResponse
    {
        $request->validate([
            'partner_id' => 'required|integer|exists:affiliate_partners,id',
            'user_id' => 'required|integer|exists:users,id',
            'subscription_id' => 'required|integer|exists:subscriptions,id',
        ]);

        $result = $this->affiliateService->createCommission(
            $request->partner_id,
            $request->user_id,
            $request->subscription_id
        );
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Approve commission (Admin only)
     */
    public function approveCommission(int $commissionId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->affiliateService->approveCommission($commissionId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Mark commission as paid (Admin only)
     */
    public function markCommissionAsPaid(int $commissionId): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->affiliateService->markCommissionAsPaid($commissionId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get partner statistics
     */
    public function getPartnerStatistics(Request $request, int $partnerId): JsonResponse
    {
        $months = $request->get('months', 12);
        $result = $this->affiliateService->getPartnerStatistics($partnerId, $months);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get global affiliate statistics (Admin only)
     */
    public function getGlobalStatistics(Request $request): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $months = $request->get('months', 12);
        $result = $this->affiliateService->getGlobalStatistics($months);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get partners by type
     */
    public function getPartnersByType(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:teacher,influencer,school,corporate',
        ]);

        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $result = $this->affiliateService->getPartnersByType($type, $limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get pending commissions (Admin only)
     */
    public function getPendingCommissions(Request $request): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $result = $this->affiliateService->getPendingCommissions($limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Process bulk commission approvals (Admin only)
     */
    public function processBulkCommissionApprovals(Request $request): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'integer|exists:commissions,id',
        ]);

        $result = $this->affiliateService->processBulkCommissionApprovals($request->commission_ids);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get affiliate program requirements
     */
    public function getProgramRequirements(): JsonResponse
    {
        $requirements = AffiliatePartner::getTypeRequirements();
        
        return response()->json([
            'success' => true,
            'message' => 'الزامات برنامه وابسته دریافت شد',
            'data' => $requirements
        ]);
    }

    /**
     * Get tier commission rates
     */
    public function getTierCommissionRates(): JsonResponse
    {
        $rates = AffiliatePartner::getTierCommissionRates();
        
        return response()->json([
            'success' => true,
            'message' => 'نرخ‌های کمیسیون دریافت شد',
            'data' => $rates
        ]);
    }
}
