<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoryComment;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommentsManagementController extends Controller
{
    /**
     * Display a listing of comments
     */
    public function index(Request $request): View
    {
        $query = StoryComment::with(['story', 'user', 'parent', 'replies'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'pending':
                    $query->where('is_approved', false);
                    break;
                case 'approved':
                    $query->where('is_approved', true);
                    break;
                case 'rejected':
                    $query->where('is_approved', false)->where('is_visible', false);
                    break;
                case 'pinned':
                    $query->where('is_pinned', true);
                    break;
            }
        }

        // Filter by story
        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('phone_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('story', function ($storyQuery) use ($search) {
                      $storyQuery->where('title', 'like', "%{$search}%");
                  });
            });
        }

        $comments = $query->paginate(20);

        // Get filter options
        $stories = Story::select('id', 'title')->orderBy('title')->get();
        $users = User::select('id', 'name', 'phone_number')
            ->whereHas('storyComments')
            ->orderBy('name')
            ->get();

        // Statistics
        $stats = [
            'total' => StoryComment::count(),
            'pending' => StoryComment::where('is_approved', false)->count(),
            'approved' => StoryComment::where('is_approved', true)->count(),
            'rejected' => StoryComment::where('is_approved', false)->where('is_visible', false)->count(),
            'pinned' => StoryComment::where('is_pinned', true)->count(),
            'today' => StoryComment::whereDate('created_at', today())->count(),
            'this_week' => StoryComment::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => StoryComment::whereMonth('created_at', now()->month)->count(),
        ];

        return view('admin.comments.index', compact('comments', 'stories', 'users', 'stats'));
    }

    /**
     * Display pending comments
     */
    public function pending(Request $request): View
    {
        $request->merge(['status' => 'pending']);
        return $this->index($request);
    }

    /**
     * Display approved comments
     */
    public function approved(Request $request): View
    {
        $request->merge(['status' => 'approved']);
        return $this->index($request);
    }

    /**
     * Display rejected comments
     */
    public function rejected(Request $request): View
    {
        $request->merge(['status' => 'rejected']);
        return $this->index($request);
    }

    /**
     * Display the specified comment
     */
    public function show(StoryComment $comment): View
    {
        $comment->load(['story', 'user', 'parent', 'replies.user', 'approver']);
        
        return view('admin.comments.show', compact('comment'));
    }

    /**
     * Approve a comment
     */
    public function approve(StoryComment $comment): RedirectResponse
    {
        $comment->update([
            'is_approved' => true,
            'is_visible' => true,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Update parent's replies count if this is a reply
        if ($comment->parent_id) {
            $parent = $comment->parent;
            $parent->increment('replies_count');
        }

        return redirect()->back()->with('success', 'نظر با موفقیت تایید شد.');
    }

    /**
     * Reject a comment
     */
    public function reject(StoryComment $comment): RedirectResponse
    {
        $comment->update([
            'is_approved' => false,
            'is_visible' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return redirect()->back()->with('success', 'نظر با موفقیت رد شد.');
    }

    /**
     * Pin a comment
     */
    public function pin(StoryComment $comment): RedirectResponse
    {
        $comment->update(['is_pinned' => true]);

        return redirect()->back()->with('success', 'نظر با موفقیت سنجاق شد.');
    }

    /**
     * Unpin a comment
     */
    public function unpin(StoryComment $comment): RedirectResponse
    {
        $comment->update(['is_pinned' => false]);

        return redirect()->back()->with('success', 'نظر با موفقیت از سنجاق خارج شد.');
    }

    /**
     * Remove the specified comment
     */
    public function destroy(StoryComment $comment): RedirectResponse
    {
        // Update parent's replies count if this is a reply
        if ($comment->parent_id) {
            $parent = $comment->parent;
            $parent->decrement('replies_count');
        }

        // Delete all replies first
        $comment->replies()->delete();
        
        // Delete the comment
        $comment->delete();

        return redirect()->route('admin.comments.index')->with('success', 'نظر با موفقیت حذف شد.');
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject,delete,pin,unpin',
            'comment_ids' => 'required|array|min:1',
            'comment_ids.*' => 'exists:story_comments,id',
        ]);

        $commentIds = $request->comment_ids;
        $action = $request->action;

        switch ($action) {
            case 'approve':
                StoryComment::whereIn('id', $commentIds)->update([
                    'is_approved' => true,
                    'is_visible' => true,
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);
                $message = 'نظرات انتخاب شده با موفقیت تایید شدند.';
                break;

            case 'reject':
                StoryComment::whereIn('id', $commentIds)->update([
                    'is_approved' => false,
                    'is_visible' => false,
                    'approved_at' => null,
                    'approved_by' => null,
                ]);
                $message = 'نظرات انتخاب شده با موفقیت رد شدند.';
                break;

            case 'delete':
                // Delete replies first
                StoryComment::whereIn('parent_id', $commentIds)->delete();
                // Delete main comments
                StoryComment::whereIn('id', $commentIds)->delete();
                $message = 'نظرات انتخاب شده با موفقیت حذف شدند.';
                break;

            case 'pin':
                StoryComment::whereIn('id', $commentIds)->update(['is_pinned' => true]);
                $message = 'نظرات انتخاب شده با موفقیت سنجاق شدند.';
                break;

            case 'unpin':
                StoryComment::whereIn('id', $commentIds)->update(['is_pinned' => false]);
                $message = 'نظرات انتخاب شده با موفقیت از سنجاق خارج شدند.';
                break;
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Get comments statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_comments' => StoryComment::count(),
            'pending_comments' => StoryComment::where('is_approved', false)->count(),
            'approved_comments' => StoryComment::where('is_approved', true)->count(),
            'rejected_comments' => StoryComment::where('is_approved', false)->where('is_visible', false)->count(),
            'pinned_comments' => StoryComment::where('is_pinned', true)->count(),
            'total_likes' => StoryComment::sum('likes_count'),
            'total_replies' => StoryComment::sum('replies_count'),
        ];

        // Comments by status
        $commentsByStatus = StoryComment::selectRaw('
            CASE 
                WHEN is_approved = 1 THEN "approved"
                WHEN is_approved = 0 AND is_visible = 1 THEN "pending"
                ELSE "rejected"
            END as status,
            COUNT(*) as count
        ')->groupBy('status')->get();

        // Comments over time (last 30 days)
        $commentsOverTime = StoryComment::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top stories by comments
        $topStoriesByComments = Story::withCount('comments')
            ->orderBy('comments_count', 'desc')
            ->limit(10)
            ->get();

        // Top users by comments
        $topUsersByComments = User::withCount('storyComments')
            ->orderBy('story_comments_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'comments_by_status' => $commentsByStatus,
                'comments_over_time' => $commentsOverTime,
                'top_stories_by_comments' => $topStoriesByComments,
                'top_users_by_comments' => $topUsersByComments,
            ]
        ]);
    }

    /**
     * Export comments
     */
    public function export(Request $request)
    {
        $query = StoryComment::with(['story', 'user']);

        // Apply same filters as index
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'pending':
                    $query->where('is_approved', false);
                    break;
                case 'approved':
                    $query->where('is_approved', true);
                    break;
                case 'rejected':
                    $query->where('is_approved', false)->where('is_visible', false);
                    break;
                case 'pinned':
                    $query->where('is_pinned', true);
                    break;
            }
        }

        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $comments = $query->orderBy('created_at', 'desc')->get();

        $filename = 'comments_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($comments) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, [
                'ID',
                'Story Title',
                'User Name',
                'User Phone',
                'Content',
                'Status',
                'Is Pinned',
                'Likes Count',
                'Replies Count',
                'Parent ID',
                'Created At',
                'Approved At',
                'Approved By'
            ]);

            // Data
            foreach ($comments as $comment) {
                $status = $comment->is_approved ? 'Approved' : ($comment->is_visible ? 'Pending' : 'Rejected');
                
                fputcsv($file, [
                    $comment->id,
                    $comment->story->title ?? '',
                    $comment->user->name ?? '',
                    $comment->user->phone_number ?? '',
                    $comment->content,
                    $status,
                    $comment->is_pinned ? 'Yes' : 'No',
                    $comment->likes_count,
                    $comment->replies_count,
                    $comment->parent_id ?? '',
                    $comment->created_at->format('Y-m-d H:i:s'),
                    $comment->approved_at ? $comment->approved_at->format('Y-m-d H:i:s') : '',
                    $comment->approver->name ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
