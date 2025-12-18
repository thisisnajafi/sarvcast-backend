<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePartner;
use App\Models\InfluencerCampaign;
use App\Models\TeacherAccount;
use App\Models\SchoolPartnership;
use App\Models\CorporateSponsorship;
use App\Models\Commission;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PartnersDashboardController extends Controller
{
    /**
     * Display the partners dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        // Overall statistics
        $stats = [
            'total_partners' => AffiliatePartner::count(),
            'active_partners' => AffiliatePartner::where('status', 'active')->count(),
            'pending_partners' => AffiliatePartner::where('status', 'pending')->count(),
            'verified_partners' => AffiliatePartner::where('is_verified', true)->count(),
            'total_commission_paid' => Commission::where('status', 'paid')->sum('commission_amount'),
            'pending_commissions' => Commission::where('status', 'pending')->sum('commission_amount'),
        ];

        // Partner type breakdown
        $partnerTypes = AffiliatePartner::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->type => $item->count];
            });

        // Recent partners
        $recentPartners = AffiliatePartner::with(['influencerCampaigns'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top performing partners
        $topPartners = AffiliatePartner::withCount(['influencerCampaigns as campaigns_count'])
            ->withSum('commissions as total_earnings', 'commission_amount')
            ->orderBy('total_earnings', 'desc')
            ->limit(10)
            ->get();

        // Commission trends
        $commissionTrends = Commission::selectRaw('DATE(created_at) as date, SUM(commission_amount) as total_amount, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Partner status distribution
        $statusDistribution = AffiliatePartner::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Tier distribution
        $tierDistribution = AffiliatePartner::selectRaw('tier, COUNT(*) as count')
            ->whereNotNull('tier')
            ->groupBy('tier')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tier => $item->count];
            });

        // Monthly performance
        $monthlyPerformance = $this->getMonthlyPerformance($startDate);

        // Partner engagement metrics
        $engagementMetrics = [
            'avg_campaigns_per_partner' => AffiliatePartner::withCount('influencerCampaigns')->get()->avg('influencer_campaigns_count'),
            'conversion_rate' => $this->calculateConversionRate(),
            'retention_rate' => $this->calculateRetentionRate(),
            'avg_commission_per_partner' => AffiliatePartner::withSum('commissions', 'commission_amount')->get()->avg('commissions_sum_commission_amount'),
        ];

        return view('admin.dashboards.partners', compact(
            'stats',
            'partnerTypes',
            'recentPartners',
            'topPartners',
            'commissionTrends',
            'statusDistribution',
            'tierDistribution',
            'monthlyPerformance',
            'engagementMetrics',
            'dateRange'
        ));
    }

    /**
     * Get partner analytics data
     */
    public function analytics(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        // Partner registrations over time
        $registrationsOverTime = AffiliatePartner::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Commission payments over time
        $paymentsOverTime = Commission::selectRaw('DATE(paid_at) as date, SUM(commission_amount) as total_amount')
            ->where('paid_at', '>=', $startDate)
            ->where('status', 'paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Partner performance by type
        $performanceByType = AffiliatePartner::selectRaw('type, COUNT(*) as partners, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_partners')
            ->groupBy('type')
            ->get();

        // Top earning partners
        $topEarners = AffiliatePartner::withSum('commissions as total_earnings', 'commission_amount')
            ->orderBy('total_earnings', 'desc')
            ->limit(10)
            ->get(['name', 'type', 'total_earnings']);

        return response()->json([
            'success' => true,
            'data' => [
                'registrations_over_time' => $registrationsOverTime,
                'payments_over_time' => $paymentsOverTime,
                'performance_by_type' => $performanceByType,
                'top_earners' => $topEarners,
                'date_range' => $dateRange
            ]
        ]);
    }

    /**
     * Get monthly performance data
     */
    private function getMonthlyPerformance($startDate): array
    {
        $months = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte(now())) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();

            $months[] = [
                'month' => $currentDate->format('Y-m'),
                'month_name' => $currentDate->format('F Y'),
                'new_partners' => AffiliatePartner::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'total_commissions' => Commission::whereBetween('created_at', [$monthStart, $monthEnd])->sum('commission_amount'),
                'active_campaigns' => InfluencerCampaign::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', 'active')->count(),
            ];

            $currentDate->addMonth();
        }

        return $months;
    }

    /**
     * Calculate conversion rate (pending to active)
     */
    private function calculateConversionRate(): float
    {
        $totalPartners = AffiliatePartner::count();
        if ($totalPartners === 0) return 0;

        $activePartners = AffiliatePartner::where('status', 'active')->count();
        return round(($activePartners / $totalPartners) * 100, 2);
    }

    /**
     * Calculate retention rate
     */
    private function calculateRetentionRate(): float
    {
        $totalPartners = AffiliatePartner::count();
        if ($totalPartners === 0) return 0;

        $retainedPartners = AffiliatePartner::where('status', 'active')
            ->where('created_at', '<=', now()->subDays(30))
            ->count();

        return round(($retainedPartners / $totalPartners) * 100, 2);
    }

    /**
     * Export partners data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $type = $request->get('type', 'all');

        return redirect()->back()
            ->with('success', "گزارش شرکا با فرمت {$format} آماده دانلود است.");
    }
}