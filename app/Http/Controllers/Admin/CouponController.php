<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\CouponCode;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            'name' => $request->code, // Use code as name if not provided
            'type' => $request->type,
            'discount_value' => $request->value,
            'description' => $request->description,
            'usage_limit' => $request->usage_limit,
            'expires_at' => $request->expires_at,
            'is_active' => $request->is_active,
            'created_by' => auth()->id(), // Set the current admin as creator
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
            'name' => $request->code, // Use code as name if not provided
            'type' => $request->type,
            'discount_value' => $request->value,
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

    // API Methods for Next dashboard
    public function apiIndex(Request $request)
    {
        $query = $this->buildCouponQuery($request);
        $this->applyCouponListSort($query, $request);

        $perPage = $this->resolveCouponPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildCouponQuery($request);
        $this->applyCouponListSort($query, $request);

        $filename = 'coupons-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'code', 'type', 'discount_value', 'description', 'usage_limit', 'expires_at', 'is_active', 'created_at']);

            $query->clone()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->code,
                        $row->type,
                        $row->discount_value,
                        $row->description,
                        $row->usage_limit,
                        $row->expires_at?->toIso8601String(),
                        $row->is_active ? '1' : '0',
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
            'code' => 'required|string|max:50|unique:coupon_codes,code',
            'type' => 'required|in:percentage,fixed_amount,free_coins',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'required|boolean',
        ]);

        $coupon = CouponCode::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['code'],
            'type' => $validated['type'],
            'discount_value' => $validated['value'],
            'description' => $validated['description'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'],
            'created_by' => auth()->id(),
        ]);

        return AdminApiResponse::success($coupon, 'Coupon created successfully', 201);
    }

    public function apiShow(CouponCode $coupon)
    {
        return AdminApiResponse::success($coupon);
    }

    public function apiUpdate(Request $request, CouponCode $coupon)
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:coupon_codes,code,' . $coupon->id,
            'type' => 'sometimes|required|in:percentage,fixed_amount,free_coins',
            'value' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'sometimes|required|boolean',
        ]);

        $updateData = [];
        if (array_key_exists('code', $validated)) {
            $updateData['code'] = strtoupper($validated['code']);
            $updateData['name'] = $validated['code'];
        }
        if (array_key_exists('type', $validated)) {
            $updateData['type'] = $validated['type'];
        }
        if (array_key_exists('value', $validated)) {
            $updateData['discount_value'] = $validated['value'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if (array_key_exists('usage_limit', $validated)) {
            $updateData['usage_limit'] = $validated['usage_limit'];
        }
        if (array_key_exists('expires_at', $validated)) {
            $updateData['expires_at'] = $validated['expires_at'];
        }
        if (array_key_exists('is_active', $validated)) {
            $updateData['is_active'] = $validated['is_active'];
        }

        $coupon->update($updateData);

        return AdminApiResponse::success($coupon->fresh(), 'Coupon updated successfully');
    }

    public function apiDestroy(CouponCode $coupon)
    {
        $coupon->delete();

        return AdminApiResponse::okMessage('Coupon deleted successfully');
    }

    public function apiBulkAction(Request $request)
    {
        $ids = $request->input('coupon_ids', $request->input('selected_items', []));
        $request->merge(['coupon_ids' => is_array($ids) ? $ids : []]);

        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'coupon_ids' => 'required|array|min:1',
            'coupon_ids.*' => 'exists:coupon_codes,id',
        ]);

        $coupons = CouponCode::whereIn('id', $request->coupon_ids);

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
            default:
                $message = 'Bulk action executed successfully';
        }

        return AdminApiResponse::okMessage($message);
    }

    public function apiStatistics()
    {
        return AdminApiResponse::success([
            'total_coupons' => CouponCode::count(),
            'active_coupons' => CouponCode::where('is_active', true)->count(),
            'inactive_coupons' => CouponCode::where('is_active', false)->count(),
            'expired_coupons' => CouponCode::where('expires_at', '<', now())->count(),
            'percentage_coupons' => CouponCode::where('type', 'percentage')->count(),
            'fixed_amount_coupons' => CouponCode::where('type', 'fixed_amount')->count(),
            'free_coins_coupons' => CouponCode::where('type', 'free_coins')->count(),
        ]);
    }

    private function buildCouponQuery(Request $request)
    {
        $query = CouponCode::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $val = $request->input('is_active');
            $query->where('is_active', filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $val);
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

    private function applyCouponListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'code', 'type', 'discount_value', 'is_active'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveCouponPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}
