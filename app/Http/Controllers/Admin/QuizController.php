<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizQuestion;
use App\Models\Story;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
    /**
     * Display a listing of quiz questions
     */
    public function index(Request $request): View
    {
        $query = QuizQuestion::with(['story', 'episode']);

        // Apply filters
        if ($request->filled('question_type')) {
            $query->where('question_type', $request->question_type);
        }

        if ($request->filled('difficulty_level')) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('story_id')) {
            $query->where('story_id', $request->story_id);
        }

        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->episode_id);
        }

        if ($request->filled('search')) {
            $query->where('question_text', 'like', '%' . $request->search . '%')
                  ->orWhere('explanation', 'like', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        switch ($sortBy) {
            case 'question':
                $query->orderBy('question_text', $sortDirection);
                break;
            case 'type':
                $query->orderBy('question_type', $sortDirection);
                break;
            case 'difficulty':
                $query->orderBy('difficulty_level', $sortDirection);
                break;
            case 'story':
                $query->join('stories', 'quiz_questions.story_id', '=', 'stories.id')
                      ->orderBy('stories.title', $sortDirection);
                break;
            case 'episode':
                $query->join('episodes', 'quiz_questions.episode_id', '=', 'episodes.id')
                      ->orderBy('episodes.title', $sortDirection);
                break;
            default:
                $query->orderBy('created_at', $sortDirection);
        }

        $perPage = $request->get('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page

        $questions = $query->paginate($perPage);
        
        // Get statistics
        $stats = [
            'total' => QuizQuestion::count(),
            'active' => QuizQuestion::where('is_active', true)->count(),
            'inactive' => QuizQuestion::where('is_active', false)->count(),
            'multiple_choice' => QuizQuestion::where('question_type', 'multiple_choice')->count(),
            'true_false' => QuizQuestion::where('question_type', 'true_false')->count(),
            'fill_blank' => QuizQuestion::where('question_type', 'fill_blank')->count(),
            'matching' => QuizQuestion::where('question_type', 'matching')->count(),
            'easy' => QuizQuestion::where('difficulty_level', 'easy')->count(),
            'medium' => QuizQuestion::where('difficulty_level', 'medium')->count(),
            'hard' => QuizQuestion::where('difficulty_level', 'hard')->count(),
        ];

        // Get stories and episodes for filters
        $stories = Story::select('id', 'title')->get();
        $episodes = Episode::select('id', 'title')->get();

        return view('admin.quiz.index', compact('questions', 'stats', 'stories', 'episodes'));
    }

    /**
     * Show the form for creating a new quiz question
     */
    public function create(): View
    {
        $stories = Story::select('id', 'title')->get();
        $episodes = Episode::select('id', 'title')->get();
        return view('admin.quiz.create', compact('stories', 'episodes'));
    }

    /**
     * Store a newly created quiz question
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|string|in:multiple_choice,true_false,fill_blank,matching,essay',
            'difficulty_level' => 'required|string|in:easy,medium,hard',
            'points' => 'required|integer|min:1|max:100',
            'time_limit' => 'nullable|integer|min:10|max:3600',
            'explanation' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'options' => 'required_if:question_type,multiple_choice|array',
            'options.*' => 'string|max:500',
            'correct_answer' => 'required|string|max:500',
            'correct_options' => 'required_if:question_type,multiple_choice|array',
            'correct_options.*' => 'integer|min:0',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        try {
            DB::beginTransaction();

            // Handle options for multiple choice questions
            if ($validated['question_type'] === 'multiple_choice') {
                $validated['options'] = json_encode($validated['options']);
                $validated['correct_options'] = json_encode($validated['correct_options']);
            } else {
                $validated['options'] = null;
                $validated['correct_options'] = null;
            }

            // Handle tags
            if (isset($validated['tags'])) {
                $validated['tags'] = json_encode($validated['tags']);
            }

            $question = QuizQuestion::create($validated);

            DB::commit();

            return redirect()->route('admin.quiz.index')
                           ->with('success', 'سؤال کویز با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating quiz question: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در ایجاد سؤال کویز. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Display the specified quiz question
     */
    public function show(QuizQuestion $quiz): View
    {
        $quiz->load(['story', 'episode', 'quizAttempts']);
        return view('admin.quiz.show', compact('quiz'));
    }

    /**
     * Show the form for editing the specified quiz question
     */
    public function edit(QuizQuestion $quiz): View
    {
        $stories = Story::select('id', 'title')->get();
        $episodes = Episode::select('id', 'title')->get();
        return view('admin.quiz.edit', compact('quiz', 'stories', 'episodes'));
    }

    /**
     * Update the specified quiz question
     */
    public function update(Request $request, QuizQuestion $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'story_id' => 'nullable|exists:stories,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|string|in:multiple_choice,true_false,fill_blank,matching,essay',
            'difficulty_level' => 'required|string|in:easy,medium,hard',
            'points' => 'required|integer|min:1|max:100',
            'time_limit' => 'nullable|integer|min:10|max:3600',
            'explanation' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'options' => 'required_if:question_type,multiple_choice|array',
            'options.*' => 'string|max:500',
            'correct_answer' => 'required|string|max:500',
            'correct_options' => 'required_if:question_type,multiple_choice|array',
            'correct_options.*' => 'integer|min:0',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        try {
            DB::beginTransaction();

            // Handle options for multiple choice questions
            if ($validated['question_type'] === 'multiple_choice') {
                $validated['options'] = json_encode($validated['options']);
                $validated['correct_options'] = json_encode($validated['correct_options']);
            } else {
                $validated['options'] = null;
                $validated['correct_options'] = null;
            }

            // Handle tags
            if (isset($validated['tags'])) {
                $validated['tags'] = json_encode($validated['tags']);
            }

            $quiz->update($validated);

            DB::commit();

            return redirect()->route('admin.quiz.index')
                           ->with('success', 'سؤال کویز با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating quiz question: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'خطا در به‌روزرسانی سؤال کویز. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Remove the specified quiz question
     */
    public function destroy(QuizQuestion $quiz): RedirectResponse
    {
        try {
            $quiz->delete();

            return redirect()->route('admin.quiz.index')
                           ->with('success', 'سؤال کویز با موفقیت حذف شد.');

        } catch (\Exception $e) {
            Log::error('Error deleting quiz question: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در حذف سؤال کویز. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Toggle active status of a quiz question
     */
    public function toggle(QuizQuestion $quiz): RedirectResponse
    {
        try {
            $quiz->update(['is_active' => !$quiz->is_active]);

            $status = $quiz->is_active ? 'فعال' : 'غیرفعال';
            return redirect()->back()
                           ->with('success', "سؤال کویز با موفقیت {$status} شد.");

        } catch (\Exception $e) {
            Log::error('Error toggling quiz question: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در تغییر وضعیت سؤال کویز. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Duplicate a quiz question
     */
    public function duplicate(QuizQuestion $quiz): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $newQuestion = $quiz->replicate();
            $newQuestion->question_text = $quiz->question_text . ' (کپی)';
            $newQuestion->is_active = false;
            $newQuestion->save();

            DB::commit();

            return redirect()->route('admin.quiz.edit', $newQuestion)
                           ->with('success', 'سؤال کویز با موفقیت کپی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error duplicating quiz question: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در کپی کردن سؤال کویز. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|string|in:activate,deactivate,delete',
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'exists:quiz_questions,id'
        ]);

        try {
            DB::beginTransaction();

            $questions = QuizQuestion::whereIn('id', $request->question_ids);

            switch ($request->action) {
                case 'activate':
                    $questions->update(['is_active' => true]);
                    $message = 'سؤالات کویز انتخاب شده با موفقیت فعال شدند.';
                    break;

                case 'deactivate':
                    $questions->update(['is_active' => false]);
                    $message = 'سؤالات کویز انتخاب شده با موفقیت غیرفعال شدند.';
                    break;

                case 'delete':
                    $questions->delete();
                    $message = 'سؤالات کویز انتخاب شده با موفقیت حذف شدند.';
                    break;
            }

            DB::commit();

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk action: ' . $e->getMessage());
            
            return redirect()->back()
                           ->with('error', 'خطا در انجام عملیات گروهی. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Export quiz questions
     */
    public function export(Request $request)
    {
        // Implementation for exporting quiz questions
        // This would typically generate a CSV or Excel file
        return response()->json(['message' => 'Export functionality will be implemented']);
    }

    /**
     * Get quiz question statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => QuizQuestion::count(),
            'active' => QuizQuestion::where('is_active', true)->count(),
            'inactive' => QuizQuestion::where('is_active', false)->count(),
            'by_type' => QuizQuestion::selectRaw('question_type, COUNT(*) as count')
                                    ->groupBy('question_type')
                                    ->get(),
            'by_difficulty' => QuizQuestion::selectRaw('difficulty_level, COUNT(*) as count')
                                          ->groupBy('difficulty_level')
                                          ->get(),
            'by_story' => QuizQuestion::join('stories', 'quiz_questions.story_id', '=', 'stories.id')
                                     ->selectRaw('stories.title, COUNT(*) as count')
                                     ->groupBy('stories.id', 'stories.title')
                                     ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get episodes for a specific story
     */
    public function getEpisodes(Request $request)
    {
        $storyId = $request->get('story_id');
        
        if (!$storyId) {
            return response()->json([]);
        }

        $episodes = Episode::where('story_id', $storyId)
                          ->select('id', 'title')
                          ->get();

        return response()->json($episodes);
    }
}
