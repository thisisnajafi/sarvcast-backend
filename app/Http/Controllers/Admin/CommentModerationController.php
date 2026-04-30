<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\StoryComment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CommentModerationController extends Controller
{
    private function resolveCommentModerationPerPage(Request $request): int
    {
        $raw = $request->input('perPage', $request->input('per_page', 20));
        $n = is_numeric($raw) ? (int) $raw : 20;

        return max(1, min(100, $n));
    }

    private function applyCommentModerationDateFilters(Builder $query, Request $request): void
    {
        if ($request->filled('date_range')) {
            $preset = $request->string('date_range')->toString();
            $now = Carbon::now();
            match ($preset) {
                'today' => $query->whereDate('story_comments.created_at', $now->toDateString()),
                'week' => $query->where('story_comments.created_at', '>=', $now->copy()->subWeek()),
                'month' => $query->where('story_comments.created_at', '>=', $now->copy()->subMonth()),
                'year' => $query->where('story_comments.created_at', '>=', $now->copy()->subYear()),
                default => null,
            };
        } elseif ($request->filled('dateFrom') || $request->filled('dateTo')) {
            if ($request->filled('dateFrom')) {
                $query->whereDate('story_comments.created_at', '>=', $request->string('dateFrom')->toString());
            }
            if ($request->filled('dateTo')) {
                $query->whereDate('story_comments.created_at', '<=', $request->string('dateTo')->toString());
            }
        }
    }

    private function buildCommentModerationApiListQuery(Request $request): Builder
    {
        $query = StoryComment::query()
            ->with(['user', 'story', 'approver']);

        $search = $request->filled('q')
            ? $request->string('q')->toString()
            : ($request->filled('search') ? $request->string('search')->toString() : null);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('story_comments.content', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'pending') {
                $query->where('story_comments.is_approved', false)->where('story_comments.is_visible', true);
            } elseif ($status === 'approved') {
                $query->where('story_comments.is_approved', true)->where('story_comments.is_visible', true);
            } elseif ($status === 'rejected') {
                $query->where('story_comments.is_visible', false);
            }
        }

        if ($request->filled('is_pinned')) {
            $query->where('story_comments.is_pinned', $request->boolean('is_pinned'));
        }

        if ($request->filled('story_id')) {
            $query->where('story_comments.story_id', (int) $request->input('story_id'));
        }

        $this->applyCommentModerationDateFilters($query, $request);

        return $query;
    }

    private function applyCommentModerationListSort(Builder $query, Request $request): void
    {
        $sortBy = $request->input('sortBy', $request->input('sort_by', 'created_at'));
        $sortDir = strtolower((string) $request->input('sortDir', $request->input('sort_direction', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $column = match ($sortBy) {
            'id' => 'story_comments.id',
            'story_id' => 'story_comments.story_id',
            'is_pinned' => 'story_comments.is_pinned',
            default => 'story_comments.created_at',
        };

        $query->orderBy($column, $sortDir)->orderBy('story_comments.id', 'desc');
    }

    public function apiIndex(Request $request)
    {
        try {
            $query = $this->buildCommentModerationApiListQuery($request);
            $this->applyCommentModerationListSort($query, $request);
            $perPage = $this->resolveCommentModerationPerPage($request);

            return AdminApiResponse::paginated(
                $query->paginate($perPage)->appends($request->query())
            );
        } catch (\Throwable $e) {
            Log::error('Comment moderation apiIndex failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بارگذاری نظرات.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiExport(Request $request)
    {
        try {
            $query = $this->buildCommentModerationApiListQuery($request);
            $this->applyCommentModerationListSort($query, $request);

            $filename = 'comment-moderation-'.now()->format('Y-m-d-His').'.csv';

            return response()->streamDownload(function () use ($query) {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($handle, ['id', 'story_id', 'user_id', 'content', 'is_approved', 'is_visible', 'is_pinned', 'created_at']);

                $query->clone()->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->story_id,
                            $row->user_id,
                            $row->content,
                            $row->is_approved ? '1' : '0',
                            $row->is_visible ? '1' : '0',
                            $row->is_pinned ? '1' : '0',
                            $row->created_at?->toIso8601String(),
                        ]);
                    }
                });

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Throwable $e) {
            Log::error('Comment moderation apiExport failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در خروجی CSV.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiShow(StoryComment $comment)
    {
        try {
            return AdminApiResponse::success(
                $comment->load(['user', 'story', 'approver', 'replies.user'])
            );
        } catch (\Throwable $e) {
            Log::error('Comment moderation apiShow failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بارگذاری نظر.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $commentIds = $request->input('comment_ids', $request->input('selected_items', []));
        if (! is_array($commentIds)) {
            $commentIds = [];
        }

        $validator = Validator::make(
            [
                'action' => $request->input('action'),
                'comment_ids' => $commentIds,
            ],
            [
                'action' => 'required|in:approve,reject,pin,unpin,delete',
                'comment_ids' => 'required|array|min:1',
                'comment_ids.*' => 'integer|exists:story_comments,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $comments = StoryComment::whereIn('id', $commentIds)->get();
            foreach ($comments as $comment) {
                switch ($request->action) {
                    case 'approve':
                        $comment->update([
                            'is_approved' => true,
                            'is_visible' => true,
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        break;
                    case 'reject':
                        $comment->update([
                            'is_approved' => false,
                            'is_visible' => false,
                        ]);
                        break;
                    case 'pin':
                        $comment->update(['is_pinned' => true]);
                        break;
                    case 'unpin':
                        $comment->update(['is_pinned' => false]);
                        break;
                    case 'delete':
                        $comment->delete();
                        break;
                }
            }

            DB::commit();

            return AdminApiResponse::okMessage('عملیات گروهی با موفقیت انجام شد.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Comment moderation apiBulkAction failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات گروهی.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiStatistics()
    {
        try {
            return AdminApiResponse::success([
                'total_comments' => StoryComment::count(),
                'pending_comments' => StoryComment::where('is_approved', false)->where('is_visible', true)->count(),
                'approved_comments' => StoryComment::where('is_approved', true)->where('is_visible', true)->count(),
                'rejected_comments' => StoryComment::where('is_visible', false)->count(),
                'pinned_comments' => StoryComment::where('is_pinned', true)->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Comment moderation apiStatistics failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بارگذاری آمار.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }
}
