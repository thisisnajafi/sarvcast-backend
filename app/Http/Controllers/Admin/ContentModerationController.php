<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\ContentModeration;
use App\Models\User;
use App\Models\Story;
use App\Models\Episode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    // --- API helpers ---

    private function resolveContentModerationPerPage(Request $request): int
    {
        $raw = $request->input('perPage', $request->input('per_page', 20));
        return max(1, min(100, is_numeric($raw) ? (int) $raw : 20));
    }

    private function buildContentModerationApiListQuery(Request $request): Builder
    {
        $query = ContentModeration::query()->with(['user', 'story', 'episode', 'moderator']);

        $search = $request->filled('q')
            ? $request->string('q')->toString()
            : ($request->filled('search') ? $request->string('search')->toString() : null);

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('content_moderations.reason', 'like', "%{$search}%")
                  ->orWhere('content_moderations.notes', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('content_moderations.status', $request->string('status')->toString());
        }
        if ($request->filled('content_type')) {
            $query->where('content_moderations.content_type', $request->string('content_type')->toString());
        }
        if ($request->filled('severity')) {
            $query->where('content_moderations.severity', $request->string('severity')->toString());
        }
        if ($request->filled('moderator_id')) {
            $query->where('content_moderations.moderator_id', (int) $request->input('moderator_id'));
        }

        if ($request->filled('date_range')) {
            $now = Carbon::now();
            match ($request->string('date_range')->toString()) {
                'today' => $query->whereDate('content_moderations.created_at', $now->toDateString()),
                'week'  => $query->where('content_moderations.created_at', '>=', $now->copy()->subWeek()),
                'month' => $query->where('content_moderations.created_at', '>=', $now->copy()->subMonth()),
                'year'  => $query->where('content_moderations.created_at', '>=', $now->copy()->subYear()),
                default => null,
            };
        } else {
            if ($request->filled('dateFrom') || $request->filled('date_from')) {
                $from = $request->input('dateFrom', $request->input('date_from'));
                $query->whereDate('content_moderations.created_at', '>=', $from);
            }
            if ($request->filled('dateTo') || $request->filled('date_to')) {
                $to = $request->input('dateTo', $request->input('date_to'));
                $query->whereDate('content_moderations.created_at', '<=', $to);
            }
        }

        return $query;
    }

    private function applyContentModerationListSort(Builder $query, Request $request): void
    {
        $sortBy = $request->input('sortBy', $request->input('sort_by', 'created_at'));
        $sortDir = strtolower((string) $request->input('sortDir', $request->input('sort_direction', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $column = match ($sortBy) {
            'id' => 'content_moderations.id',
            'status' => 'content_moderations.status',
            'severity' => 'content_moderations.severity',
            'content_type' => 'content_moderations.content_type',
            default => 'content_moderations.created_at',
        };

        $query->orderBy($column, $sortDir)->orderBy('content_moderations.id', 'desc');
    }

    // --- API Methods ---

    public function apiIndex(Request $request)
    {
        try {
            $query = $this->buildContentModerationApiListQuery($request);
            $this->applyContentModerationListSort($query, $request);
            $perPage = $this->resolveContentModerationPerPage($request);

            return AdminApiResponse::paginated(
                $query->paginate($perPage)->appends($request->query())
            );
        } catch (\Throwable $e) {
            Log::error('Content moderation apiIndex failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بارگذاری موارد نظارت.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiExport(Request $request)
    {
        try {
            $query = $this->buildContentModerationApiListQuery($request);
            $this->applyContentModerationListSort($query, $request);

            $filename = 'content-moderation-' . now()->format('Y-m-d-His') . '.csv';

            return response()->streamDownload(function () use ($query) {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }
                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($handle, ['id', 'user_id', 'content_type', 'content_id', 'reason', 'severity', 'status', 'moderator_id', 'created_at', 'moderated_at']);

                $query->clone()->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->user_id,
                            $row->content_type,
                            $row->content_id,
                            $row->reason,
                            $row->severity,
                            $row->status,
                            $row->moderator_id,
                            $row->created_at?->toIso8601String(),
                            $row->moderated_at?->toIso8601String(),
                        ]);
                    }
                });

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Throwable $e) {
            Log::error('Content moderation apiExport failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در خروجی CSV.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

            if ($request->hasFile('evidence_files')) {
                $files = [];
                foreach ($request->file('evidence_files') as $file) {
                    $path = $file->store('moderation/evidence', 'public');
                    $files[] = $path;
                }
                $moderation->update(['evidence_files' => json_encode($files)]);
            }

            DB::commit();

            return AdminApiResponse::success(
                $moderation->load(['user', 'story', 'episode', 'moderator']),
                'گزارش نظارت محتوا با موفقیت ایجاد شد.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Content moderation apiStore failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد گزارش نظارت محتوا.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiShow(ContentModeration $contentModeration)
    {
        try {
            return AdminApiResponse::success(
                $contentModeration->load(['user', 'story', 'episode', 'moderator'])
            );
        } catch (\Throwable $e) {
            Log::error('Content moderation apiShow failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بارگذاری گزارش.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiUpdate(Request $request, ContentModeration $contentModeration)
    {
        $validator = Validator::make($request->all(), [
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

            if ($request->status !== 'pending' && !$contentModeration->moderator_id) {
                $updateData['moderator_id'] = auth()->id();
                $updateData['moderated_at'] = now();
            }

            $contentModeration->update($updateData);

            if ($request->hasFile('evidence_files')) {
                if ($contentModeration->evidence_files) {
                    $oldFiles = json_decode($contentModeration->evidence_files, true);
                    if (is_array($oldFiles)) {
                        foreach ($oldFiles as $file) {
                            Storage::disk('public')->delete($file);
                        }
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

            return AdminApiResponse::success(
                $contentModeration->load(['user', 'story', 'episode', 'moderator']),
                'گزارش نظارت محتوا با موفقیت به‌روزرسانی شد.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Content moderation apiUpdate failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی گزارش نظارت محتوا.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiDestroy(ContentModeration $contentModeration)
    {
        try {
            DB::beginTransaction();

            if ($contentModeration->evidence_files) {
                $files = json_decode($contentModeration->evidence_files, true);
                if (is_array($files)) {
                    foreach ($files as $file) {
                        Storage::disk('public')->delete($file);
                    }
                }
            }

            $contentModeration->delete();
            DB::commit();

            return AdminApiResponse::okMessage('گزارش نظارت محتوا با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Content moderation apiDestroy failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف گزارش نظارت محتوا.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function apiBulkAction(Request $request)
    {
        $moderationIds = $request->input('moderation_ids', $request->input('selected_items', []));
        if (!is_array($moderationIds)) {
            $moderationIds = [];
        }

        $validator = Validator::make(
            ['action' => $request->input('action'), 'moderation_ids' => $moderationIds],
            [
                'action' => 'required|string|in:approve,reject,delete',
                'moderation_ids' => 'required|array|min:1',
                'moderation_ids.*' => 'integer|exists:content_moderations,id',
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
                            if ($moderation->evidence_files) {
                                $files = json_decode($moderation->evidence_files, true);
                                if (is_array($files)) {
                                    foreach ($files as $file) {
                                        Storage::disk('public')->delete($file);
                                    }
                                }
                            }
                            $moderation->delete();
                            break;
                    }
                    $successCount++;
                } catch (\Throwable $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for content moderation', [
                        'moderation_id' => $moderationId,
                        'action' => $action,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            $message = "عملیات روی {$successCount} گزارش نظارت انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} گزارش ناموفق بود";
            }

            return AdminApiResponse::okMessage($message);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Content moderation apiBulkAction failed', ['error' => $e->getMessage()]);

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
                'total_moderations' => ContentModeration::count(),
                'pending_moderations' => ContentModeration::where('status', 'pending')->count(),
                'approved_moderations' => ContentModeration::where('status', 'approved')->count(),
                'rejected_moderations' => ContentModeration::where('status', 'rejected')->count(),
                'moderations_by_content_type' => ContentModeration::selectRaw('content_type, COUNT(*) as count')
                    ->groupBy('content_type')->get(),
                'moderations_by_severity' => ContentModeration::selectRaw('severity, COUNT(*) as count')
                    ->groupBy('severity')->get(),
                'moderations_by_status' => ContentModeration::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')->get(),
                'moderations_today' => ContentModeration::whereDate('created_at', today())->count(),
                'moderations_this_week' => ContentModeration::where('created_at', '>=', now()->startOfWeek())->count(),
                'moderations_this_month' => ContentModeration::where('created_at', '>=', now()->startOfMonth())->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Content moderation apiStatistics failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بارگذاری آمار.',
                'error' => 'SERVER_ERROR',
            ], 500);
        }
    }
}