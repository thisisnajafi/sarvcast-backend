<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    /**
     * Display a listing of referrals.
     */
    public function index(Request $request)
    {
        $query = Referral::with(['referrer', 'referred']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhereHas('referrer', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('referred', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by referral type
        if ($request->filled('referral_type')) {
            $query->where('referral_type', $request->referral_type);
        }

        // Filter by reward status
        if ($request->filled('reward_status')) {
            $query->where('reward_status', $request->reward_status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $referrals = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => Referral::count(),
            'pending' => Referral::where('status', 'pending')->count(),
            'completed' => Referral::where('status', 'completed')->count(),
            'expired' => Referral::where('status', 'expired')->count(),
            'total_rewards' => Referral::where('reward_status', 'paid')->sum('reward_amount'),
            'pending_rewards' => Referral::where('reward_status', 'pending')->sum('reward_amount'),
        ];

        return view('admin.referrals.index', compact('referrals', 'stats'));
    }

    /**
     * Show the form for creating a new referral.
     */
    public function create()
    {
        $users = User::where('is_active', true)->get();
        return view('admin.referrals.create', compact('users'));
    }

    /**
     * Store a newly created referral.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referrer_id' => 'required|exists:users,id',
            'referred_email' => 'required|email',
            'referral_type' => 'required|in:user_registration,subscription_purchase,content_engagement',
            'reward_amount' => 'required|numeric|min:0',
            'expiry_days' => 'required|integer|min:1|max:365',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Check if referred email already exists
            $referredUser = User::where('email', $request->referred_email)->first();
            
            if ($referredUser && $referredUser->id === $request->referrer_id) {
                return redirect()->back()
                    ->withErrors(['referred_email' => 'کاربر نمی‌تواند خودش را معرفی کند.'])
                    ->withInput();
            }

            // Generate unique referral code
            $referralCode = $this->generateReferralCode();

            $referral = Referral::create([
                'referrer_id' => $request->referrer_id,
                'referred_id' => $referredUser ? $referredUser->id : null,
                'referred_email' => $request->referred_email,
                'referral_code' => $referralCode,
                'referral_type' => $request->referral_type,
                'reward_amount' => $request->reward_amount,
                'expiry_days' => $request->expiry_days,
                'expires_at' => now()->addDays($request->expiry_days),
                'description' => $request->description,
                'status' => $referredUser ? 'completed' : 'pending',
                'reward_status' => 'pending',
            ]);

            DB::commit();

            return redirect()->route('admin.referrals.index')
                ->with('success', 'معرفی با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در ایجاد معرفی: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified referral.
     */
    public function show(Referral $referral)
    {
        $referral->load(['referrer', 'referred']);
        
        // Get related referrals
        $relatedReferrals = Referral::where('referrer_id', $referral->referrer_id)
            ->where('id', '!=', $referral->id)
            ->with(['referred'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.referrals.show', compact('referral', 'relatedReferrals'));
    }

    /**
     * Show the form for editing the specified referral.
     */
    public function edit(Referral $referral)
    {
        $users = User::where('is_active', true)->get();
        return view('admin.referrals.edit', compact('referral', 'users'));
    }

    /**
     * Update the specified referral.
     */
    public function update(Request $request, Referral $referral)
    {
        $validator = Validator::make($request->all(), [
            'referrer_id' => 'required|exists:users,id',
            'referred_email' => 'required|email',
            'referral_type' => 'required|in:user_registration,subscription_purchase,content_engagement',
            'reward_amount' => 'required|numeric|min:0',
            'expiry_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:pending,completed,expired,cancelled',
            'reward_status' => 'required|in:pending,paid,cancelled',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Check if referred email already exists
            $referredUser = User::where('email', $request->referred_email)->first();
            
            if ($referredUser && $referredUser->id === $request->referrer_id) {
                return redirect()->back()
                    ->withErrors(['referred_email' => 'کاربر نمی‌تواند خودش را معرفی کند.'])
                    ->withInput();
            }

            $referral->update([
                'referrer_id' => $request->referrer_id,
                'referred_id' => $referredUser ? $referredUser->id : null,
                'referred_email' => $request->referred_email,
                'referral_type' => $request->referral_type,
                'reward_amount' => $request->reward_amount,
                'expiry_days' => $request->expiry_days,
                'expires_at' => now()->addDays($request->expiry_days),
                'status' => $request->status,
                'reward_status' => $request->reward_status,
                'description' => $request->description,
            ]);

            DB::commit();

            return redirect()->route('admin.referrals.index')
                ->with('success', 'معرفی با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در به‌روزرسانی معرفی: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified referral.
     */
    public function destroy(Referral $referral)
    {
        try {
            $referral->delete();
            return redirect()->route('admin.referrals.index')
                ->with('success', 'معرفی با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف معرفی: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve a referral.
     */
    public function approve(Referral $referral)
    {
        try {
            DB::beginTransaction();

            $referral->update([
                'status' => 'completed',
                'reward_status' => 'paid',
                'completed_at' => now(),
            ]);

            // Add reward to referrer's account
            if ($referral->referrer) {
                $referral->referrer->increment('coins', $referral->reward_amount);
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'معرفی با موفقیت تأیید شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در تأیید معرفی: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a referral.
     */
    public function reject(Referral $referral)
    {
        try {
            $referral->update([
                'status' => 'cancelled',
                'reward_status' => 'cancelled',
            ]);

            return redirect()->back()
                ->with('success', 'معرفی با موفقیت رد شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در رد معرفی: ' . $e->getMessage()]);
        }
    }

    /**
     * Pay reward for a referral.
     */
    public function payReward(Referral $referral)
    {
        try {
            DB::beginTransaction();

            $referral->update([
                'reward_status' => 'paid',
                'paid_at' => now(),
            ]);

            // Add reward to referrer's account
            if ($referral->referrer) {
                $referral->referrer->increment('coins', $referral->reward_amount);
            }

            DB::commit();

            return redirect()->back()
                ->with('success', 'پاداش با موفقیت پرداخت شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در پرداخت پاداش: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on referrals.
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject,pay_rewards,delete',
            'referral_ids' => 'required|array|min:1',
            'referral_ids.*' => 'exists:referrals,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک معرفی را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $referrals = Referral::whereIn('id', $request->referral_ids);

            switch ($request->action) {
                case 'approve':
                    $referrals->update([
                        'status' => 'completed',
                        'reward_status' => 'paid',
                        'completed_at' => now(),
                    ]);
                    
                    // Add rewards to referrers
                    foreach ($referrals->get() as $referral) {
                        if ($referral->referrer) {
                            $referral->referrer->increment('coins', $referral->reward_amount);
                        }
                    }
                    break;

                case 'reject':
                    $referrals->update([
                        'status' => 'cancelled',
                        'reward_status' => 'cancelled',
                    ]);
                    break;

                case 'pay_rewards':
                    $referrals->update([
                        'reward_status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    
                    // Add rewards to referrers
                    foreach ($referrals->get() as $referral) {
                        if ($referral->referrer) {
                            $referral->referrer->increment('coins', $referral->reward_amount);
                        }
                    }
                    break;

                case 'delete':
                    $referrals->delete();
                    break;
            }

            DB::commit();

            $actionLabels = [
                'approve' => 'تأیید',
                'reject' => 'رد',
                'pay_rewards' => 'پرداخت پاداش',
                'delete' => 'حذف',
            ];

            return redirect()->back()
                ->with('success', 'عملیات ' . $actionLabels[$request->action] . ' با موفقیت انجام شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در انجام عملیات: ' . $e->getMessage()]);
        }
    }

    /**
     * Export referrals data.
     */
    public function export(Request $request)
    {
        $query = Referral::with(['referrer', 'referred']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhereHas('referrer', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('referral_type')) {
            $query->where('referral_type', $request->referral_type);
        }

        $referrals = $query->orderBy('created_at', 'desc')->get();

        $filename = 'referrals_' . now()->format('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($referrals) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, [
                'کد معرفی',
                'معرف',
                'ایمیل معرفی شده',
                'نوع معرفی',
                'مبلغ پاداش',
                'وضعیت',
                'وضعیت پاداش',
                'تاریخ ایجاد',
                'تاریخ انقضا',
                'تاریخ تکمیل'
            ]);

            foreach ($referrals as $referral) {
                fputcsv($file, [
                    $referral->referral_code,
                    $referral->referrer ? $referral->referrer->first_name . ' ' . $referral->referrer->last_name : '-',
                    $referral->referred_email,
                    $this->getReferralTypeLabel($referral->referral_type),
                    $referral->reward_amount,
                    $this->getStatusLabel($referral->status),
                    $this->getRewardStatusLabel($referral->reward_status),
                    $referral->created_at->format('Y/m/d H:i'),
                    $referral->expires_at ? $referral->expires_at->format('Y/m/d H:i') : '-',
                    $referral->completed_at ? $referral->completed_at->format('Y/m/d H:i') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get referral statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_referrals' => Referral::count(),
            'pending_referrals' => Referral::where('status', 'pending')->count(),
            'completed_referrals' => Referral::where('status', 'completed')->count(),
            'expired_referrals' => Referral::where('status', 'expired')->count(),
            'total_rewards_paid' => Referral::where('reward_status', 'paid')->sum('reward_amount'),
            'pending_rewards' => Referral::where('reward_status', 'pending')->sum('reward_amount'),
            'top_referrers' => Referral::select('referrer_id')
                ->selectRaw('COUNT(*) as referral_count')
                ->selectRaw('SUM(reward_amount) as total_rewards')
                ->with('referrer')
                ->groupBy('referrer_id')
                ->orderBy('referral_count', 'desc')
                ->limit(10)
                ->get(),
            'referrals_by_type' => Referral::selectRaw('referral_type, COUNT(*) as count')
                ->groupBy('referral_type')
                ->get(),
            'referrals_by_month' => Referral::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
        ];

        return view('admin.referrals.statistics', compact('stats'));
    }

    /**
     * Generate unique referral code.
     */
    private function generateReferralCode()
    {
        do {
            $code = 'REF' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (Referral::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get referral type label.
     */
    private function getReferralTypeLabel($type)
    {
        $labels = [
            'user_registration' => 'ثبت‌نام کاربر',
            'subscription_purchase' => 'خرید اشتراک',
            'content_engagement' => 'تعامل با محتوا',
        ];

        return $labels[$type] ?? $type;
    }

    /**
     * Get status label.
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'در انتظار',
            'completed' => 'تکمیل شده',
            'expired' => 'منقضی شده',
            'cancelled' => 'لغو شده',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Get reward status label.
     */
    private function getRewardStatusLabel($status)
    {
        $labels = [
            'pending' => 'در انتظار',
            'paid' => 'پرداخت شده',
            'cancelled' => 'لغو شده',
        ];

        return $labels[$status] ?? $status;
    }
}
