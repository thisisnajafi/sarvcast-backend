<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\CoinTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $query = $this->buildCoinTransactionQuery($request);
        $this->applyCoinListSort($query, $request);

        $perPage = $this->resolveCoinsPerPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    public function apiExport(Request $request)
    {
        $query = $this->buildCoinTransactionQuery($request);
        $this->applyCoinListSort($query, $request);

        $filename = 'coin-transactions-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['id', 'user_id', 'amount', 'type', 'status', 'description', 'created_at']);

            $query->clone()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->user_id,
                        $row->amount,
                        $row->type,
                        $row->status,
                        $row->description,
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

        return AdminApiResponse::success(
            $transaction->load('user'),
            'سکه با موفقیت اضافه شد.'
        );
    }

    public function apiShow(CoinTransaction $coin)
    {
        return AdminApiResponse::success($coin->load('user'));
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

        return AdminApiResponse::success(
            $coin->load('user'),
            'تراکنش سکه با موفقیت به‌روزرسانی شد.'
        );
    }

    public function apiDestroy(CoinTransaction $coin)
    {
        // Revert user's coin balance
        $user = $coin->user;
        $user->decrement('coins', $coin->amount);

        $coin->delete();

        return AdminApiResponse::okMessage('تراکنش سکه با موفقیت حذف شد.');
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

        return AdminApiResponse::okMessage($message);
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

        return AdminApiResponse::success([
            'stats' => $stats,
            'daily_stats' => $dailyStats,
        ]);
    }

    private function buildCoinTransactionQuery(Request $request)
    {
        $query = CoinTransaction::query()->with(['user']);

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

    private function applyCoinListSort($query, Request $request): void
    {
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'amount', 'type', 'status'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolveCoinsPerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}
