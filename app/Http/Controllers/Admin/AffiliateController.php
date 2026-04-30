<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\AffiliatePartner;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {
        $query = AffiliatePartner::with(['user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by commission rate range
        if ($request->filled('commission_range')) {
            $range = $request->commission_range;
            switch ($range) {
                case 'low':
                    $query->where('commission_rate', '<', 5);
                    break;
                case 'medium':
                    $query->whereBetween('commission_rate', [5, 15]);
                    break;
                case 'high':
                    $query->where('commission_rate', '>', 15);
                    break;
            }
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

        $partners = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.affiliate.index', compact('partners'));
    }

    public function create()
    {
        $users = User::where('role', 'user')->get();
        return view('admin.affiliate.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:affiliate_partners,user_id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'referral_code' => 'required|string|max:50|unique:affiliate_partners,referral_code',
            'website_url' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $partner = AffiliatePartner::create([
            'user_id' => $request->user_id,
            'commission_rate' => $request->commission_rate,
            'referral_code' => strtoupper($request->referral_code),
            'website_url' => $request->website_url,
            'description' => $request->description,
            'status' => $request->status,
            'total_earnings' => 0,
            'paid_amount' => 0,
            'payment_status' => 'pending',
        ]);

        return redirect()->route('admin.affiliate.index')
            ->with('success', 'شریک وابسته با موفقیت ایجاد شد.');
    }

    public function show(AffiliatePartner $affiliate)
    {
        $affiliate->load(['user']);
        return view('admin.affiliate.show', compact('affiliate'));
    }

    public function edit(AffiliatePartner $affiliate)
    {
        $users = User::where('role', 'user')->get();
        return view('admin.affiliate.edit', compact('affiliate', 'users'));
    }

    public function update(Request $request, AffiliatePartner $affiliate)
    {
        $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
            'referral_code' => 'required|string|max:50|unique:affiliate_partners,referral_code,' . $affiliate->id,
            'website_url' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $affiliate->update([
            'commission_rate' => $request->commission_rate,
            'referral_code' => strtoupper($request->referral_code),
            'website_url' => $request->website_url,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.affiliate.index')
            ->with('success', 'شریک وابسته با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(AffiliatePartner $affiliate)
    {
        $affiliate->delete();

        return redirect()->route('admin.affiliate.index')
            ->with('success', 'شریک وابسته با موفقیت حذف شد.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,approve',
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:affiliate_partners,id',
        ]);

        $partners = AffiliatePartner::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                $partners->delete();
                $message = 'شرکای وابسته انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'activate':
                $partners->update(['status' => 'active']);
                $message = 'شرکای وابسته انتخاب شده با موفقیت فعال شدند.';
                break;

            case 'deactivate':
                $partners->update(['status' => 'inactive']);
                $message = 'شرکای وابسته انتخاب شده با موفقیت غیرفعال شدند.';
                break;

            case 'approve':
                $partners->update(['status' => 'active']);
                $message = 'شرکای وابسته انتخاب شده با موفقیت تایید شدند.';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    public function export(Request $request)
    {
        $query = AffiliatePartner::with(['user']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $partners = $query->orderBy('created_at', 'desc')->get();

        return redirect()->back()
            ->with('success', 'گزارش شرکای وابسته آماده دانلود است.');
    }

    public function statistics()
    {
        $stats = [
            'total_partners' => AffiliatePartner::count(),
            'active_partners' => AffiliatePartner::where('status', 'active')->count(),
            'pending_partners' => AffiliatePartner::where('status', 'pending')->count(),
            'inactive_partners' => AffiliatePartner::where('status', 'inactive')->count(),
            'total_earnings' => AffiliatePartner::sum('total_earnings'),
            'total_paid' => AffiliatePartner::sum('paid_amount'),
            'average_commission_rate' => AffiliatePartner::avg('commission_rate'),
            'top_earner' => AffiliatePartner::with('user')->orderBy('total_earnings', 'desc')->first(),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'signups' => AffiliatePartner::whereDate('created_at', $date)->count(),
                'earnings' => rand(1000, 5000),
            ];
        }

        return view('admin.affiliate.statistics', compact('stats', 'dailyStats'));
    }

    // API Methods for Next dashboard
    public function apiIndex(Request $request)
    {
        $query = $this->buildAffiliateApiQuery($request);
        $this->applyAffiliateListSort($query, $request);

        $perPage = $this->resolveAffiliatePerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildAffiliateApiQuery($request);
        $this->applyAffiliateListSort($query, $request);

        $filename = 'affiliate-partners-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'name', 'email', 'phone', 'type', 'tier', 'status', 'commission_rate', 'is_verified', 'created_at']);

            $query->clone()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->name,
                        $row->email,
                        $row->phone,
                        $row->type,
                        $row->tier,
                        $row->status,
                        $row->commission_rate,
                        $row->is_verified ? '1' : '0',
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
            'email' => 'required|email|max:255|unique:affiliate_partners,email',
            'phone' => 'nullable|string|max:50',
            'type' => 'required|in:teacher,influencer,school,corporate',
            'tier' => 'nullable|in:micro,mid,macro,enterprise',
            'status' => 'required|in:pending,active,suspended,terminated',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'follower_count' => 'nullable|integer|min:0',
            'social_media_handle' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'website' => 'nullable|url|max:255',
            'verification_documents' => 'nullable|array',
            'is_verified' => 'boolean',
        ]);

        $validated['verified_at'] = ($validated['is_verified'] ?? false) ? now() : null;
        $item = AffiliatePartner::create($validated);

        return AdminApiResponse::success($item, 'شریک وابسته با موفقیت ایجاد شد.', 201);
    }

    public function apiShow(AffiliatePartner $affiliate)
    {
        return AdminApiResponse::success($affiliate);
    }

    public function apiUpdate(Request $request, AffiliatePartner $affiliate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:affiliate_partners,email,' . $affiliate->id,
            'phone' => 'nullable|string|max:50',
            'type' => 'required|in:teacher,influencer,school,corporate',
            'tier' => 'nullable|in:micro,mid,macro,enterprise',
            'status' => 'required|in:pending,active,suspended,terminated',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'follower_count' => 'nullable|integer|min:0',
            'social_media_handle' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'website' => 'nullable|url|max:255',
            'verification_documents' => 'nullable|array',
            'is_verified' => 'boolean',
        ]);

        if (($validated['is_verified'] ?? false) && !$affiliate->is_verified) {
            $validated['verified_at'] = now();
        }
        if (!(bool)($validated['is_verified'] ?? false)) {
            $validated['verified_at'] = null;
        }

        $affiliate->update($validated);

        return AdminApiResponse::success($affiliate->fresh(), 'شریک وابسته با موفقیت به‌روزرسانی شد.');
    }

    public function apiDestroy(AffiliatePartner $affiliate)
    {
        $affiliate->delete();

        return AdminApiResponse::okMessage('شریک وابسته حذف شد.');
    }

    public function apiBulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,activate,suspend,terminate,verify',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'integer|exists:affiliate_partners,id',
            'affiliate_ids' => 'nullable|array',
            'affiliate_ids.*' => 'integer|exists:affiliate_partners,id',
        ]);

        $ids = $validated['affiliate_ids'] ?? $validated['selected_items'] ?? [];
        if (count($ids) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'هیچ موردی انتخاب نشده است.',
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $items = AffiliatePartner::whereIn('id', $ids)->get();
            foreach ($items as $item) {
                if ($validated['action'] === 'delete') {
                    $item->delete();
                } elseif ($validated['action'] === 'activate') {
                    $item->update(['status' => 'active']);
                } elseif ($validated['action'] === 'suspend') {
                    $item->update(['status' => 'suspended']);
                } elseif ($validated['action'] === 'terminate') {
                    $item->update(['status' => 'terminated']);
                } elseif ($validated['action'] === 'verify') {
                    $item->update(['is_verified' => true, 'verified_at' => now(), 'status' => 'active']);
                }
            }
            DB::commit();

            return AdminApiResponse::okMessage('عملیات گروهی با موفقیت انجام شد.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در عملیات گروهی: '.$e->getMessage(),
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiStatistics()
    {
        return AdminApiResponse::success([
            'stats' => [
                'total_partners' => AffiliatePartner::count(),
                'active_partners' => AffiliatePartner::where('status', 'active')->count(),
                'pending_partners' => AffiliatePartner::where('status', 'pending')->count(),
                'suspended_partners' => AffiliatePartner::where('status', 'suspended')->count(),
                'verified_partners' => AffiliatePartner::where('is_verified', true)->count(),
                'average_commission_rate' => round((float) AffiliatePartner::avg('commission_rate'), 2),
            ],
        ]);
    }

    private function buildAffiliateApiQuery(Request $request)
    {
        $query = AffiliatePartner::query();

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('social_media_handle', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('tier')) {
            $query->where('tier', $request->tier);
        }
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->filled('commission_range')) {
            $range = $request->commission_range;
            switch ($range) {
                case 'low':
                    $query->where('commission_rate', '<', 5);
                    break;
                case 'medium':
                    $query->whereBetween('commission_rate', [5, 15]);
                    break;
                case 'high':
                    $query->where('commission_rate', '>', 15);
                    break;
            }
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

    private function applyAffiliateListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'name', 'commission_rate', 'status', 'type', 'tier'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveAffiliatePerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}
