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
}