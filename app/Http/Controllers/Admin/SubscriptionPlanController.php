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
            $query->where('is_active', $request->status === 'active');
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
            'slug' => 'nullable|string|max:255|unique:subscription_plans,slug',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0', // Website price
            'currency' => 'nullable|string|size:3',
            'duration_days' => 'required|integer|min:1',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            // Flavor-specific fields
            'myket_price' => 'nullable|numeric|min:0',
            'myket_product_id' => 'nullable|string|max:255',
            'cafebazaar_price' => 'nullable|numeric|min:0',
            'cafebazaar_product_id' => 'nullable|string|max:255',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => $request->name,
            'slug' => $request->slug ?? \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
            'price' => $request->price,
            'currency' => $request->currency ?? 'IRT',
            'duration_days' => $request->duration_days,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'features' => $request->features,
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
            'sort_order' => $request->sort_order ?? 0,
            // Flavor-specific fields
            'myket_price' => $request->myket_price,
            'myket_product_id' => $request->myket_product_id,
            'cafebazaar_price' => $request->cafebazaar_price,
            'cafebazaar_product_id' => $request->cafebazaar_product_id,
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
            'slug' => 'nullable|string|max:255|unique:subscription_plans,slug,' . $subscriptionPlan->id,
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0', // Website price
            'currency' => 'nullable|string|size:3',
            'duration_days' => 'required|integer|min:1',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            // Flavor-specific fields
            'myket_price' => 'nullable|numeric|min:0',
            'myket_product_id' => 'nullable|string|max:255',
            'cafebazaar_price' => 'nullable|numeric|min:0',
            'cafebazaar_product_id' => 'nullable|string|max:255',
        ]);

        $subscriptionPlan->update([
            'name' => $request->name,
            'slug' => $request->slug ?? $subscriptionPlan->slug,
            'description' => $request->description,
            'price' => $request->price,
            'currency' => $request->currency ?? $subscriptionPlan->currency,
            'duration_days' => $request->duration_days,
            'discount_percentage' => $request->discount_percentage ?? $subscriptionPlan->discount_percentage,
            'features' => $request->features,
            'is_active' => $request->boolean('is_active', $subscriptionPlan->is_active),
            'is_featured' => $request->boolean('is_featured', $subscriptionPlan->is_featured),
            'sort_order' => $request->sort_order ?? $subscriptionPlan->sort_order,
            // Flavor-specific fields
            'myket_price' => $request->myket_price,
            'myket_product_id' => $request->myket_product_id,
            'cafebazaar_price' => $request->cafebazaar_price,
            'cafebazaar_product_id' => $request->cafebazaar_product_id,
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
                $plans->update(['is_active' => true]);
                $message = 'پلن‌های اشتراک انتخاب شده با موفقیت فعال شدند.';
                break;

            case 'deactivate':
                $plans->update(['is_active' => false]);
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
            $query->where('is_active', $request->status === 'active');
        }

        $plans = $query->orderBy('sort_order', 'asc')->get();

        return redirect()->back()
            ->with('success', 'گزارش پلن‌های اشتراک آماده دانلود است.');
    }

    public function statistics()
    {
        $stats = [
            'total_plans' => SubscriptionPlan::count(),
            'active_plans' => SubscriptionPlan::where('is_active', true)->count(),
            'inactive_plans' => SubscriptionPlan::where('is_active', false)->count(),
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
