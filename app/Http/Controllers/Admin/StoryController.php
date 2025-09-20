<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Category;
use App\Models\Person;
use App\Models\Episode;
use App\Services\InAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StoryController extends Controller
{
    protected $notificationService;

    public function __construct(InAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Story::with(['category', 'director', 'narrator', 'episodes']);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        if ($request->filled('is_completely_free')) {
            $query->where('is_completely_free', $request->boolean('is_completely_free'));
        }

        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        if ($request->filled('director_id')) {
            $query->where('director_id', $request->director_id);
        }

        if ($request->filled('narrator_id')) {
            $query->where('narrator_id', $request->narrator_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('min_duration')) {
            $query->where('duration', '>=', $request->min_duration);
        }

        if ($request->filled('max_duration')) {
            $query->where('duration', '<=', $request->max_duration);
        }

        if ($request->filled('min_episodes')) {
            $query->where('total_episodes', '>=', $request->min_episodes);
        }

        if ($request->filled('max_episodes')) {
            $query->where('total_episodes', '<=', $request->max_episodes);
        }

        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        if ($request->filled('min_play_count')) {
            $query->where('play_count', '>=', $request->min_play_count);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('subtitle', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('tags', 'like', '%' . $request->search . '%');
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        switch ($sort) {
            case 'title':
                $query->orderBy('title', $direction);
                break;
            case 'status':
                $query->orderBy('status', $direction);
                break;
            case 'rating':
                $query->orderBy('rating', $direction);
                break;
            case 'play_count':
                $query->orderBy('play_count', $direction);
                break;
            case 'duration':
                $query->orderBy('duration', $direction);
                break;
            case 'total_episodes':
                $query->orderBy('total_episodes', $direction);
                break;
            case 'published_at':
                $query->orderBy('published_at', $direction);
                break;
            default:
                $query->orderBy('created_at', $direction);
        }

        $perPage = $request->get('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page

        $stories = $query->paginate($perPage);
        
        // Get filter options
        $categories = Category::where('is_active', true)->get();
        $directors = Person::whereJsonContains('roles', 'director')->get();
        $narrators = Person::whereJsonContains('roles', 'narrator')->get();
        
        // Get statistics
        $stats = [
            'total' => Story::count(),
            'published' => Story::where('status', 'published')->count(),
            'draft' => Story::where('status', 'draft')->count(),
            'pending' => Story::where('status', 'pending')->count(),
            'premium' => Story::where('is_premium', true)->count(),
            'free' => Story::where('is_completely_free', true)->count(),
            'total_episodes' => Story::sum('total_episodes'),
            'total_duration' => Story::sum('duration'),
            'avg_rating' => Story::avg('rating'),
            'total_plays' => Story::sum('play_count')
        ];

        return view('admin.stories.index', compact('stories', 'categories', 'directors', 'narrators', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $people = Person::all();
        
        return view('admin.stories.create', compact('categories', 'people'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Convert comma-separated tags string to array
        if ($request->has('tags') && is_string($request->tags)) {
            // Handle both Persian (،) and English (,) commas
            $tagsString = str_replace('،', ',', $request->tags);
            $request->merge([
                'tags' => array_filter(array_map('trim', explode(',', $tagsString)))
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'age_group' => 'required|string',
            'director_id' => 'nullable|exists:people,id',
            'writer_id' => 'nullable|exists:people,id',
            'author_id' => 'nullable|exists:people,id',
            'narrator_id' => 'nullable|exists:people,id',
            'is_premium' => 'boolean',
            'is_completely_free' => 'boolean',
            'status' => 'required|in:draft,pending,approved,rejected,published',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        // Set default language (all stories are in Persian)
        $validated['language'] = 'persian';

        // Handle file uploads
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('stories', 'public');
            $validated['image_url'] = asset('storage/' . $imagePath);
        }
        
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('stories', 'public');
            $validated['cover_image_url'] = asset('storage/' . $coverImagePath);
        }

        $story = Story::create($validated);

        return redirect()->route('admin.stories.index')
            ->with('success', 'داستان با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Story $story)
    {
        $story->load(['category', 'director', 'writer', 'author', 'narrator', 'episodes.narrator', 'people']);
        
        return view('admin.stories.show', compact('story'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Story $story)
    {
        $categories = Category::where('is_active', true)->get();
        $people = Person::all();
        
        return view('admin.stories.edit', compact('story', 'categories', 'people'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Story $story)
    {
        // Convert comma-separated tags string to array
        if ($request->has('tags') && is_string($request->tags)) {
            // Handle both Persian (،) and English (,) commas
            $tagsString = str_replace('،', ',', $request->tags);
            $request->merge([
                'tags' => array_filter(array_map('trim', explode(',', $tagsString)))
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'age_group' => 'required|string',
            'director_id' => 'nullable|exists:people,id',
            'writer_id' => 'nullable|exists:people,id',
            'author_id' => 'nullable|exists:people,id',
            'narrator_id' => 'nullable|exists:people,id',
            'is_premium' => 'boolean',
            'is_completely_free' => 'boolean',
            'status' => 'required|in:draft,pending,approved,rejected,published',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        // Set default language (all stories are in Persian)
        $validated['language'] = 'persian';

        // Handle file uploads
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('stories', 'public');
            $validated['image_url'] = asset('storage/' . $imagePath);
        }
        
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('stories', 'public');
            $validated['cover_image_url'] = asset('storage/' . $coverImagePath);
        }

        $story->update($validated);

        return redirect()->route('admin.stories.index')
            ->with('success', 'داستان با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Story $story)
    {
        try {
            DB::beginTransaction();

            // Delete associated episodes
            $story->episodes()->delete();
            
            // Delete the story
            $story->delete();

            DB::commit();

            return redirect()->route('admin.stories.index')
                ->with('success', 'داستان با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete story', [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.stories.index')
                ->with('error', 'خطا در حذف داستان: ' . $e->getMessage());
        }
    }

    /**
     * Bulk operations on stories
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:publish,unpublish,delete,change_status,change_category',
            'story_ids' => 'required|array|min:1',
            'story_ids.*' => 'integer|exists:stories,id',
            'status' => 'required_if:action,change_status|string|in:draft,pending,approved,rejected,published',
            'category_id' => 'required_if:action,change_category|integer|exists:categories,id'
        ], [
            'action.required' => 'عملیات الزامی است',
            'action.in' => 'عملیات نامعتبر است',
            'story_ids.required' => 'انتخاب حداقل یک داستان الزامی است',
            'story_ids.array' => 'شناسه‌های داستان باید آرایه باشند',
            'story_ids.min' => 'حداقل یک داستان باید انتخاب شود',
            'story_ids.*.exists' => 'یکی از داستان‌ها یافت نشد',
            'status.required_if' => 'وضعیت الزامی است',
            'status.in' => 'وضعیت نامعتبر است',
            'category_id.required_if' => 'دسته‌بندی الزامی است',
            'category_id.exists' => 'دسته‌بندی یافت نشد'
        ]);

        try {
            DB::beginTransaction();

            $storyIds = $request->story_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($storyIds as $storyId) {
                try {
                    $story = Story::findOrFail($storyId);

                    switch ($action) {
                        case 'publish':
                            $story->update([
                                'status' => 'published',
                                'published_at' => now()
                            ]);
                            break;

                        case 'unpublish':
                            $story->update([
                                'status' => 'draft',
                                'published_at' => null
                            ]);
                            break;

                        case 'delete':
                            $story->episodes()->delete();
                            $story->delete();
                            break;

                        case 'change_status':
                            $story->update(['status' => $request->status]);
                            break;

                        case 'change_category':
                            $story->update(['category_id' => $request->category_id]);
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for story', [
                        'story_id' => $storyId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $message = "عملیات {$action} روی {$successCount} داستان انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} داستان ناموفق بود";
            }

            return redirect()->route('admin.stories.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'story_ids' => $request->story_ids,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.stories.index')
                ->with('error', 'خطا در انجام عملیات گروهی: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate a story
     */
    public function duplicate(Story $story)
    {
        try {
            DB::beginTransaction();

            $newStory = $story->replicate();
            $newStory->title = $story->title . ' (کپی)';
            $newStory->status = 'draft';
            $newStory->published_at = null;
            $newStory->play_count = 0;
            $newStory->rating = 0;
            $newStory->save();

            // Duplicate episodes
            foreach ($story->episodes as $episode) {
                $newEpisode = $episode->replicate();
                $newEpisode->story_id = $newStory->id;
                $newEpisode->status = 'draft';
                $newEpisode->published_at = null;
                $newEpisode->play_count = 0;
                $newEpisode->rating = 0;
                $newEpisode->save();
            }

            DB::commit();

            return redirect()->route('admin.stories.edit', $newStory)
                ->with('success', 'داستان با موفقیت کپی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate story', [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.stories.index')
                ->with('error', 'خطا در کپی کردن داستان: ' . $e->getMessage());
        }
    }

    /**
     * Export stories
     */
    public function export(Request $request)
    {
        $query = Story::with(['category', 'director', 'narrator']);

        // Apply same filters as index
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_premium')) {
            $query->where('is_premium', $request->boolean('is_premium'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('subtitle', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $stories = $query->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'عنوان', 'زیرعنوان', 'توضیحات', 'دسته‌بندی', 'گروه سنی', 'زبان',
            'مدت زمان', 'تعداد قسمت‌ها', 'قسمت‌های رایگان', 'کارگردان', 'راوی',
            'نویسنده', 'نویسنده اصلی', 'وضعیت', 'پریمیوم', 'کاملاً رایگان',
            'امتیاز', 'تعداد پخش', 'تگ‌ها', 'تاریخ ایجاد', 'تاریخ انتشار'
        ];

        foreach ($stories as $story) {
            $csvData[] = [
                $story->id,
                $story->title,
                $story->subtitle,
                $story->description,
                $story->category->name ?? '',
                $story->age_group,
                $story->language,
                $story->duration,
                $story->total_episodes,
                $story->free_episodes,
                $story->director->name ?? '',
                $story->narrator->name ?? '',
                $story->writer->name ?? '',
                $story->author->name ?? '',
                $story->status,
                $story->is_premium ? 'بله' : 'خیر',
                $story->is_completely_free ? 'بله' : 'خیر',
                $story->rating,
                $story->play_count,
                is_array($story->tags) ? implode(', ', $story->tags) : $story->tags,
                $story->created_at->format('Y-m-d H:i:s'),
                $story->published_at ? $story->published_at->format('Y-m-d H:i:s') : ''
            ];
        }

        $filename = 'stories_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get story statistics
     */
    public function statistics()
    {
        $stats = [
            'total_stories' => Story::count(),
            'published_stories' => Story::where('status', 'published')->count(),
            'draft_stories' => Story::where('status', 'draft')->count(),
            'pending_stories' => Story::where('status', 'pending')->count(),
            'premium_stories' => Story::where('is_premium', true)->count(),
            'free_stories' => Story::where('is_completely_free', true)->count(),
            'total_episodes' => Story::sum('total_episodes'),
            'total_duration' => Story::sum('duration'),
            'avg_rating' => round(Story::avg('rating'), 2),
            'total_plays' => Story::sum('play_count'),
            'stories_by_category' => Story::selectRaw('categories.name, COUNT(*) as count')
                ->join('categories', 'stories.category_id', '=', 'categories.id')
                ->groupBy('categories.id', 'categories.name')
                ->get(),
            'stories_by_status' => Story::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'stories_by_age_group' => Story::selectRaw('age_group, COUNT(*) as count')
                ->groupBy('age_group')
                ->get(),
            'recent_stories' => Story::with(['category', 'director'])
                ->latest()
                ->limit(10)
                ->get(),
            'top_rated_stories' => Story::with(['category', 'director'])
                ->orderBy('rating', 'desc')
                ->limit(10)
                ->get(),
            'most_played_stories' => Story::with(['category', 'director'])
                ->orderBy('play_count', 'desc')
                ->limit(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Publish story and notify users
     */
    public function publish(Story $story)
    {
        try {
            DB::beginTransaction();

            $story->update([
                'status' => 'published',
                'published_at' => now()
            ]);

            // Send notification to all users about new story
            $this->notificationService->createNewStoryNotification(
                0, // Will be sent to all users
                $story->title
            );

            DB::commit();

            return redirect()->route('admin.stories.index')
                ->with('success', 'داستان منتشر شد و کاربران مطلع شدند.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to publish story', [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.stories.index')
                ->with('error', 'خطا در انتشار داستان: ' . $e->getMessage());
        }
    }
}
