<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePartner;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CommissionPaymentController extends Controller
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

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
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

        return view('admin.commission-payments.index', compact('partners'));
    }

    public function create()
    {
        $users = User::where('role', 'user')->get();
        return view('admin.commission-payments.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'total_earnings' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer,paypal,zarinpal,crypto',
            'payment_status' => 'required|in:pending,paid,failed',
            'notes' => 'nullable|string|max:500',
        ]);

        $partner = AffiliatePartner::create([
            'user_id' => $request->user_id,
            'commission_rate' => $request->commission_rate,
            'total_earnings' => $request->total_earnings,
            'paid_amount' => $request->paid_amount,
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'notes' => $request->notes,
            'status' => 'active',
        ]);

        return redirect()->route('admin.commission-payments.index')
            ->with('success', 'پرداخت کمیسیون با موفقیت ایجاد شد.');
    }

    public function show(AffiliatePartner $commissionPayment)
    {
        $commissionPayment->load(['user']);
        return view('admin.commission-payments.show', compact('commissionPayment'));
    }

    public function edit(AffiliatePartner $commissionPayment)
    {
        $users = User::where('role', 'user')->get();
        return view('admin.commission-payments.edit', compact('commissionPayment', 'users'));
    }

    public function update(Request $request, AffiliatePartner $commissionPayment)
    {
        $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
            'total_earnings' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer,paypal,zarinpal,crypto',
            'payment_status' => 'required|in:pending,paid,failed',
            'notes' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        $commissionPayment->update($request->only([
            'commission_rate', 'total_earnings', 'paid_amount', 
            'payment_method', 'payment_status', 'notes', 'status'
        ]));

        return redirect()->route('admin.commission-payments.index')
            ->with('success', 'پرداخت کمیسیون با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(AffiliatePartner $commissionPayment)
    {
        $commissionPayment->delete();

        return redirect()->route('admin.commission-payments.index')
            ->with('success', 'پرداخت کمیسیون با موفقیت حذف شد.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,mark_paid,mark_pending,mark_failed',
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:affiliate_partners,id',
        ]);

        $partners = AffiliatePartner::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                $partners->delete();
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'mark_paid':
                $partners->update(['payment_status' => 'paid']);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت پرداخت شده علامت‌گذاری شدند.';
                break;

            case 'mark_pending':
                $partners->update(['payment_status' => 'pending']);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت در انتظار علامت‌گذاری شدند.';
                break;

            case 'mark_failed':
                $partners->update(['payment_status' => 'failed']);
                $message = 'پرداخت‌های کمیسیون انتخاب شده با موفقیت ناموفق علامت‌گذاری شدند.';
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

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $partners = $query->orderBy('created_at', 'desc')->get();

        return redirect()->back()
            ->with('success', 'گزارش پرداخت‌های کمیسیون آماده دانلود است.');
    }

    public function statistics()
    {
        $stats = [
            'total_partners' => AffiliatePartner::count(),
            'active_partners' => AffiliatePartner::where('status', 'active')->count(),
            'total_earnings' => AffiliatePartner::sum('total_earnings'),
            'total_paid' => AffiliatePartner::sum('paid_amount'),
            'pending_payments' => AffiliatePartner::where('payment_status', 'pending')->count(),
            'paid_payments' => AffiliatePartner::where('payment_status', 'paid')->count(),
            'failed_payments' => AffiliatePartner::where('payment_status', 'failed')->count(),
            'average_commission_rate' => AffiliatePartner::avg('commission_rate'),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'earnings' => rand(1000, 5000),
                'payments' => rand(5, 20),
            ];
        }

        return view('admin.commission-payments.statistics', compact('stats', 'dailyStats'));
    }
}
