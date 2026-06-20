<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

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

        return AdminApiResponse::success($stats);
    }

    /**
     * Get all subscriptions for API
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = $this->buildSubscriptionApiQuery($request);
        $this->applySubscriptionApiSort($query, $request);

        $perPage = $this->resolveSubscriptionPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $subscriptions = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($subscriptions);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'type' => 'required|string|max:64',
            'status' => 'sometimes|in:active,pending,trial',
            'auto_renew' => 'sometimes|boolean',
            'start_date' => 'sometimes|date',
        ]);

        try {
            $subscription = $this->subscriptionService->createSubscription(
                (int) $validated['user_id'],
                $validated['type'],
                [
                    'status' => $validated['status'] ?? 'active',
                    'auto_renew' => $validated['auto_renew'] ?? false,
                    'start_date' => isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : now(),
                    'payment_method' => 'admin',
                ]
            );

            return AdminApiResponse::success(
                $subscription->load('user'),
                'اشتراک با موفقیت ایجاد شد.',
                201
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get a single subscription for API.
     */
    public function apiShow(Subscription $subscription): JsonResponse
    {
        return AdminApiResponse::success($subscription->load(['user', 'payments']));
    }

    public function apiUpdate(Request $request, Subscription $subscription): JsonResponse
    {
        if ($request->input('action') === 'cancel') {
            $request->validate([
                'cancellation_reason' => 'required|string|max:500',
            ]);
            $subscription->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_at' => now(),
            ]);
        } elseif ($request->input('action') === 'reactivate') {
            $subscription->update([
                'status' => 'active',
                'cancellation_reason' => null,
                'cancelled_at' => null,
            ]);
        } else {
            $validated = $request->validate([
                'status' => 'sometimes|in:active,expired,cancelled,pending,trial',
                'auto_renew' => 'sometimes|boolean',
                'end_date' => 'sometimes|date',
            ]);
            $update = $validated;
            if (isset($update['status']) && $update['status'] === 'cancelled') {
                $update['cancelled_at'] = now();
            }
            if (isset($update['status']) && $update['status'] === 'active') {
                $update['cancellation_reason'] = null;
                $update['cancelled_at'] = null;
            }
            $subscription->update($update);
        }

        return AdminApiResponse::success(
            $subscription->fresh(['user', 'payments']),
            'اشتراک به‌روزرسانی شد.'
        );
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildSubscriptionApiQuery($request);
        $this->applySubscriptionApiSort($query, $request);

        $filename = 'subscriptions-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'user_id', 'type', 'status', 'price', 'start_date', 'end_date', 'auto_renew', 'created_at']);

            $query->clone()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->user_id,
                        $row->type,
                        $row->status,
                        $row->price,
                        $row->start_date?->format('Y-m-d'),
                        $row->end_date?->format('Y-m-d'),
                        $row->auto_renew ? '1' : '0',
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Run bulk actions on subscriptions.
     */
    public function apiBulkAction(Request $request): JsonResponse
    {
        $ids = $request->input('subscription_ids', $request->input('selected_items', []));
        $request->merge(['subscription_ids' => is_array($ids) ? $ids : []]);

        $request->validate([
            'action' => 'required|in:cancel,reactivate,delete',
            'subscription_ids' => 'required|array|min:1',
            'subscription_ids.*' => 'integer|exists:subscriptions,id',
            'reason' => 'required_if:action,cancel|string|max:500',
        ]);

        $items = Subscription::whereIn('id', $request->subscription_ids)->get();
        foreach ($items as $subscription) {
            if ($request->action === 'cancel') {
                $subscription->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $request->reason,
                    'cancelled_at' => now(),
                ]);
            } elseif ($request->action === 'reactivate') {
                $subscription->update([
                    'status' => 'active',
                    'cancellation_reason' => null,
                    'cancelled_at' => null,
                ]);
            } elseif ($request->action === 'delete') {
                $subscription->delete();
            }
        }

        return AdminApiResponse::okMessage('Bulk action executed successfully.');
    }

    public function apiStatistics(): JsonResponse
    {
        return $this->statistics();
    }

    private function buildSubscriptionApiQuery(Request $request)
    {
        $query = Subscription::query()->with(['user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('phone_number', 'like', '%'.$search.'%');
            });
        }

        $dateFrom = $request->input('dateFrom', $request->input('date_from'));
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        $dateTo = $request->input('dateTo', $request->input('date_to'));
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    private function applySubscriptionApiSort($query, Request $request): void
    {
        $sortBy = (string) $request->input('sortBy', $request->input('sort', 'created_at'));
        $sortDir = strtolower((string) $request->input('sortDir', $request->input('direction', 'desc'))) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'price', 'status', 'type', 'end_date'];
        if (!in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveSubscriptionPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}