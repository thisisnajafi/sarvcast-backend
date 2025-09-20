<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentModeration;
use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ContentModerationController extends Controller
{
    /**
     * Display a listing of content moderation items.
     */
    public function index(Request $request)
    {
        $query = ContentModeration::with(['user', 'story', 'episode']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content_type', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('story', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('episode', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by content type
        if ($request->filled('content_type')) {
            $query->where('content_type', $request->content_type);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by moderator
        if ($request->filled('moderator_id')) {
            $query->where('moderator_id', $request->moderator_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $moderations = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total' => ContentModeration::count(),
            'pending' => ContentModeration::where('status', 'pending')->count(),
            'approved' => ContentModeration::where('status', 'approved')->count(),
            'rejected' => ContentModeration::where('status', 'rejected')->count(),
            'high_severity' => ContentModeration::where('severity', 'high')->count(),
            'medium_severity' => ContentModeration::where('severity', 'medium')->count(),
            'low_severity' => ContentModeration::where('severity', 'low')->count(),
        ];

        $moderators = User::where('role', 'admin')->orWhere('role', 'moderator')->get();
        $stories = Story::where('status', 'published')->get();
        $episodes = Episode::where('status', 'published')->get();

        return view('admin.content-moderation.index', compact('moderations', 'stats', 'moderators', 'stories', 'episodes'));
    }

    /**
     * Show the form for creating a new content moderation item.
     */
    public function create()
    {
        $users = User::where('is_active', true)->get();
        $stories = Story::where('status', 'published')->get();
        $episodes = Episode::where('status', 'published')->get();
        return view('admin.content-moderation.create', compact('users', 'stories', 'episodes'));
    }

    /**
     * Store a newly created content moderation item.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'content_type' => 'required|in:story,episode,comment,review,user_profile',
            'content_id' => 'required|integer',
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'reason' => 'required|string|max:255',
            'severity' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,approved,rejected',
            'notes' => 'nullable|string|max:1000',
            'evidence_files' => 'nullable|array|max:5',
            'evidence_files.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'user_id' => $request->user_id,
                'content_type' => $request->content_type,
                'content_id' => $request->content_id,
                'story_id' => $request->story_id,
                'episode_id' => $request->episode_id,
                'reason' => $request->reason,
                'severity' => $request->severity,
                'status' => $request->status,
                'notes' => $request->notes,
                'moderator_id' => auth()->id(),
            ];

            // Handle evidence files upload
            if ($request->hasFile('evidence_files')) {
                $evidenceFiles = [];
                foreach ($request->file('evidence_files') as $file) {
                    $path = $file->store('moderation/evidence', 'public');
                    $evidenceFiles[] = $path;
                }
                $data['evidence_files'] = json_encode($evidenceFiles);
            }

            $moderation = ContentModeration::create($data);

            DB::commit();

            return redirect()->route('admin.content-moderation.index')
                ->with('success', 'آیتم نظارت بر محتوا با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در ایجاد آیتم نظارت بر محتوا: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified content moderation item.
     */
    public function show(ContentModeration $contentModeration)
    {
        $contentModeration->load(['user', 'story', 'episode', 'moderator']);
        
        // Get related moderations
        $relatedModerations = ContentModeration::where('user_id', $contentModeration->user_id)
            ->where('id', '!=', $contentModeration->id)
            ->with(['story', 'episode'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.content-moderation.show', compact('contentModeration', 'relatedModerations'));
    }

    /**
     * Show the form for editing the specified content moderation item.
     */
    public function edit(ContentModeration $contentModeration)
    {
        $users = User::where('is_active', true)->get();
        $stories = Story::where('status', 'published')->get();
        $episodes = Episode::where('status', 'published')->get();
        return view('admin.content-moderation.edit', compact('contentModeration', 'users', 'stories', 'episodes'));
    }

    /**
     * Update the specified content moderation item.
     */
    public function update(Request $request, ContentModeration $contentModeration)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'content_type' => 'required|in:story,episode,comment,review,user_profile',
            'content_id' => 'required|integer',
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'reason' => 'required|string|max:255',
            'severity' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,approved,rejected',
            'notes' => 'nullable|string|max:1000',
            'evidence_files' => 'nullable|array|max:5',
            'evidence_files.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = [
                'user_id' => $request->user_id,
                'content_type' => $request->content_type,
                'content_id' => $request->content_id,
                'story_id' => $request->story_id,
                'episode_id' => $request->episode_id,
                'reason' => $request->reason,
                'severity' => $request->severity,
                'status' => $request->status,
                'notes' => $request->notes,
                'moderator_id' => auth()->id(),
            ];

            // Handle evidence files upload
            if ($request->hasFile('evidence_files')) {
                // Delete old evidence files
                if ($contentModeration->evidence_files) {
                    $oldFiles = json_decode($contentModeration->evidence_files, true);
                    foreach ($oldFiles as $file) {
                        Storage::disk('public')->delete($file);
                    }
                }

                $evidenceFiles = [];
                foreach ($request->file('evidence_files') as $file) {
                    $path = $file->store('moderation/evidence', 'public');
                    $evidenceFiles[] = $path;
                }
                $data['evidence_files'] = json_encode($evidenceFiles);
            }

            $contentModeration->update($data);

            DB::commit();

            return redirect()->route('admin.content-moderation.index')
                ->with('success', 'آیتم نظارت بر محتوا با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'خطا در به‌روزرسانی آیتم نظارت بر محتوا: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified content moderation item.
     */
    public function destroy(ContentModeration $contentModeration)
    {
        try {
            // Delete associated evidence files
            if ($contentModeration->evidence_files) {
                $files = json_decode($contentModeration->evidence_files, true);
                foreach ($files as $file) {
                    Storage::disk('public')->delete($file);
                }
            }

            $contentModeration->delete();
            return redirect()->route('admin.content-moderation.index')
                ->with('success', 'آیتم نظارت بر محتوا با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در حذف آیتم نظارت بر محتوا: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve content moderation.
     */
    public function approve(ContentModeration $contentModeration)
    {
        try {
            $contentModeration->update([
                'status' => 'approved',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
            ]);

            return redirect()->back()
                ->with('success', 'آیتم نظارت بر محتوا با موفقیت تأیید شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در تأیید آیتم نظارت بر محتوا: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject content moderation.
     */
    public function reject(ContentModeration $contentModeration)
    {
        try {
            $contentModeration->update([
                'status' => 'rejected',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
            ]);

            return redirect()->back()
                ->with('success', 'آیتم نظارت بر محتوا با موفقیت رد شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'خطا در رد آیتم نظارت بر محتوا: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk actions on content moderation items.
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject,delete',
            'moderation_ids' => 'required|array|min:1',
            'moderation_ids.*' => 'exists:content_moderations,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors(['error' => 'لطفاً حداقل یک آیتم را انتخاب کنید.']);
        }

        try {
            DB::beginTransaction();

            $moderations = ContentModeration::whereIn('id', $request->moderation_ids);

            switch ($request->action) {
                case 'approve':
                    $moderations->update([
                        'status' => 'approved',
                        'moderator_id' => auth()->id(),
                        'moderated_at' => now(),
                    ]);
                    break;

                case 'reject':
                    $moderations->update([
                        'status' => 'rejected',
                        'moderator_id' => auth()->id(),
                        'moderated_at' => now(),
                    ]);
                    break;

                case 'delete':
                    // Delete associated evidence files
                    foreach ($moderations->get() as $moderation) {
                        if ($moderation->evidence_files) {
                            $files = json_decode($moderation->evidence_files, true);
                            foreach ($files as $file) {
                                Storage::disk('public')->delete($file);
                            }
                        }
                    }
                    $moderations->delete();
                    break;
            }

            DB::commit();

            $actionLabels = [
                'approve' => 'تأیید',
                'reject' => 'رد',
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
     * Export content moderation data.
     */
    public function export(Request $request)
    {
        $query = ContentModeration::with(['user', 'story', 'episode', 'moderator']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('content_type')) {
            $query->where('content_type', $request->content_type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $moderations = $query->orderBy('created_at', 'desc')->get();

        $filename = 'content_moderation_' . now()->format('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($moderations) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, [
                'کاربر',
                'نوع محتوا',
                'شناسه محتوا',
                'داستان',
                'اپیزود',
                'دلیل',
                'شدت',
                'وضعیت',
                'یادداشت',
                'ناظر',
                'تاریخ ایجاد',
                'تاریخ نظارت'
            ]);

            foreach ($moderations as $moderation) {
                fputcsv($file, [
                    $moderation->user ? $moderation->user->first_name . ' ' . $moderation->user->last_name : '-',
                    $this->getContentTypeLabel($moderation->content_type),
                    $moderation->content_id,
                    $moderation->story ? $moderation->story->title : '-',
                    $moderation->episode ? $moderation->episode->title : '-',
                    $moderation->reason,
                    $this->getSeverityLabel($moderation->severity),
                    $this->getStatusLabel($moderation->status),
                    $moderation->notes,
                    $moderation->moderator ? $moderation->moderator->first_name . ' ' . $moderation->moderator->last_name : '-',
                    $moderation->created_at->format('Y/m/d H:i'),
                    $moderation->moderated_at ? $moderation->moderated_at->format('Y/m/d H:i') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get content moderation statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_moderations' => ContentModeration::count(),
            'pending_moderations' => ContentModeration::where('status', 'pending')->count(),
            'approved_moderations' => ContentModeration::where('status', 'approved')->count(),
            'rejected_moderations' => ContentModeration::where('status', 'rejected')->count(),
            'by_content_type' => ContentModeration::selectRaw('content_type, COUNT(*) as count')
                ->groupBy('content_type')
                ->get(),
            'by_severity' => ContentModeration::selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->get(),
            'by_status' => ContentModeration::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'top_reporters' => ContentModeration::selectRaw('user_id, COUNT(*) as report_count')
                ->with('user')
                ->groupBy('user_id')
                ->orderBy('report_count', 'desc')
                ->limit(10)
                ->get(),
            'moderations_by_month' => ContentModeration::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
        ];

        return view('admin.content-moderation.statistics', compact('stats'));
    }

    /**
     * Get content type label.
     */
    private function getContentTypeLabel($type)
    {
        $labels = [
            'story' => 'داستان',
            'episode' => 'اپیزود',
            'comment' => 'نظر',
            'review' => 'بررسی',
            'user_profile' => 'پروفایل کاربر',
        ];

        return $labels[$type] ?? $type;
    }

    /**
     * Get severity label.
     */
    private function getSeverityLabel($severity)
    {
        $labels = [
            'low' => 'کم',
            'medium' => 'متوسط',
            'high' => 'زیاد',
        ];

        return $labels[$severity] ?? $severity;
    }

    /**
     * Get status label.
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'در انتظار',
            'approved' => 'تأیید شده',
            'rejected' => 'رد شده',
        ];

        return $labels[$status] ?? $status;
    }

    // API Methods
    public function apiIndex(Request $request)
    {
        $query = ContentModeration::with(['user', 'story', 'episode', 'moderator']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
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

        // Filter by content type
        if ($request->filled('content_type')) {
            $query->where('content_type', $request->content_type);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by moderator
        if ($request->filled('moderator_id')) {
            $query->where('moderator_id', $request->moderator_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $moderations = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $moderations->items(),
            'pagination' => [
                'current_page' => $moderations->currentPage(),
                'last_page' => $moderations->lastPage(),
                'per_page' => $moderations->perPage(),
                'total' => $moderations->total(),
            ]
        ]);
    }

    public function apiStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'content_type' => 'required|in:story,episode,comment,review,user_profile',
            'content_id' => 'required|integer',
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'reason' => 'required|string|max:500',
            'severity' => 'required|in:low,medium,high',
            'notes' => 'nullable|string|max:1000',
            'evidence_files' => 'nullable|array',
            'evidence_files.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $moderation = ContentModeration::create([
                'user_id' => $request->user_id,
                'content_type' => $request->content_type,
                'content_id' => $request->content_id,
                'story_id' => $request->story_id,
                'episode_id' => $request->episode_id,
                'reason' => $request->reason,
                'severity' => $request->severity,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Handle evidence files upload
            if ($request->hasFile('evidence_files')) {
                $files = [];
                foreach ($request->file('evidence_files') as $file) {
                    $path = $file->store('moderation/evidence', 'public');
                    $files[] = $path;
                }
                $moderation->update(['evidence_files' => json_encode($files)]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'گزارش نظارت محتوا با موفقیت ایجاد شد.',
                'data' => $moderation->load(['user', 'story', 'episode', 'moderator'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating content moderation: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد گزارش نظارت محتوا: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiShow(ContentModeration $contentModeration)
    {
        $contentModeration->load(['user', 'story', 'episode', 'moderator']);

        return response()->json([
            'success' => true,
            'data' => $contentModeration
        ]);
    }

    public function apiUpdate(Request $request, ContentModeration $contentModeration)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'content_type' => 'required|in:story,episode,comment,review,user_profile',
            'content_id' => 'required|integer',
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'reason' => 'required|string|max:500',
            'severity' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,approved,rejected',
            'notes' => 'nullable|string|max:1000',
            'moderator_id' => 'nullable|exists:users,id',
            'evidence_files' => 'nullable|array',
            'evidence_files.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'user_id' => $request->user_id,
                'content_type' => $request->content_type,
                'content_id' => $request->content_id,
                'story_id' => $request->story_id,
                'episode_id' => $request->episode_id,
                'reason' => $request->reason,
                'severity' => $request->severity,
                'status' => $request->status,
                'notes' => $request->notes,
            ];

            // Update moderator if status is being changed
            if ($request->status !== 'pending' && !$contentModeration->moderator_id) {
                $updateData['moderator_id'] = auth()->id();
                $updateData['moderated_at'] = now();
            }

            $contentModeration->update($updateData);

            // Handle evidence files upload
            if ($request->hasFile('evidence_files')) {
                // Delete old evidence files
                if ($contentModeration->evidence_files) {
                    $oldFiles = json_decode($contentModeration->evidence_files, true);
                    foreach ($oldFiles as $file) {
                        Storage::disk('public')->delete($file);
                    }
                }

                $files = [];
                foreach ($request->file('evidence_files') as $file) {
                    $path = $file->store('moderation/evidence', 'public');
                    $files[] = $path;
                }
                $contentModeration->update(['evidence_files' => json_encode($files)]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'گزارش نظارت محتوا با موفقیت به‌روزرسانی شد.',
                'data' => $contentModeration->load(['user', 'story', 'episode', 'moderator'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating content moderation: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی گزارش نظارت محتوا: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDestroy(ContentModeration $contentModeration)
    {
        try {
            DB::beginTransaction();

            // Delete associated evidence files
            if ($contentModeration->evidence_files) {
                $files = json_decode($contentModeration->evidence_files, true);
                foreach ($files as $file) {
                    Storage::disk('public')->delete($file);
                }
            }

            $contentModeration->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'گزارش نظارت محتوا با موفقیت حذف شد.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting content moderation: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف گزارش نظارت محتوا: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:approve,reject,delete',
            'moderation_ids' => 'required|array|min:1',
            'moderation_ids.*' => 'integer|exists:content_moderations,id',
        ]);

        try {
            DB::beginTransaction();

            $moderationIds = $request->moderation_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($moderationIds as $moderationId) {
                try {
                    $moderation = ContentModeration::findOrFail($moderationId);

                    switch ($action) {
                        case 'approve':
                            $moderation->update([
                                'status' => 'approved',
                                'moderator_id' => auth()->id(),
                                'moderated_at' => now(),
                            ]);
                            break;

                        case 'reject':
                            $moderation->update([
                                'status' => 'rejected',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
                            ]);
                            break;

                        case 'delete':
                            // Delete associated evidence files
                            if ($moderation->evidence_files) {
                                $files = json_decode($moderation->evidence_files, true);
                                foreach ($files as $file) {
                                    Storage::disk('public')->delete($file);
                                }
                            }
                            $moderation->delete();
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for content moderation', [
                        'moderation_id' => $moderationId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $actionLabels = [
                'approve' => 'تأیید',
                'reject' => 'رد',
                'delete' => 'حذف',
            ];

            $message = "عملیات {$actionLabels[$action]} روی {$successCount} گزارش نظارت انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} گزارش ناموفق بود";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'moderation_ids' => $request->moderation_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در انجام عملیات گروهی: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiStatistics()
    {
        $stats = [
            'total_moderations' => ContentModeration::count(),
            'pending_moderations' => ContentModeration::where('status', 'pending')->count(),
            'approved_moderations' => ContentModeration::where('status', 'approved')->count(),
            'rejected_moderations' => ContentModeration::where('status', 'rejected')->count(),
            'moderations_by_content_type' => ContentModeration::selectRaw('content_type, COUNT(*) as count')
                ->groupBy('content_type')
                ->get(),
            'moderations_by_severity' => ContentModeration::selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->get(),
            'moderations_by_status' => ContentModeration::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'top_reporters' => ContentModeration::selectRaw('user_id, COUNT(*) as report_count')
                ->with('user')
                ->groupBy('user_id')
                ->orderBy('report_count', 'desc')
                ->limit(10)
                ->get(),
            'moderations_by_month' => ContentModeration::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
            'recent_moderations' => ContentModeration::with(['user', 'story', 'episode', 'moderator'])
                ->latest()
                ->limit(10)
                ->get(),
            'moderations_today' => ContentModeration::whereDate('created_at', today())->count(),
            'moderations_this_week' => ContentModeration::where('created_at', '>=', now()->startOfWeek())->count(),
            'moderations_this_month' => ContentModeration::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}