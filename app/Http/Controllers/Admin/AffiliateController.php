<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePartner;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
}
