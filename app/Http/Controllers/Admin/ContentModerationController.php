<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Episode;
use App\Models\User;
use App\Models\Rating;
use App\Models\Report;
use App\Services\InAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ContentModerationController extends Controller
{
    protected $notificationService;

    public function __construct(InAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display moderation dashboard
     */
    public function index(Request $request)
    {
        $query = Story::with(['category', 'director', 'narrator', 'episodes']);

        // Apply moderation filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('moderation_status')) {
            $query->where('moderation_status', $request->moderation_status);
        }

        if ($request->filled('priority')) {
            $query->where('moderation_priority', $request->priority);
        }

        if ($request->filled('moderator_id')) {
            $query->where('moderator_id', $request->moderator_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('subtitle', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        switch ($sort) {
            case 'title':
                $query->orderBy('title', $direction);
                break;
            case 'moderation_status':
                $query->orderBy('moderation_status', $direction);
                break;
            case 'moderation_priority':
                $query->orderBy('moderation_priority', $direction);
                break;
            case 'moderator_id':
                $query->orderBy('moderator_id', $direction);
                break;
            case 'moderation_date':
                $query->orderBy('moderated_at', $direction);
                break;
            default:
                $query->orderBy('created_at', $direction);
        }

        $perPage = $request->get('per_page', 20);
        $perPage = min($perPage, 100);

        $stories = $query->paginate($perPage);
        
        // Get moderation statistics
        $stats = [
            'total_pending' => Story::where('moderation_status', 'pending')->count(),
            'total_approved' => Story::where('moderation_status', 'approved')->count(),
            'total_rejected' => Story::where('moderation_status', 'rejected')->count(),
            'total_flagged' => Story::where('moderation_status', 'flagged')->count(),
            'high_priority' => Story::where('moderation_priority', 'high')->count(),
            'medium_priority' => Story::where('moderation_priority', 'medium')->count(),
            'low_priority' => Story::where('moderation_priority', 'low')->count(),
            'pending_episodes' => Episode::where('moderation_status', 'pending')->count(),
            'total_reports' => Report::count(),
            'unresolved_reports' => Report::where('status', 'pending')->count(),
            'moderators' => User::where('role', 'moderator')->count(),
            'avg_moderation_time' => $this->getAverageModerationTime()
        ];

        // Get moderators for filter
        $moderators = User::where('role', 'moderator')->get();

        return view('admin.moderation.index', compact('stories', 'stats', 'moderators'));
    }

    /**
     * Show content for moderation
     */
    public function show(Story $story)
    {
        $story->load(['category', 'director', 'narrator', 'episodes', 'moderator']);
        
        // Get moderation history
        $moderationHistory = $story->moderationHistory ?? [];
        
        // Get reports for this story
        $reports = Report::where('content_type', 'story')
            ->where('content_id', $story->id)
            ->with('reporter')
            ->latest()
            ->get();

        // Get similar content for comparison
        $similarStories = Story::where('category_id', $story->category_id)
            ->where('id', '!=', $story->id)
            ->where('moderation_status', 'approved')
            ->limit(5)
            ->get();

        return view('admin.moderation.show', compact('story', 'moderationHistory', 'reports', 'similarStories'));
    }

    /**
     * Approve content
     */
    public function approve(Request $request, Story $story)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
            'rating' => 'nullable|integer|min:1|max:5',
            'age_rating' => 'nullable|string|in:G,PG,PG-13,R',
            'content_warnings' => 'nullable|array',
            'content_warnings.*' => 'string|max:100'
        ], [
            'notes.max' => 'یادداشت نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'rating.min' => 'امتیاز باید حداقل 1 باشد',
            'rating.max' => 'امتیاز نمی‌تواند بیشتر از 5 باشد',
            'age_rating.in' => 'رده سنی نامعتبر است',
            'content_warnings.array' => 'هشدارهای محتوا باید آرایه باشند',
            'content_warnings.*.max' => 'هر هشدار نمی‌تواند بیشتر از 100 کاراکتر باشد'
        ]);

        try {
            DB::beginTransaction();

            $story->update([
                'moderation_status' => 'approved',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
                'moderation_notes' => $request->notes,
                'moderation_rating' => $request->rating,
                'age_rating' => $request->age_rating,
                'content_warnings' => $request->content_warnings,
                'status' => 'published'
            ]);

            // Update episodes status
            $story->episodes()->update([
                'moderation_status' => 'approved',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
                'status' => 'published'
            ]);

            // Send notification to content creator
            if ($story->created_by) {
                $this->notificationService->createNotification(
                    $story->created_by,
                    'success',
                    'محتوای شما تایید شد',
                    "داستان '{$story->title}' با موفقیت تایید و منتشر شد.",
                    [
                        'action_type' => 'link',
                        'action_url' => route('stories.show', $story->id),
                        'action_text' => 'مشاهده داستان'
                    ]
                );
            }

            DB::commit();

            return redirect()->route('admin.moderation.index')
                ->with('success', 'محتوا با موفقیت تایید شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve content', [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در تایید محتوا: ' . $e->getMessage());
        }
    }

    /**
     * Reject content
     */
    public function reject(Request $request, Story $story)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'rejection_code' => 'required|string|in:inappropriate_content,violence,language,sexual_content,copyright_violation,quality_issues,other',
            'suggestions' => 'nullable|string|max:1000',
            'allow_resubmission' => 'boolean'
        ], [
            'reason.required' => 'دلیل رد الزامی است',
            'reason.max' => 'دلیل رد نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'rejection_code.required' => 'کد رد الزامی است',
            'rejection_code.in' => 'کد رد نامعتبر است',
            'suggestions.max' => 'پیشنهادات نمی‌تواند بیشتر از 1000 کاراکتر باشد'
        ]);

        try {
            DB::beginTransaction();

            $story->update([
                'moderation_status' => 'rejected',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
                'moderation_notes' => $request->reason,
                'rejection_code' => $request->rejection_code,
                'rejection_suggestions' => $request->suggestions,
                'allow_resubmission' => $request->boolean('allow_resubmission'),
                'status' => 'rejected'
            ]);

            // Update episodes status
            $story->episodes()->update([
                'moderation_status' => 'rejected',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
                'status' => 'rejected'
            ]);

            // Send notification to content creator
            if ($story->created_by) {
                $this->notificationService->createNotification(
                    $story->created_by,
                    'error',
                    'محتوای شما رد شد',
                    "داستان '{$story->title}' رد شد. دلیل: {$request->reason}",
                    [
                        'action_type' => 'link',
                        'action_url' => route('stories.edit', $story->id),
                        'action_text' => 'ویرایش داستان'
                    ]
                );
            }

            DB::commit();

            return redirect()->route('admin.moderation.index')
                ->with('success', 'محتوا با موفقیت رد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject content', [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در رد محتوا: ' . $e->getMessage());
        }
    }

    /**
     * Flag content for review
     */
    public function flag(Request $request, Story $story)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'priority' => 'required|string|in:low,medium,high',
            'flag_type' => 'required|string|in:content_concern,quality_issue,copyright_issue,other'
        ], [
            'reason.required' => 'دلیل پرچم‌گذاری الزامی است',
            'reason.max' => 'دلیل پرچم‌گذاری نمی‌تواند بیشتر از 1000 کاراکتر باشد',
            'priority.required' => 'اولویت الزامی است',
            'priority.in' => 'اولویت نامعتبر است',
            'flag_type.required' => 'نوع پرچم الزامی است',
            'flag_type.in' => 'نوع پرچم نامعتبر است'
        ]);

        try {
            DB::beginTransaction();

            $story->update([
                'moderation_status' => 'flagged',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
                'moderation_notes' => $request->reason,
                'moderation_priority' => $request->priority,
                'flag_type' => $request->flag_type,
                'status' => 'pending'
            ]);

            DB::commit();

            return redirect()->route('admin.moderation.index')
                ->with('success', 'محتوا با موفقیت پرچم‌گذاری شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to flag content', [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در پرچم‌گذاری محتوا: ' . $e->getMessage());
        }
    }

    /**
     * Get average moderation time
     */
    private function getAverageModerationTime()
    {
        $avgTime = Story::whereNotNull('moderated_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, moderated_at)) as avg_hours')
            ->value('avg_hours');

        return round($avgTime ?? 0, 2);
    }
}