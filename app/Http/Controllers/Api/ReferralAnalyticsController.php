<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferralAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralAnalyticsController extends Controller
{
    protected $referralAnalyticsService;

    public function __construct(ReferralAnalyticsService $referralAnalyticsService)
    {
        $this->referralAnalyticsService = $referralAnalyticsService;
    }

    public function getOverview(): JsonResponse
    {
        try {
            $overview = $this->referralAnalyticsService->getReferralSystemOverview();
            
            return response()->json([
                'success' => true,
                'message' => 'آمار کلی سیستم ارجاع با موفقیت دریافت شد',
                'data' => $overview
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral system overview: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار کلی سیستم ارجاع'
            ], 500);
        }
    }

    public function getTrends(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $trends = $this->referralAnalyticsService->getReferralTrends($days);
            
            return response()->json([
                'success' => true,
                'message' => 'روند ارجاعات با موفقیت دریافت شد',
                'data' => $trends
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral trends: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت روند ارجاعات'
            ], 500);
        }
    }

    public function getTopReferrers(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $referrers = $this->referralAnalyticsService->getTopReferrers($limit);
            
            return response()->json([
                'success' => true,
                'message' => 'برترین ارجاع‌دهندگان با موفقیت دریافت شد',
                'data' => $referrers
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting top referrers: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت برترین ارجاع‌دهندگان'
            ], 500);
        }
    }

    public function getSources(): JsonResponse
    {
        try {
            $sources = $this->referralAnalyticsService->getReferralSources();
            
            return response()->json([
                'success' => true,
                'message' => 'منابع ارجاع با موفقیت دریافت شد',
                'data' => $sources
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral sources: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت منابع ارجاع'
            ], 500);
        }
    }

    public function getPerformanceByTimeframe(): JsonResponse
    {
        try {
            $performance = $this->referralAnalyticsService->getReferralPerformanceByTimeframe();
            
            return response()->json([
                'success' => true,
                'message' => 'عملکرد ارجاع بر اساس بازه زمانی با موفقیت دریافت شد',
                'data' => $performance
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral performance by timeframe: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت عملکرد ارجاع بر اساس بازه زمانی'
            ], 500);
        }
    }

    public function getFunnelAnalysis(): JsonResponse
    {
        try {
            $funnel = $this->referralAnalyticsService->getReferralFunnelAnalysis();
            
            return response()->json([
                'success' => true,
                'message' => 'تحلیل قیف ارجاع با موفقیت دریافت شد',
                'data' => $funnel
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral funnel analysis: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تحلیل قیف ارجاع'
            ], 500);
        }
    }

    public function getGeographicDistribution(): JsonResponse
    {
        try {
            $distribution = $this->referralAnalyticsService->getReferralGeographicDistribution();
            
            return response()->json([
                'success' => true,
                'message' => 'توزیع جغرافیایی ارجاعات با موفقیت دریافت شد',
                'data' => $distribution
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral geographic distribution: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت توزیع جغرافیایی ارجاعات'
            ], 500);
        }
    }

    public function getRevenueAnalysis(): JsonResponse
    {
        try {
            $revenue = $this->referralAnalyticsService->getReferralRevenueAnalysis();
            
            return response()->json([
                'success' => true,
                'message' => 'تحلیل درآمد ارجاعات با موفقیت دریافت شد',
                'data' => $revenue
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral revenue analysis: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تحلیل درآمد ارجاعات'
            ], 500);
        }
    }

    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = $this->referralAnalyticsService->getReferralSystemHealth();
            
            return response()->json([
                'success' => true,
                'message' => 'سلامت سیستم ارجاع با موفقیت دریافت شد',
                'data' => $health
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting referral system health: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت سلامت سیستم ارجاع'
            ], 500);
        }
    }

    public function getComprehensiveReport(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $limit = $request->get('limit', 10);
            
            $report = [
                'overview' => $this->referralAnalyticsService->getReferralSystemOverview(),
                'trends' => $this->referralAnalyticsService->getReferralTrends($days),
                'top_referrers' => $this->referralAnalyticsService->getTopReferrers($limit),
                'sources' => $this->referralAnalyticsService->getReferralSources(),
                'performance_by_timeframe' => $this->referralAnalyticsService->getReferralPerformanceByTimeframe(),
                'funnel_analysis' => $this->referralAnalyticsService->getReferralFunnelAnalysis(),
                'geographic_distribution' => $this->referralAnalyticsService->getReferralGeographicDistribution(),
                'revenue_analysis' => $this->referralAnalyticsService->getReferralRevenueAnalysis(),
                'system_health' => $this->referralAnalyticsService->getReferralSystemHealth(),
                'generated_at' => now()->toISOString(),
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'گزارش جامع سیستم ارجاع با موفقیت دریافت شد',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            \Log::error("Error getting comprehensive referral report: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت گزارش جامع سیستم ارجاع'
            ], 500);
        }
    }
}
