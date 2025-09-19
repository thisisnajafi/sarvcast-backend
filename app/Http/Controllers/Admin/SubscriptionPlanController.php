<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionPlan::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Date range filter
        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', Carbon::now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', Carbon::now()->subMonth());
                    break;
                case 'year':
                    $query->where('created_at', '>=', Carbon::now()->subYear());
                    break;
            }
        }

        $plans = $query->orderBy('sort_order', 'asc')->paginate(20);

        return view('admin.subscription-plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.subscription-plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:monthly,yearly,lifetime',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'max_stories' => 'nullable|integer|min:0',
            'max_episodes' => 'nullable|integer|min:0',
            'max_storage_gb' => 'nullable|numeric|min:0',
            'priority_support' => 'boolean',
            'custom_domain' => 'boolean',
            'analytics_access' => 'boolean',
            'api_access' => 'boolean',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'features' => $request->features ? json_encode($request->features) : null,
            'max_stories' => $request->max_stories,
            'max_episodes' => $request->max_episodes,
            'max_storage_gb' => $request->max_storage_gb,
            'priority_support' => $request->boolean('priority_support'),
            'custom_domain' => $request->boolean('custom_domain'),
            'analytics_access' => $request->boolean('analytics_access'),
            'api_access' => $request->boolean('api_access'),
            'status' => $request->status,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'پلن اشتراک با موفقیت ایجاد شد.');
    }

    public function show(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription-plans.show', compact('subscriptionPlan'));
    }

    public function edit(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription-plans.edit', compact('subscriptionPlan'));
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:monthly,yearly,lifetime',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'max_stories' => 'nullable|integer|min:0',
            'max_episodes' => 'nullable|integer|min:0',
            'max_storage_gb' => 'nullable|numeric|min:0',
            'priority_support' => 'boolean',
            'custom_domain' => 'boolean',
            'analytics_access' => 'boolean',
            'api_access' => 'boolean',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $subscriptionPlan->update([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'features' => $request->features ? json_encode($request->features) : null,
            'max_stories' => $request->max_stories,
            'max_episodes' => $request->max_episodes,
            'max_storage_gb' => $request->max_storage_gb,
            'priority_support' => $request->boolean('priority_support'),
            'custom_domain' => $request->boolean('custom_domain'),
            'analytics_access' => $request->boolean('analytics_access'),
            'api_access' => $request->boolean('api_access'),
            'status' => $request->status,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'پلن اشتراک با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'پلن اشتراک با موفقیت حذف شد.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:subscription_plans,id',
        ]);

        $plans = SubscriptionPlan::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                $plans->delete();
                $message = 'پلن‌های اشتراک انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'activate':
                $plans->update(['status' => 'active']);
                $message = 'پلن‌های اشتراک انتخاب شده با موفقیت فعال شدند.';
                break;

            case 'deactivate':
                $plans->update(['status' => 'inactive']);
                $message = 'پلن‌های اشتراک انتخاب شده با موفقیت غیرفعال شدند.';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    public function export(Request $request)
    {
        $query = SubscriptionPlan::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $plans = $query->orderBy('sort_order', 'asc')->get();

        return redirect()->back()
            ->with('success', 'گزارش پلن‌های اشتراک آماده دانلود است.');
    }

    public function statistics()
    {
        $stats = [
            'total_plans' => SubscriptionPlan::count(),
            'active_plans' => SubscriptionPlan::where('status', 'active')->count(),
            'inactive_plans' => SubscriptionPlan::where('status', 'inactive')->count(),
            'monthly_plans' => SubscriptionPlan::where('type', 'monthly')->count(),
            'yearly_plans' => SubscriptionPlan::where('type', 'yearly')->count(),
            'lifetime_plans' => SubscriptionPlan::where('type', 'lifetime')->count(),
            'average_price' => SubscriptionPlan::avg('price'),
            'total_revenue' => rand(1000000, 5000000), // This would come from actual subscription data
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'subscriptions' => rand(5, 50),
                'revenue' => rand(10000, 100000),
            ];
        }

        return view('admin.subscription-plans.statistics', compact('stats', 'dailyStats'));
    }
}
