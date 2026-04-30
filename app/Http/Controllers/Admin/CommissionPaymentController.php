<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\CommissionPayment;
use App\Models\AffiliatePartner;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CommissionPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionPayment::with(['affiliatePartner', 'processor']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('affiliatePartner', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
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

        $commissionPayments = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get real statistics
        $stats = [
            'total_payments' => CommissionPayment::count(),
            'total_amount' => CommissionPayment::sum('amount'),
            'paid_amount' => CommissionPayment::where('status', 'paid')->sum('amount'),
            'pending_amount' => CommissionPayment::where('status', 'pending')->sum('amount'),
            'pending_count' => CommissionPayment::where('status', 'pending')->count(),
            'paid_count' => CommissionPayment::where('status', 'paid')->count(),
            'failed_count' => CommissionPayment::where('status', 'failed')->count(),
        ];

        return view('admin.commission-payments.index', compact('commissionPayments', 'stats'));
    }

    public function create()
    {
        $affiliatePartners = AffiliatePartner::where('status', 'active')->get();
        return view('admin.commission-payments.create', compact('affiliatePartners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'affiliate_partner_id' => 'required|exists:affiliate_partners,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:IRT,USD,EUR',
            'payment_type' => 'required|in:commission,bonus,refund',
            'payment_method' => 'required|in:bank_transfer,paypal,zarinpal,crypto',
            'status' => 'required|in:pending,processing,paid,failed,cancelled',
            'notes' => 'nullable|string|max:500',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $commissionPayment = CommissionPayment::create([
            'affiliate_partner_id' => $request->affiliate_partner_id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'payment_type' => $request->payment_type,
            'payment_method' => $request->payment_method,
            'status' => $request->status,
            'notes' => $request->notes,
            'payment_reference' => $request->payment_reference,
            'processed_by' => auth()->id(),
        ]);

        return redirect()->route('admin.commission-payments.index')
            ->with('success', 'پرداخت کمیسیون با موفقیت ایجاد شد.');
    }

    public function show(CommissionPayment $commissionPayment)
    {
        $commissionPayment->load(['affiliatePartner', 'processor']);
        return view('admin.commission-payments.show', compact('commissionPayment'));
    }

    public function edit(CommissionPayment $commissionPayment)
    {
        $affiliatePartners = AffiliatePartner::where('status', 'active')->get();
        return view('admin.commission-payments.edit', compact('commissionPayment', 'affiliatePartners'));
    }

    public function update(Request $request, CommissionPayment $commissionPayment)
    {
        $request->validate([
            'affiliate_partner_id' => 'required|exists:affiliate_partners,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:IRT,USD,EUR',
            'payment_type' => 'required|in:commission,bonus,refund',
            'payment_method' => 'required|in:bank_transfer,paypal,zarinpal,crypto',
            'status' => 'required|in:pending,processing,paid,failed,cancelled',
            'notes' => 'nullable|string|max:500',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $commissionPayment->update($request->only([
            'affiliate_partner_id', 'amount', 'currency', 'payment_type',
            'payment_method', 'status', 'notes', 'payment_reference'
        ]));

        return redirect()->route('admin.commission-payments.index')
            ->with('success', 'پرداخت کمیسیون با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(CommissionPayment $commissionPayment)
    {
        $commissionPayment->delete();

        return redirect()->route('admin.commission-payments.index')
            ->with('success', 'پرداخت کمیسیون با موفقیت حذف شد.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,mark_paid,mark_pending,mark_failed,mark_processing',
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:commission_payments,id',
        ]);

        $payments = CommissionPayment::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                $payments->delete();
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'mark_paid':
                $payments->update(['status' => 'paid', 'paid_at' => now()]);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت پرداخت شده علامت‌گذاری شدند.';
                break;

            case 'mark_pending':
                $payments->update(['status' => 'pending']);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت در انتظار علامت‌گذاری شدند.';
                break;

            case 'mark_failed':
                $payments->update(['status' => 'failed']);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت ناموفق علامت‌گذاری شدند.';
                break;

            case 'mark_processing':
                $payments->update(['status' => 'processing', 'processed_at' => now()]);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت در حال پردازش علامت‌گذاری شدند.';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    public function export(Request $request)
    {
        $query = CommissionPayment::with(['affiliatePartner', 'processor']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('affiliatePartner', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        return redirect()->back()
            ->with('success', 'گزارش پرداخت‌های کمیسیون آماده دانلود است.');
    }

    public function statistics()
    {
        $stats = [
            'total_payments' => CommissionPayment::count(),
            'total_amount' => CommissionPayment::sum('amount'),
            'paid_amount' => CommissionPayment::where('status', 'paid')->sum('amount'),
            'pending_amount' => CommissionPayment::where('status', 'pending')->sum('amount'),
            'pending_count' => CommissionPayment::where('status', 'pending')->count(),
            'paid_count' => CommissionPayment::where('status', 'paid')->count(),
            'failed_count' => CommissionPayment::where('status', 'failed')->count(),
            'processing_count' => CommissionPayment::where('status', 'processing')->count(),
            'average_payment' => CommissionPayment::avg('amount'),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyAmount = CommissionPayment::whereDate('created_at', $date)->sum('amount');
            $dailyCount = CommissionPayment::whereDate('created_at', $date)->count();
            
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'amount' => $dailyAmount,
                'count' => $dailyCount,
            ];
        }

        return view('admin.commission-payments.statistics', compact('stats', 'dailyStats'));
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = $this->buildCommissionQuery($request);
        $this->applyCommissionListSort($query, $request);

        $perPage = $this->resolveCommissionPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildCommissionQuery($request);
        $this->applyCommissionListSort($query, $request);

        $filename = 'commission-payments-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'affiliate_partner_id', 'amount', 'currency', 'payment_type', 'payment_method', 'status', 'payment_reference', 'created_at']);

            $query->clone()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->affiliate_partner_id,
                        $row->amount,
                        $row->currency,
                        $row->payment_type,
                        $row->payment_method,
                        $row->status,
                        $row->payment_reference,
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
        $request->validate([
            'affiliate_partner_id' => 'required|exists:affiliate_partners,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:IRT,USD,EUR',
            'payment_type' => 'required|in:commission,bonus,refund',
            'payment_method' => 'required|in:bank_transfer,paypal,zarinpal,crypto',
            'status' => 'required|in:pending,processing,paid,failed,cancelled',
            'notes' => 'nullable|string|max:500',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $commissionPayment = CommissionPayment::create([
            'affiliate_partner_id' => $request->affiliate_partner_id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'payment_type' => $request->payment_type,
            'payment_method' => $request->payment_method,
            'status' => $request->status,
            'notes' => $request->notes,
            'payment_reference' => $request->payment_reference,
            'processed_by' => auth()->id(),
        ]);

        return AdminApiResponse::success(
            $commissionPayment->load(['affiliatePartner', 'processor']),
            'پرداخت کمیسیون با موفقیت ایجاد شد.'
        );
    }

    public function apiShow(CommissionPayment $commissionPayment)
    {
        return AdminApiResponse::success($commissionPayment->load(['affiliatePartner', 'processor']));
    }

    public function apiUpdate(Request $request, CommissionPayment $commissionPayment)
    {
        $request->validate([
            'affiliate_partner_id' => 'required|exists:affiliate_partners,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:IRT,USD,EUR',
            'payment_type' => 'required|in:commission,bonus,refund',
            'payment_method' => 'required|in:bank_transfer,paypal,zarinpal,crypto',
            'status' => 'required|in:pending,processing,paid,failed,cancelled',
            'notes' => 'nullable|string|max:500',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $commissionPayment->update($request->only([
            'affiliate_partner_id',
            'amount',
            'currency',
            'payment_type',
            'payment_method',
            'status',
            'notes',
            'payment_reference',
        ]));

        return AdminApiResponse::success(
            $commissionPayment->load(['affiliatePartner', 'processor']),
            'پرداخت کمیسیون با موفقیت به‌روزرسانی شد.'
        );
    }

    public function apiDestroy(CommissionPayment $commissionPayment)
    {
        $commissionPayment->delete();

        return AdminApiResponse::okMessage('پرداخت کمیسیون با موفقیت حذف شد.');
    }

    public function apiBulkAction(Request $request)
    {
        $ids = $request->input('payment_ids', $request->input('selected_items', []));
        $request->merge(['selected_items' => is_array($ids) ? $ids : []]);

        $request->validate([
            'action' => 'required|in:delete,mark_paid,mark_pending,mark_failed,mark_processing',
            'selected_items' => 'required|array|min:1',
            'selected_items.*' => 'exists:commission_payments,id',
        ]);

        $payments = CommissionPayment::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                $payments->delete();
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت حذف شدند.';
                break;
            case 'mark_paid':
                $payments->update(['status' => 'paid', 'paid_at' => now()]);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت پرداخت شده علامت‌گذاری شدند.';
                break;
            case 'mark_pending':
                $payments->update(['status' => 'pending']);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت در انتظار علامت‌گذاری شدند.';
                break;
            case 'mark_failed':
                $payments->update(['status' => 'failed']);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت ناموفق علامت‌گذاری شدند.';
                break;
            case 'mark_processing':
                $payments->update(['status' => 'processing', 'processed_at' => now()]);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت در حال پردازش علامت‌گذاری شدند.';
                break;
        }

        return AdminApiResponse::okMessage($message);
    }

    public function apiStatistics()
    {
        $stats = [
            'total_payments' => CommissionPayment::count(),
            'total_amount' => CommissionPayment::sum('amount'),
            'paid_amount' => CommissionPayment::where('status', 'paid')->sum('amount'),
            'pending_amount' => CommissionPayment::where('status', 'pending')->sum('amount'),
            'pending_count' => CommissionPayment::where('status', 'pending')->count(),
            'paid_count' => CommissionPayment::where('status', 'paid')->count(),
            'failed_count' => CommissionPayment::where('status', 'failed')->count(),
            'processing_count' => CommissionPayment::where('status', 'processing')->count(),
            'average_payment' => CommissionPayment::avg('amount'),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'amount' => CommissionPayment::whereDate('created_at', $date)->sum('amount'),
                'count' => CommissionPayment::whereDate('created_at', $date)->count(),
            ];
        }

        return AdminApiResponse::success([
            'stats' => $stats,
            'daily_stats' => $dailyStats,
        ]);
    }

    private function buildCommissionQuery(Request $request)
    {
        $query = CommissionPayment::query()->with(['affiliatePartner', 'processor']);

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->whereHas('affiliatePartner', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
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

    private function applyCommissionListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'amount', 'status', 'payment_method', 'payment_type'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveCommissionPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}
