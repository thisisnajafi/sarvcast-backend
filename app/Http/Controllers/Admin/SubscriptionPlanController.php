<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

    // API Methods for Next dashboard
    public function apiIndex(Request $request)
    {
        $query = $this->buildSubscriptionPlanQuery($request);
        $this->applySubscriptionPlanListSort($query, $request);

        $perPage = $this->resolveSubscriptionPlanPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildSubscriptionPlanQuery($request);
        $this->applySubscriptionPlanListSort($query, $request);

        $filename = 'subscription-plans-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'name', 'slug', 'price', 'currency', 'duration_days', 'is_active', 'sort_order', 'created_at']);

            $query->clone()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->slug,
                        $row->price,
                        $row->currency,
                        $row->duration_days,
                        $row->is_active ? '1' : '0',
                        $row->sort_order,
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subscription_plans,slug',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'duration_days' => 'required|integer|min:1',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'myket_price' => 'nullable|numeric|min:0',
            'myket_product_id' => 'nullable|string|max:255',
            'cafebazaar_price' => 'nullable|numeric|min:0',
            'cafebazaar_product_id' => 'nullable|string|max:255',
        ]);

        if (empty($validated['slug']) && !empty($validated['name'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }

        $plan = SubscriptionPlan::create($validated);

        return AdminApiResponse::success($plan, 'Subscription plan created successfully', 201);
    }

    public function apiShow(SubscriptionPlan $subscriptionPlan)
    {
        return AdminApiResponse::success($subscriptionPlan);
    }

    public function apiUpdate(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:subscription_plans,slug,' . $subscriptionPlan->id,
            'description' => 'nullable|string|max:1000',
            'price' => 'sometimes|required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'duration_days' => 'sometimes|required|integer|min:1',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'myket_price' => 'nullable|numeric|min:0',
            'myket_product_id' => 'nullable|string|max:255',
            'cafebazaar_price' => 'nullable|numeric|min:0',
            'cafebazaar_product_id' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('slug', $validated) && empty($validated['slug']) && !empty($validated['name'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }

        $subscriptionPlan->update($validated);

        return AdminApiResponse::success($subscriptionPlan->fresh(), 'Subscription plan updated successfully');
    }

    public function apiDestroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();

        return AdminApiResponse::okMessage('Subscription plan deleted successfully');
    }

    public function apiBulkAction(Request $request)
    {
        $ids = $request->input('plan_ids', $request->input('selected_items', []));
        $request->merge(['plan_ids' => is_array($ids) ? $ids : []]);

        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'plan_ids' => 'required|array|min:1',
            'plan_ids.*' => 'exists:subscription_plans,id',
        ]);

        $plans = SubscriptionPlan::whereIn('id', $request->plan_ids);

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
            default:
                $message = 'Bulk action executed successfully';
        }

        return AdminApiResponse::okMessage($message);
    }

    public function apiStatistics()
    {
        return AdminApiResponse::success([
            'total_plans' => SubscriptionPlan::count(),
            'active_plans' => SubscriptionPlan::where('is_active', true)->count(),
            'inactive_plans' => SubscriptionPlan::where('is_active', false)->count(),
            'average_price' => SubscriptionPlan::avg('price'),
        ]);
    }

    private function buildSubscriptionPlanQuery(Request $request)
    {
        $query = SubscriptionPlan::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }

        if ($request->filled('date_range')) {
            switch ($request->date_range) {
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

        return $query;
    }

    private function applySubscriptionPlanListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'sort_order');
        $sortDir = strtolower((string) $request->input('sortDir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = ['sort_order', 'created_at', 'id', 'name', 'price', 'duration_days', 'is_active'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'sort_order';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveSubscriptionPlanPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}
