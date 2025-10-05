<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display subscription management dashboard
     */
    public function index(Request $request): View
    {
        $query = Subscription::with('user');

        // Apply filters
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        if ($request->has('user_id') && $request->user_id !== '') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone_number', 'like', '%' . $request->search . '%');
            });
        }

        $subscriptions = $query->latest()->paginate(20);
        
        // Get statistics
        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->where('end_date', '>', now())->count(),
            'expired' => Subscription::where('status', 'expired')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'total_revenue' => Subscription::where('status', 'active')->sum('price'),
            'monthly_revenue' => Subscription::where('status', 'active')
                ->where('created_at', '>=', now()->subMonth())
                ->sum('price')
        ];

        // Get plans data for the view
        $plans = $this->subscriptionService->getPlans();

        return view('admin.subscriptions.index', compact('subscriptions', 'stats', 'plans'));
    }

    /**
     * Display subscription plans management
     */
    public function plans(): View
    {
        $plans = $this->subscriptionService->getPlans();
        
        // Get plan statistics
        $planStats = [];
        foreach ($plans as $type => $plan) {
            $planStats[$type] = [
                'name' => $plan['name'],
                'price' => $plan['price'],
                'duration_days' => $plan['duration_days'],
                'discount' => $plan['discount'] ?? 0,
                'total_subscriptions' => Subscription::where('type', $type)->count(),
                'active_subscriptions' => Subscription::where('type', $type)
                    ->where('status', 'active')
                    ->where('end_date', '>', now())
                    ->count(),
                'total_revenue' => Subscription::where('type', $type)
                    ->where('status', 'active')
                    ->sum('price')
            ];
        }

        return view('admin.subscriptions.plans', compact('plans', 'planStats'));
    }

    /**
     * Display subscription analytics
     */
    public function analytics(): View
    {
        // Revenue analytics
        $revenueData = [
            'daily' => Subscription::where('created_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(created_at) as date, SUM(price) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'monthly' => Subscription::where('created_at', '>=', now()->subMonths(12))
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(price) as revenue')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
        ];

        // Subscription status distribution
        $statusDistribution = Subscription::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Plan popularity
        $planPopularity = Subscription::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();

        // Recent subscriptions
        $recentSubscriptions = Subscription::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.subscriptions.analytics', compact(
            'revenueData', 
            'statusDistribution', 
            'planPopularity', 
            'recentSubscriptions'
        ));
    }

    /**
     * Show specific subscription details
     */
    public function show(Subscription $subscription): View
    {
        $subscription->load('user', 'payments');
        
        // Get plans data for the view
        $plans = $this->subscriptionService->getPlans();
        
        return view('admin.subscriptions.show', compact('subscription', 'plans'));
    }

    /**
     * Cancel a subscription
     */
    public function cancel(Subscription $subscription, Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $subscription->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->reason,
                'cancelled_at' => now()
            ]);

            return redirect()->back()->with('success', 'اشتراک با موفقیت لغو شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در لغو اشتراک: ' . $e->getMessage());
        }
    }

    /**
     * Reactivate a subscription
     */
    public function reactivate(Subscription $subscription): RedirectResponse
    {
        try {
            $subscription->update([
                'status' => 'active',
                'cancellation_reason' => null,
                'cancelled_at' => null
            ]);

            return redirect()->back()->with('success', 'اشتراک با موفقیت فعال شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در فعال‌سازی اشتراک: ' . $e->getMessage());
        }
    }

    /**
     * Export subscriptions data
     */
    public function export(Request $request)
    {
        $query = Subscription::with('user');

        // Apply same filters as index
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        $subscriptions = $query->latest()->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'User', 'Email', 'Phone', 'Type', 'Status', 'Price', 
            'Start Date', 'End Date', 'Auto Renew', 'Created At'
        ];

        foreach ($subscriptions as $subscription) {
            $csvData[] = [
                $subscription->id,
                $subscription->user->first_name . ' ' . $subscription->user->last_name,
                $subscription->user->email,
                $subscription->user->phone_number,
                $subscription->type,
                $subscription->status,
                $subscription->price,
                $subscription->start_date->format('Y-m-d'),
                $subscription->end_date->format('Y-m-d'),
                $subscription->auto_renew ? 'Yes' : 'No',
                $subscription->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'subscriptions_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get subscription statistics for API
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->where('end_date', '>', now())->count(),
            'expired_subscriptions' => Subscription::where('status', 'expired')->count(),
            'cancelled_subscriptions' => Subscription::where('status', 'cancelled')->count(),
            'total_revenue' => Subscription::where('status', 'active')->sum('price'),
            'monthly_revenue' => Subscription::where('status', 'active')
                ->where('created_at', '>=', now()->subMonth())
                ->sum('price'),
            'average_subscription_value' => Subscription::where('status', 'active')->avg('price'),
            'renewal_rate' => Subscription::where('auto_renew', true)->count() / max(Subscription::count(), 1) * 100
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get all subscriptions for API
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = Subscription::with(['user']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = $request->input('per_page', 20);
        $subscriptions = $query->latest()->paginate(min($perPage, 100));

        return response()->json([
            'success' => true,
            'message' => 'اشتراک‌ها دریافت شد',
            'data' => [
                'subscriptions' => $subscriptions->items(),
                'pagination' => [
                    'current_page' => $subscriptions->currentPage(),
                    'per_page' => $subscriptions->perPage(),
                    'total' => $subscriptions->total(),
                    'last_page' => $subscriptions->lastPage(),
                    'has_more' => $subscriptions->hasMorePages(),
                ]
            ]
        ]);
    }
}