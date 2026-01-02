<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\StoryComment;
use App\Models\PlayHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    /**
     * Get dashboard statistics (API endpoint)
     */
    public function getStats(Request $request)
    {
        $dateRange = $request->get('date_range', '30days');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        $startDate = $this->getStartDate($dateRange, $dateFrom);
        $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now();
        
        $cacheKey = 'api_dashboard_stats_' . md5(json_encode([
            'date_range' => $dateRange,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => auth()->id()
        ]));
        
        $stats = Cache::remember($cacheKey, 300, function() use ($startDate, $endDate) {
            return [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('status', 'active')->count(),
                    'new_today' => User::whereDate('created_at', today())->count(),
                    'new_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                ],
                'stories' => [
                    'total' => Story::count(),
                    'published' => Story::where('status', 'published')->count(),
                    'pending' => Story::where('status', 'pending')->count(),
                ],
                'episodes' => [
                    'total' => Episode::count(),
                    'published' => Episode::where('status', 'published')->count(),
                ],
                'revenue' => [
                    'total' => Payment::where('status', 'completed')->sum('amount'),
                    'today' => Payment::where('status', 'completed')->whereDate('created_at', today())->sum('amount'),
                    'this_week' => Payment::where('status', 'completed')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
                    'this_month' => Payment::where('status', 'completed')->whereMonth('created_at', now()->month)->sum('amount'),
                ],
                'engagement' => [
                    'total_comments' => StoryComment::count(),
                    'total_plays' => PlayHistory::count(),
                    'plays_today' => PlayHistory::whereDate('created_at', today())->count(),
                ],
                'subscriptions' => [
                    'total' => Subscription::count(),
                    'active' => Subscription::where('status', 'active')->count(),
                ],
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $stats,
            'cached' => Cache::has($cacheKey)
        ]);
    }
    
    /**
     * Get chart data (API endpoint)
     */
    public function getChartData(Request $request)
    {
        $chartType = $request->get('type', 'revenue');
        $dateRange = $request->get('date_range', '30days');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        $startDate = $this->getStartDate($dateRange, $dateFrom);
        $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now();
        
        $cacheKey = 'api_dashboard_chart_' . $chartType . '_' . md5($startDate . $endDate);
        
        $data = Cache::remember($cacheKey, 300, function() use ($chartType, $startDate, $endDate) {
            switch ($chartType) {
                case 'revenue':
                    return Payment::where('status', 'completed')
                        ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->groupBy('date')
                        ->orderBy('date', 'asc')
                        ->get();
                        
                case 'users':
                    return User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->groupBy('date')
                        ->orderBy('date', 'asc')
                        ->get();
                        
                case 'plays':
                    return PlayHistory::selectRaw('DATE(played_at) as date, COUNT(*) as count')
                        ->whereBetween('played_at', [$startDate, $endDate])
                        ->groupBy('date')
                        ->orderBy('date', 'asc')
                        ->get();
                        
                default:
                    return [];
            }
        });
        
        return response()->json([
            'success' => true,
            'type' => $chartType,
            'data' => $data
        ]);
    }
    
    /**
     * Get start date based on date range
     */
    private function getStartDate($dateRange, $customDateFrom = null)
    {
        if ($dateRange === 'custom' && $customDateFrom) {
            return Carbon::parse($customDateFrom)->startOfDay();
        }

        switch ($dateRange) {
            case 'today':
                return today()->startOfDay();
            case '7days':
                return now()->subDays(7)->startOfDay();
            case '30days':
                return now()->subDays(30)->startOfDay();
            case '3months':
                return now()->subMonths(3)->startOfDay();
            case '1year':
                return now()->subYear()->startOfDay();
            default:
                return now()->subDays(30)->startOfDay();
        }
    }
}

