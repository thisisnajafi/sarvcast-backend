<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CoinAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoinAnalyticsController extends Controller
{
    protected $coinAnalyticsService;

    public function __construct(CoinAnalyticsService $coinAnalyticsService)
    {
        $this->coinAnalyticsService = $coinAnalyticsService;
    }

    public function getOverview(): JsonResponse
    {
        try {
            $overview = $this->coinAnalyticsService->getCoinSystemOverview();
            
            return response()->json([
                'success' => true,
                'message' => 'آمار کلی سیستم سکه با موفقیت دریافت شد',
                'data' => $overview
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting coin system overview: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار کلی سیستم سکه'
            ], 500);
        }
    }

    public function getEarningSources(): JsonResponse
    {
        try {
            $sources = $this->coinAnalyticsService->getCoinEarningSources();
            
            return response()->json([
                'success' => true,
                'message' => 'منابع کسب سکه با موفقیت دریافت شد',
                'data' => $sources
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting coin earning sources: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت منابع کسب سکه'
            ], 500);
        }
    }

    public function getSpendingPatterns(): JsonResponse
    {
        try {
            $patterns = $this->coinAnalyticsService->getCoinSpendingPatterns();
            
            return response()->json([
                'success' => true,
                'message' => 'الگوهای خرج کردن سکه با موفقیت دریافت شد',
                'data' => $patterns
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting coin spending patterns: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت الگوهای خرج کردن سکه'
            ], 500);
        }
    }

    public function getTransactionTrends(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $trends = $this->coinAnalyticsService->getCoinTransactionTrends($days);
            
            return response()->json([
                'success' => true,
                'message' => 'روند تراکنش‌های سکه با موفقیت دریافت شد',
                'data' => $trends
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting coin transaction trends: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت روند تراکنش‌های سکه'
            ], 500);
        }
    }

    public function getUserDistribution(): JsonResponse
    {
        try {
            $distribution = $this->coinAnalyticsService->getUserCoinDistribution();
            
            return response()->json([
                'success' => true,
                'message' => 'توزیع سکه کاربران با موفقیت دریافت شد',
                'data' => $distribution
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting user coin distribution: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت توزیع سکه کاربران'
            ], 500);
        }
    }

    public function getQuizPerformance(): JsonResponse
    {
        try {
            $performance = $this->coinAnalyticsService->getQuizCoinPerformance();
            
            return response()->json([
                'success' => true,
                'message' => 'عملکرد سکه در آزمون‌ها با موفقیت دریافت شد',
                'data' => $performance
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting quiz coin performance: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت عملکرد سکه در آزمون‌ها'
            ], 500);
        }
    }

    public function getReferralPerformance(): JsonResponse
    {
        try {
            $performance = $this->coinAnalyticsService->getReferralCoinPerformance();
            
            return response()->json([
                'success' => true,
                'message' => 'عملکرد سکه در ارجاعات با موفقیت دریافت شد',
                'data' => $performance
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral coin performance: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت عملکرد سکه در ارجاعات'
            ], 500);
        }
    }

    public function getTopEarners(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $earners = $this->coinAnalyticsService->getTopCoinEarners($limit);
            
            return response()->json([
                'success' => true,
                'message' => 'برترین کسب‌کنندگان سکه با موفقیت دریافت شد',
                'data' => $earners
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting top coin earners: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت برترین کسب‌کنندگان سکه'
            ], 500);
        }
    }

    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = $this->coinAnalyticsService->getCoinSystemHealth();
            
            return response()->json([
                'success' => true,
                'message' => 'سلامت سیستم سکه با موفقیت دریافت شد',
                'data' => $health
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting coin system health: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت سلامت سیستم سکه'
            ], 500);
        }
    }

    public function getComprehensiveReport(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $limit = $request->get('limit', 10);
            
            $report = [
                'overview' => $this->coinAnalyticsService->getCoinSystemOverview(),
                'earning_sources' => $this->coinAnalyticsService->getCoinEarningSources(),
                'spending_patterns' => $this->coinAnalyticsService->getCoinSpendingPatterns(),
                'transaction_trends' => $this->coinAnalyticsService->getCoinTransactionTrends($days),
                'user_distribution' => $this->coinAnalyticsService->getUserCoinDistribution(),
                'quiz_performance' => $this->coinAnalyticsService->getQuizCoinPerformance(),
                'referral_performance' => $this->coinAnalyticsService->getReferralCoinPerformance(),
                'top_earners' => $this->coinAnalyticsService->getTopCoinEarners($limit),
                'system_health' => $this->coinAnalyticsService->getCoinSystemHealth(),
                'generated_at' => now()->toISOString(),
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'گزارش جامع سیستم سکه با موفقیت دریافت شد',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting comprehensive coin report: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت گزارش جامع سیستم سکه'
            ], 500);
        }
    }
}
