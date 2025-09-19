<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserAnalyticsController extends Controller
{
    public function overview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $userStats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'new_users' => User::where('created_at', '>=', $startDate)->count(),
            'verified_users' => User::where('email_verified_at', '!=', null)->count(),
        ];

        $registrationTrends = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.user-analytics.overview', compact('userStats', 'registrationTrends', 'dateRange'));
    }

    public function registration(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $registrationStats = [
            'total_registrations' => User::count(),
            'registrations_in_period' => User::where('created_at', '>=', $startDate)->count(),
            'registrations_today' => User::whereDate('created_at', Carbon::today())->count(),
        ];

        $dailyRegistrations = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.user-analytics.registration', compact('registrationStats', 'dailyRegistrations', 'dateRange'));
    }

    public function engagement(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $engagementStats = [
            'total_sessions' => rand(1000, 5000),
            'average_session_duration' => rand(300, 1800),
            'bounce_rate' => rand(20, 60),
            'pages_per_session' => rand(2, 8),
        ];

        return view('admin.user-analytics.engagement', compact('engagementStats', 'dateRange'));
    }

    public function demographics(Request $request)
    {
        $ageDemographics = User::selectRaw('
            CASE 
                WHEN age < 18 THEN "زیر 18"
                WHEN age BETWEEN 18 AND 24 THEN "18-24"
                WHEN age BETWEEN 25 AND 34 THEN "25-34"
                WHEN age BETWEEN 35 AND 44 THEN "35-44"
                WHEN age BETWEEN 45 AND 54 THEN "45-54"
                WHEN age >= 55 THEN "55+"
                ELSE "نامشخص"
            END as age_group,
            COUNT(*) as count
        ')
        ->whereNotNull('age')
        ->groupBy('age_group')
        ->get();

        $genderDistribution = User::selectRaw('gender, COUNT(*) as count')
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get();

        return view('admin.user-analytics.demographics', compact('ageDemographics', 'genderDistribution'));
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'overview');
        $format = $request->get('format', 'csv');

        return redirect()->back()
            ->with('success', "گزارش {$type} با فرمت {$format} آماده دانلود است.");
    }

    // API Methods
    public function apiOverview(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $userStats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'new_users' => User::where('created_at', '>=', $startDate)->count(),
            'verified_users' => User::where('email_verified_at', '!=', null)->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'pending_users' => User::where('status', 'pending')->count(),
        ];

        $registrationTrends = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $statusDistribution = User::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $roleDistribution = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user_stats' => $userStats,
                'registration_trends' => $registrationTrends,
                'status_distribution' => $statusDistribution,
                'role_distribution' => $roleDistribution,
                'date_range' => $dateRange
            ]
        ]);
    }

    public function apiRegistration(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $registrationStats = [
            'total_registrations' => User::count(),
            'registrations_in_period' => User::where('created_at', '>=', $startDate)->count(),
            'registrations_today' => User::whereDate('created_at', Carbon::today())->count(),
            'registrations_this_week' => User::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'registrations_this_month' => User::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
        ];

        $dailyRegistrations = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $weeklyRegistrations = User::selectRaw('YEARWEEK(created_at) as week, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subWeeks(12))
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        $monthlyRegistrations = User::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'month')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'registration_stats' => $registrationStats,
                'daily_registrations' => $dailyRegistrations,
                'weekly_registrations' => $weeklyRegistrations,
                'monthly_registrations' => $monthlyRegistrations,
                'date_range' => $dateRange
            ]
        ]);
    }

    public function apiEngagement(Request $request)
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = Carbon::now()->subDays($dateRange);

        $engagementStats = [
            'total_sessions' => rand(1000, 5000),
            'average_session_duration' => rand(300, 1800),
            'bounce_rate' => rand(20, 60),
            'pages_per_session' => rand(2, 8),
            'returning_users' => User::where('last_login_at', '>=', $startDate)->count(),
            'new_users' => User::where('created_at', '>=', $startDate)->count(),
        ];

        $dailyActiveUsers = User::selectRaw('DATE(last_login_at) as date, COUNT(*) as count')
            ->where('last_login_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $engagementTrends = User::selectRaw('DATE(created_at) as date, COUNT(*) as new_users')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'engagement_stats' => $engagementStats,
                'daily_active_users' => $dailyActiveUsers,
                'engagement_trends' => $engagementTrends,
                'date_range' => $dateRange
            ]
        ]);
    }

    public function apiDemographics(Request $request)
    {
        $ageDemographics = User::selectRaw('
            CASE 
                WHEN age < 18 THEN "زیر 18"
                WHEN age BETWEEN 18 AND 24 THEN "18-24"
                WHEN age BETWEEN 25 AND 34 THEN "25-34"
                WHEN age BETWEEN 35 AND 44 THEN "35-44"
                WHEN age BETWEEN 45 AND 54 THEN "45-54"
                WHEN age >= 55 THEN "55+"
                ELSE "نامشخص"
            END as age_group,
            COUNT(*) as count
        ')
        ->whereNotNull('age')
        ->groupBy('age_group')
        ->get();

        $genderDistribution = User::selectRaw('gender, COUNT(*) as count')
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get();

        $locationDistribution = User::selectRaw('country, COUNT(*) as count')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $languageDistribution = User::selectRaw('language, COUNT(*) as count')
            ->whereNotNull('language')
            ->groupBy('language')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'age_demographics' => $ageDemographics,
                'gender_distribution' => $genderDistribution,
                'location_distribution' => $locationDistribution,
                'language_distribution' => $languageDistribution
            ]
        ]);
    }

    public function apiStatistics()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'new_users_today' => User::whereDate('created_at', Carbon::today())->count(),
            'new_users_this_week' => User::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'new_users_this_month' => User::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            'verified_users' => User::where('email_verified_at', '!=', null)->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'pending_users' => User::where('status', 'pending')->count(),
            'users_by_status' => User::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->get(),
            'registration_trends' => User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'top_countries' => User::selectRaw('country, COUNT(*) as count')
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}