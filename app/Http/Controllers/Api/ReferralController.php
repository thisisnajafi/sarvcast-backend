<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Models\Referral;
use App\Services\CoinService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    protected $coinService;
    protected $referralService;

    public function __construct(CoinService $coinService, ReferralService $referralService)
    {
        $this->coinService = $coinService;
        $this->referralService = $referralService;
    }

    /**
     * Get user's referral code
     */
    public function getReferralCode(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->referralService->generateReferralCode($userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get user's referral statistics
     */
    public function getReferralStatistics(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->referralService->getUserReferralStatistics($userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get user's referrals list
     */
    public function getReferrals(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $result = $this->referralService->getUserReferrals($userId, $limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Use referral code during registration
     */
    public function useReferralCode(Request $request): JsonResponse
    {
        $request->validate([
            'referral_code' => 'required|string|exists:referral_codes,code',
        ]);

        $userId = Auth::id();
        $result = $this->referralService->processReferralCode($request->referral_code, $userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Check referral completion status
     */
    public function checkReferralCompletion(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->referralService->checkReferralCompletion($userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get global referral statistics (Admin only)
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

        $result = $this->referralService->getGlobalReferralStatistics();
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get top referrers (Admin only)
     */
    public function getTopReferrers(Request $request): JsonResponse
    {
        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $limit = $request->get('limit', 10);
        $result = $this->referralService->getTopReferrers($limit);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
