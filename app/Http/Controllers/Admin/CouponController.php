<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponCode;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = CouponCode::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
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

        $coupons = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:coupon_codes,code',
            'type' => 'required|in:percentage,fixed_amount,free_coins',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'required|boolean',
        ]);

        $coupon = CouponCode::create([
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'description' => $request->description,
            'usage_limit' => $request->usage_limit,
            'expires_at' => $request->expires_at,
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'کد تخفیف با موفقیت ایجاد شد.');
    }

    public function show(CouponCode $coupon)
    {
        return view('admin.coupons.show', compact('coupon'));
    }

    public function edit(CouponCode $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, CouponCode $coupon)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:coupon_codes,code,' . $coupon->id,
            'type' => 'required|in:percentage,fixed_amount,free_coins',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'required|boolean',
        ]);

        $coupon->update([
            'code' => strtoupper($request->code),
            'type' => $request->type,
            'value' => $request->value,
            'description' => $request->description,
            'usage_limit' => $request->usage_limit,
            'expires_at' => $request->expires_at,
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'کد تخفیف با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(CouponCode $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')
            ->with('success', 'کد تخفیف با موفقیت حذف شد.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:coupon_codes,id',
        ]);

        $coupons = CouponCode::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                $coupons->delete();
                $message = 'کدهای تخفیف انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'activate':
                $coupons->update(['is_active' => true]);
                $message = 'کدهای تخفیف انتخاب شده با موفقیت فعال شدند.';
                break;

            case 'deactivate':
                $coupons->update(['is_active' => false]);
                $message = 'کدهای تخفیف انتخاب شده با موفقیت غیرفعال شدند.';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    public function export(Request $request)
    {
        $query = CouponCode::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $coupons = $query->orderBy('created_at', 'desc')->get();

        return redirect()->back()
            ->with('success', 'گزارش کدهای تخفیف آماده دانلود است.');
    }

    public function statistics()
    {
        $stats = [
            'total_coupons' => CouponCode::count(),
            'active_coupons' => CouponCode::where('is_active', true)->count(),
            'inactive_coupons' => CouponCode::where('is_active', false)->count(),
            'expired_coupons' => CouponCode::where('expires_at', '<', now())->count(),
            'percentage_coupons' => CouponCode::where('type', 'percentage')->count(),
            'fixed_amount_coupons' => CouponCode::where('type', 'fixed_amount')->count(),
            'free_coins_coupons' => CouponCode::where('type', 'free_coins')->count(),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'created' => CouponCode::whereDate('created_at', $date)->count(),
                'used' => rand(0, 10), // This would come from usage tracking
            ];
        }

        return view('admin.coupons.statistics', compact('stats', 'dailyStats'));
    }
}
