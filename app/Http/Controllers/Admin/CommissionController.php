<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\Commission;
use App\Models\AffiliatePartner;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CommissionController extends Controller
{
    // ── LIST ─────────────────────────────────────────────────────────────────

    public function apiIndex(Request $request)
    {
        $query = $this->buildQuery($request);
        $this->applySort($query, $request);

        $perPage = $this->resolvePerPage($request);
        $page    = max(1, (int) $request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return AdminApiResponse::paginated($paginator);
    }

    // ── SHOW ──────────────────────────────────────────────────────────────────

    public function apiShow(Commission $commission)
    {
        return AdminApiResponse::success(
            $commission->load(['affiliatePartner', 'user', 'subscription'])
        );
    }

    // ── STATISTICS ────────────────────────────────────────────────────────────

    public function apiStatistics()
    {
        $stats = [
            'total_commissions'  => Commission::count(),
            'total_amount'       => Commission::sum('commission_amount'),
            'paid_amount'        => Commission::where('status', 'paid')->sum('commission_amount'),
            'pending_amount'     => Commission::where('status', 'pending')->sum('commission_amount'),
            'approved_amount'    => Commission::where('status', 'approved')->sum('commission_amount'),
            'pending_count'      => Commission::where('status', 'pending')->count(),
            'approved_count'     => Commission::where('status', 'approved')->count(),
            'paid_count'         => Commission::where('status', 'paid')->count(),
            'cancelled_count'    => Commission::where('status', 'cancelled')->count(),
            'average_commission' => Commission::avg('commission_amount'),
        ];

        $dailyStats = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyStats[] = [
                'date'   => $date->format('Y-m-d'),
                'amount' => Commission::whereDate('created_at', $date)->sum('commission_amount'),
                'count'  => Commission::whereDate('created_at', $date)->count(),
            ];
        }

        return AdminApiResponse::success([
            'stats'       => $stats,
            'daily_stats' => $dailyStats,
        ]);
    }

    // ── APPROVE ───────────────────────────────────────────────────────────────

    public function apiApprove(Commission $commission)
    {
        if (! in_array($commission->status, ['pending'], true)) {
            return AdminApiResponse::success(
                $commission,
                'تنها کمیسیون‌های در انتظار قابل تأیید هستند.'
            );
        }

        $commission->approve();

        return AdminApiResponse::success(
            $commission->fresh(['affiliatePartner', 'user']),
            'کمیسیون با موفقیت تأیید شد.'
        );
    }

    // ── REJECT / CANCEL ───────────────────────────────────────────────────────

    public function apiReject(Request $request, Commission $commission)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if (! in_array($commission->status, ['pending', 'approved'], true)) {
            return AdminApiResponse::success(
                $commission,
                'کمیسیون در وضعیتی نیست که رد شود.'
            );
        }

        $commission->cancel($request->reason);

        return AdminApiResponse::success(
            $commission->fresh(['affiliatePartner', 'user']),
            'کمیسیون با موفقیت رد شد.'
        );
    }

    // ── BULK ACTION ───────────────────────────────────────────────────────────

    public function apiBulkAction(Request $request)
    {
        $ids = $request->input('commission_ids', $request->input('selected_items', []));
        $request->merge(['selected_items' => is_array($ids) ? $ids : []]);

        $request->validate([
            'action'           => 'required|in:approve,cancel,mark_paid',
            'selected_items'   => 'required|array|min:1',
            'selected_items.*' => 'exists:commissions,id',
            'reason'           => 'nullable|string|max:500',
        ]);

        $commissions = Commission::whereIn('id', $request->selected_items)->get();

        switch ($request->action) {
            case 'approve':
                $commissions->each(fn ($c) => $c->approve());
                $message = 'کمیسیون‌های انتخاب‌شده تأیید شدند.';
                break;
            case 'mark_paid':
                Commission::whereIn('id', $request->selected_items)->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                ]);
                $message = 'کمیسیون‌های انتخاب‌شده پرداخت‌شده علامت‌گذاری شدند.';
                break;
            case 'cancel':
                $commissions->each(fn ($c) => $c->cancel($request->reason));
                $message = 'کمیسیون‌های انتخاب‌شده لغو شدند.';
                break;
        }

        return AdminApiResponse::okMessage($message);
    }

    // ── EXPORT ────────────────────────────────────────────────────────────────

    public function apiExport(Request $request)
    {
        $query = $this->buildQuery($request);
        $this->applySort($query, $request);

        $filename = 'commissions-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, [
                'id', 'affiliate_partner_id', 'partner_name',
                'user_id', 'subscription_amount', 'commission_rate',
                'commission_amount', 'status', 'commission_period_start',
                'commission_period_end', 'paid_at', 'created_at',
            ]);

            $query->clone()->with('affiliatePartner')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->affiliate_partner_id,
                        $row->affiliatePartner?->name ?? '',
                        $row->user_id,
                        $row->subscription_amount,
                        $row->commission_rate,
                        $row->commission_amount,
                        $row->status,
                        $row->commission_period_start?->format('Y-m-d'),
                        $row->commission_period_end?->format('Y-m-d'),
                        $row->paid_at?->toIso8601String(),
                        $row->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────────────────────

    private function buildQuery(Request $request)
    {
        $query = Commission::query()->with(['affiliatePartner', 'user']);

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $query->whereHas('affiliatePartner', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('partner_id')) {
            $query->where('affiliate_partner_id', (int) $request->partner_id);
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('commission_period_start', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('commission_period_start', '<=', $request->dateTo);
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

    private function applySort($query, Request $request): void
    {
        $sortBy  = $request->input('sortBy', 'created_at');
        $sortDir = strtolower((string) $request->input('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'id', 'commission_amount', 'status', 'commission_period_start', 'paid_at'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir);
    }

    private function resolvePerPage(Request $request): int
    {
        $raw = (int) $request->input('perPage', $request->input('per_page', 20));

        return min(100, max(1, $raw ?: 20));
    }
}
