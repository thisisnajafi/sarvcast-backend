<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
