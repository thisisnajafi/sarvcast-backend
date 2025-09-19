<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CoinController extends Controller
{
    public function index(Request $request)
    {
        $query = CoinTransaction::with(['user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.coins.index', compact('transactions'));
    }

    public function create()
    {
        $users = User::where('role', 'user')->get();
        return view('admin.coins.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer|min:1',
            'type' => 'required|in:earned,purchased,gift,refund,admin_adjustment',
            'description' => 'nullable|string|max:500',
        ]);

        $transaction = CoinTransaction::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'type' => $request->type,
            'description' => $request->description,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        // Update user's coin balance
        $user = User::find($request->user_id);
        $user->increment('coins', $request->amount);

        return redirect()->route('admin.coins.index')
            ->with('success', 'سکه با موفقیت اضافه شد.');
    }

    public function show(CoinTransaction $coin)
    {
        $coin->load(['user']);
        return view('admin.coins.show', compact('coin'));
    }

    public function edit(CoinTransaction $coin)
    {
        $users = User::where('role', 'user')->get();
        return view('admin.coins.edit', compact('coin', 'users'));
    }

    public function update(Request $request, CoinTransaction $coin)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'type' => 'required|in:earned,purchased,gift,refund,admin_adjustment',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:pending,completed,failed',
        ]);

        $oldAmount = $coin->amount;
        $coin->update($request->only(['amount', 'type', 'description', 'status']));

        // Update user's coin balance if amount changed
        if ($oldAmount != $request->amount) {
            $user = $coin->user;
            $difference = $request->amount - $oldAmount;
            $user->increment('coins', $difference);
        }

        return redirect()->route('admin.coins.index')
            ->with('success', 'تراکنش سکه با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(CoinTransaction $coin)
    {
        // Revert user's coin balance
        $user = $coin->user;
        $user->decrement('coins', $coin->amount);

        $coin->delete();

        return redirect()->route('admin.coins.index')
            ->with('success', 'تراکنش سکه با موفقیت حذف شد.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,approve,reject',
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:coin_transactions,id',
        ]);

        $transactions = CoinTransaction::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                foreach ($transactions->get() as $transaction) {
                    $user = $transaction->user;
                    $user->decrement('coins', $transaction->amount);
                }
                $transactions->delete();
                $message = 'تراکنش‌های انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'approve':
                $transactions->update(['status' => 'completed']);
                $message = 'تراکنش‌های انتخاب شده با موفقیت تایید شدند.';
                break;

            case 'reject':
                $transactions->update(['status' => 'failed']);
                $message = 'تراکنش‌های انتخاب شده با موفقیت رد شدند.';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    public function export(Request $request)
    {
        $query = CoinTransaction::with(['user']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return redirect()->back()
            ->with('success', 'گزارش تراکنش‌های سکه آماده دانلود است.');
    }

    public function statistics()
    {
        $stats = [
            'total_transactions' => CoinTransaction::count(),
            'total_coins_earned' => CoinTransaction::where('type', 'earned')->sum('amount'),
            'total_coins_purchased' => CoinTransaction::where('type', 'purchased')->sum('amount'),
            'total_coins_gifted' => CoinTransaction::where('type', 'gift')->sum('amount'),
            'pending_transactions' => CoinTransaction::where('status', 'pending')->count(),
            'completed_transactions' => CoinTransaction::where('status', 'completed')->count(),
            'failed_transactions' => CoinTransaction::where('status', 'failed')->count(),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'transactions' => CoinTransaction::whereDate('created_at', $date)->count(),
                'coins' => CoinTransaction::whereDate('created_at', $date)->sum('amount'),
            ];
        }

        return view('admin.coins.statistics', compact('stats', 'dailyStats'));
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = CoinTransaction::with(['user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer|min:1',
            'type' => 'required|in:earned,purchased,gift,refund,admin_adjustment',
            'description' => 'nullable|string|max:500',
        ]);

        $transaction = CoinTransaction::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'type' => $request->type,
            'description' => $request->description,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        // Update user's coin balance
        $user = User::find($request->user_id);
        $user->increment('coins', $request->amount);

        return response()->json([
            'success' => true,
            'message' => 'سکه با موفقیت اضافه شد.',
            'data' => $transaction->load('user')
        ]);
    }

    public function apiShow(CoinTransaction $coin)
    {
        return response()->json([
            'success' => true,
            'data' => $coin->load('user')
        ]);
    }

    public function apiUpdate(Request $request, CoinTransaction $coin)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'type' => 'required|in:earned,purchased,gift,refund,admin_adjustment',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:pending,completed,failed',
        ]);

        $oldAmount = $coin->amount;
        $coin->update($request->only(['amount', 'type', 'description', 'status']));

        // Update user's coin balance if amount changed
        if ($oldAmount != $request->amount) {
            $user = $coin->user;
            $difference = $request->amount - $oldAmount;
            $user->increment('coins', $difference);
        }

        return response()->json([
            'success' => true,
            'message' => 'تراکنش سکه با موفقیت به‌روزرسانی شد.',
            'data' => $coin->load('user')
        ]);
    }

    public function apiDestroy(CoinTransaction $coin)
    {
        // Revert user's coin balance
        $user = $coin->user;
        $user->decrement('coins', $coin->amount);

        $coin->delete();

        return response()->json([
            'success' => true,
            'message' => 'تراکنش سکه با موفقیت حذف شد.'
        ]);
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,approve,reject',
            'selected_items' => 'required|array',
            'selected_items.*' => 'exists:coin_transactions,id',
        ]);

        $transactions = CoinTransaction::whereIn('id', $request->selected_items);

        switch ($request->action) {
            case 'delete':
                foreach ($transactions->get() as $transaction) {
                    $user = $transaction->user;
                    $user->decrement('coins', $transaction->amount);
                }
                $transactions->delete();
                $message = 'تراکنش‌های انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'approve':
                $transactions->update(['status' => 'completed']);
                $message = 'تراکنش‌های انتخاب شده با موفقیت تایید شدند.';
                break;

            case 'reject':
                $transactions->update(['status' => 'failed']);
                $message = 'تراکنش‌های انتخاب شده با موفقیت رد شدند.';
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    public function apiStatistics()
    {
        $stats = [
            'total_transactions' => CoinTransaction::count(),
            'total_coins_earned' => CoinTransaction::where('type', 'earned')->sum('amount'),
            'total_coins_purchased' => CoinTransaction::where('type', 'purchased')->sum('amount'),
            'total_coins_gifted' => CoinTransaction::where('type', 'gift')->sum('amount'),
            'pending_transactions' => CoinTransaction::where('status', 'pending')->count(),
            'completed_transactions' => CoinTransaction::where('status', 'completed')->count(),
            'failed_transactions' => CoinTransaction::where('status', 'failed')->count(),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'transactions' => CoinTransaction::whereDate('created_at', $date)->count(),
                'coins' => CoinTransaction::whereDate('created_at', $date)->sum('amount'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'daily_stats' => $dailyStats
            ]
        ]);
    }
}
