<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\InAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    public function __construct(
        protected InAppNotificationService $inAppNotificationService
    ) {}

    /**
     * GET /api/admin/support-tickets
     * Filters: status, priority, search (subject), assigned_to
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::with(['user', 'assignee'])
            ->withCount('messages');

        // Latest message eager-load (sub-select approach via relationship)
        $query->with(['messages' => function ($q) {
            $q->latest()->limit(1);
        }]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('subject', 'like', "%{$search}%");
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate($request->integer('perPage', 20));

        return AdminApiResponse::paginated($tickets);
    }

    /**
     * GET /api/admin/support-tickets/{ticket}
     */
    public function show(SupportTicket $ticket): JsonResponse
    {
        $ticket->load(['user', 'assignee', 'messages.author']);

        return AdminApiResponse::success($ticket);
    }

    /**
     * POST /api/admin/support-tickets
     * Admin creates a ticket on behalf of a user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'  => 'nullable|exists:users,id',
            'subject'  => 'required|string|max:255',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'channel'  => 'nullable|string|max:50',
            'body'     => 'nullable|string|max:10000',
        ]);

        $ticket = SupportTicket::create([
            'user_id'  => $validated['user_id'] ?? null,
            'subject'  => $validated['subject'],
            'priority' => $validated['priority'] ?? SupportTicket::PRIORITY_NORMAL,
            'channel'  => $validated['channel'] ?? 'web',
            'status'   => SupportTicket::STATUS_OPEN,
        ]);

        if (! empty($validated['body'])) {
            SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'author_user_id'    => Auth::id(),
                'body'              => $validated['body'],
                'is_staff'          => true,
            ]);
        }

        $ticket->load(['user', 'assignee', 'messages']);

        return AdminApiResponse::success($ticket, 'تیکت پشتیبانی ایجاد شد.', 201);
    }

    /**
     * POST /api/admin/support-tickets/{ticket}/reply
     * Staff reply. Sets status to pending if ticket was open.
     */
    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:10000',
        ]);

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'author_user_id'    => Auth::id(),
            'body'              => $validated['body'],
            'is_staff'          => true,
        ]);

        // Set status pending if was open
        if ($ticket->status === SupportTicket::STATUS_OPEN) {
            $ticket->update(['status' => SupportTicket::STATUS_PENDING]);
        }

        // Notify the user (in-app)
        if ($ticket->user_id) {
            try {
                $this->inAppNotificationService->createNotification(
                    $ticket->user_id,
                    'support',
                    'پاسخ به تیکت پشتیبانی شما',
                    "تیم پشتیبانی به تیکت «{$ticket->subject}» پاسخ داده است.",
                    ['send_push' => true]
                );
            } catch (\Throwable $e) {
                // Non-fatal — log and continue
                \Illuminate\Support\Facades\Log::warning('Support reply notification failed', [
                    'ticket_id' => $ticket->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $ticket->refresh()->load(['user', 'assignee', 'messages.author']);

        return AdminApiResponse::success([
            'ticket'  => $ticket,
            'message' => $message,
        ], 'پاسخ ارسال شد.');
    }

    /**
     * PATCH /api/admin/support-tickets/{ticket}
     * Update status, priority, assigned_to.
     */
    public function update(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'status'      => 'nullable|in:open,pending,closed,resolved',
            'priority'    => 'nullable|in:low,normal,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update(array_filter($validated, fn ($v) => $v !== null));

        $ticket->refresh()->load(['user', 'assignee']);

        return AdminApiResponse::success($ticket, 'تیکت به‌روزرسانی شد.');
    }

    /**
     * POST /api/admin/support-tickets/{ticket}/close
     */
    public function close(SupportTicket $ticket): JsonResponse
    {
        $ticket->update([
            'status'    => SupportTicket::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        return AdminApiResponse::success($ticket, 'تیکت بسته شد.');
    }

    /**
     * GET /api/admin/support-tickets/statistics/data
     */
    public function statistics(): JsonResponse
    {
        $counts = SupportTicket::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return AdminApiResponse::success([
            'total'    => SupportTicket::count(),
            'open'     => $counts->get('open', 0),
            'pending'  => $counts->get('pending', 0),
            'closed'   => $counts->get('closed', 0),
            'resolved' => $counts->get('resolved', 0),
        ]);
    }

    /**
     * POST /api/admin/support-tickets/bulk-action
     * Supported actions: close, assign
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action'      => 'required|in:close,assign',
            'ids'         => 'required|array|min:1',
            'ids.*'       => 'integer',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $query = SupportTicket::whereIn('id', $validated['ids']);

        if ($validated['action'] === 'close') {
            $affected = $query->update([
                'status'    => SupportTicket::STATUS_CLOSED,
                'closed_at' => now(),
            ]);
        } else {
            $affected = $query->update([
                'assigned_to' => $validated['assigned_to'],
            ]);
        }

        return AdminApiResponse::success(['affected' => $affected], "عملیات دسته‌جمعی انجام شد.");
    }
}
