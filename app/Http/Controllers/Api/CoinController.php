<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CoinService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CoinController extends Controller
{
    protected $coinService;

    public function __construct(CoinService $coinService)
    {
        $this->coinService = $coinService;
    }

    /**
     * Get user's coin balance
     */
    public function getBalance(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->coinService->getUserBalance($userId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get user's coin transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);
        
        $result = $this->coinService->getUserTransactions($userId, $limit, $offset);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get user's coin statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $days = $request->get('days', 30);
        
        $result = $this->coinService->getUserStatistics($userId, $days);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Spend coins for redemption
     */
    public function spendCoins(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'source_type' => 'required|string',
            'source_id' => 'nullable|integer',
            'description' => 'required|string',
        ]);

        $userId = Auth::id();
        $result = $this->coinService->spendCoins(
            $userId,
            $request->amount,
            $request->source_type,
            $request->source_id,
            $request->description,
            $request->get('metadata', [])
        );
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get coin redemption options
     */
    public function getRedemptionOptions(): JsonResponse
    {
        $options = $this->coinService->getRedemptionOptions();
        
        return response()->json([
            'success' => true,
            'message' => 'گزینه‌های تبدیل سکه دریافت شد',
            'data' => $options
        ]);
    }

    /**
     * Award coins (Admin only)
     */
    public function awardCoins(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|integer|min:1',
            'source_type' => 'required|string',
            'source_id' => 'nullable|integer',
            'description' => 'required|string',
        ]);

        // Check if user is admin
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $result = $this->coinService->awardCoins(
            $request->user_id,
            $request->amount,
            $request->source_type,
            $request->source_id,
            $request->description,
            $request->get('metadata', [])
        );
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get global coin statistics (Admin only)
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

        $days = $request->get('days', 30);
        $result = $this->coinService->getGlobalStatistics($days);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
