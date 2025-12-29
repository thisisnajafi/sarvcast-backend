<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Favorite;
use App\Models\Rating;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoryController extends Controller
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Transform stories for API response
     */
    private function transformStories($stories)
    {
        foreach ($stories as $story) {
            // Duration is already in seconds in database, no conversion needed
            // The API response should match the Flutter documentation format
        }
        return $stories;
    }
    /**
     * Get paginated list of stories
     */
    public function index(Request $request)
    {
        $query = Story::with(['category', 'director', 'narrator'])
            ->published();

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
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

        // Apply sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->orderBy('play_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            default:
                $query->latest();
        }

        $stories = $query->paginate($request->get('per_page', 20));

        // Transform stories to match API specification
        $transformedStories = $this->transformStories($stories->items());

        return response()->json([
            'success' => true,
            'data' => $transformedStories
        ]);
    }

    /**
     * Get detailed story information
     */
    public function show(Request $request, Story $story)
    {
        $story->load(['category', 'director', 'writer', 'author', 'narrator', 'episodes', 'people', 'characters.voiceActor']);

        // Check access control
        $user = $request->user();
        $accessInfo = $this->accessControlService->canAccessStory($user ? $user->id : 0, $story->id);

        // Check if user has favorited this story
        $isFavorite = false;
        if ($user) {
            $isFavorite = Favorite::where('user_id', $user->id)
                ->where('story_id', $story->id)
                ->exists();
        }

        // Get user's rating if authenticated
        $userRating = null;
        if ($user) {
            $rating = Rating::where('user_id', $user->id)
                ->where('story_id', $story->id)
                ->first();
            $userRating = $rating ? $rating->rating : null;
        }

        // Filter episodes based on access
        $accessibleEpisodes = [];
        if ($accessInfo['has_access']) {
            foreach ($story->episodes as $episode) {
                $episodeAccess = $this->accessControlService->canAccessEpisode($user ? $user->id : 0, $episode->id);
                if ($episodeAccess['has_access']) {
                    $accessibleEpisodes[] = $episode;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'story' => $story,
                'access_info' => $accessInfo,
                'accessible_episodes' => $accessibleEpisodes,
                'is_favorite' => $isFavorite,
                'user_rating' => $userRating
            ]
        ]);
    }

    /**
     * Get episodes for a specific story
     */
    public function episodes(Story $story, Request $request)
    {
        $user = Auth::user();
        $includeDraft = $request->boolean('include_draft', false);

        // For admins/super admins, include all episodes (draft, pending, published, etc.)
        // For regular users, only show published episodes (unless include_draft is explicitly requested)
        if ($user && ($user->isAdmin() || $user->isSuperAdmin())) {
            $query = $story->episodes();
        } elseif ($includeDraft) {
            // Allow including draft episodes if explicitly requested (for testing)
            $query = $story->episodes();
        } else {
            $query = $story->episodes()->published();
        }

        // Filter by premium status if user doesn't have active subscription
        if ($user && !$user->hasActiveSubscription()) {
            $query->where('is_premium', false);
        }

        $episodes = $query->with(['narrator', 'people', 'imageTimelines.voiceActor.person'])->orderBy('episode_number')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'episodes' => $episodes
            ]
        ]);
    }

    /**
     * Add story to favorites
     */
    public function addFavorite(Story $story)
    {
        $user = Auth::user();

        // Check if already favorited
        if (Favorite::where('user_id', $user->id)->where('story_id', $story->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Story is already in favorites'
            ], 400);
        }

        Favorite::create([
            'user_id' => $user->id,
            'story_id' => $story->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Story added to favorites'
        ]);
    }

    /**
     * Remove story from favorites
     */
    public function removeFavorite(Story $story)
    {
        $user = Auth::user();

        Favorite::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Story removed from favorites'
        ]);
    }

    /**
     * Get featured stories for the home page
     */
    public function featured(Request $request)
    {
        $limit = $request->get('limit', 10);

        $stories = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
            ->published()
            ->where('is_featured', true)
            ->orderBy('featured_order', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Featured stories retrieved successfully',
            'data' => $this->transformStories($stories)
        ]);
    }

    /**
     * Get popular stories based on play count and ratings
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);
        $period = $request->get('period', 'all'); // daily, weekly, monthly, all

        $query = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
            ->published();

        // Apply time period filter
        if ($period !== 'all') {
            $days = match($period) {
                'daily' => 1,
                'weekly' => 7,
                'monthly' => 30,
                default => 1
            };
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $stories = $query->orderBy('play_count', 'desc')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Popular stories retrieved successfully',
            'data' => $this->transformStories($stories)
        ]);
    }

    /**
     * Get recently added stories
     */
    public function recent(Request $request)
    {
        $limit = $request->get('limit', 10);

        $stories = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
            ->published()
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Recent stories retrieved successfully',
            'data' => $this->transformStories($stories)
        ]);
    }

    /**
     * Get personalized story recommendations for the user
     */
    public function recommendations(Request $request)
    {
        $limit = $request->get('limit', 10);
        $user = $request->user();

        if (!$user) {
            // Return popular stories for non-authenticated users
            $stories = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
                ->published()
                ->orderBy('play_count', 'desc')
                ->limit($limit)
                ->get();
        } else {
            // Get personalized recommendations based on user preferences
            $stories = Story::with(['category', 'narrator', 'author', 'director', 'writer', 'people'])
                ->published()
                ->whereNotIn('id', function($query) use ($user) {
                    $query->select('story_id')
                        ->from('play_histories')
                        ->where('user_id', $user->id);
                })
                ->orderBy('rating', 'desc')
                ->limit($limit)
                ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Story recommendations retrieved successfully',
            'data' => $this->transformStories($stories)
        ]);
    }

    /**
     * Assign narrator to a story (admin & super admin)
     *
     * @param Request $request
     * @param Story $story
     * @return JsonResponse
     */
    public function assignNarrator(Request $request, Story $story)
    {
        $request->validate([
            'narrator_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::find($value);
                    if ($user && !in_array($user->role, [
                        \App\Models\User::ROLE_VOICE_ACTOR,
                        \App\Models\User::ROLE_ADMIN,
                        \App\Models\User::ROLE_SUPER_ADMIN
                    ])) {
                        $fail('کاربر انتخاب شده باید نقش صداپیشه، ادمین یا ادمین کل داشته باشد.');
                    }
                },
            ],
        ], [
            'narrator_id.required' => 'شناسه راوی الزامی است',
            'narrator_id.exists' => 'کاربر انتخاب شده معتبر نیست',
        ]);

        $story->update(['narrator_id' => $request->narrator_id]);
        $story->load('narrator');

        return response()->json([
            'success' => true,
            'message' => 'راوی با موفقیت به داستان اختصاص داده شد.',
            'data' => $story
        ]);
    }

    /**
     * Assign author to a story (admin & super admin)
     *
     * @param Request $request
     * @param Story $story
     * @return JsonResponse
     */
    public function assignAuthor(Request $request, Story $story)
    {
        $request->validate([
            'author_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ], [
            'author_id.required' => 'شناسه نویسنده الزامی است',
            'author_id.exists' => 'کاربر انتخاب شده معتبر نیست',
        ]);

        $story->update(['author_id' => $request->author_id]);
        $story->load('author');

        return response()->json([
            'success' => true,
            'message' => 'نویسنده با موفقیت به داستان اختصاص داده شد.',
            'data' => $story
        ]);
    }

    /**
     * Update workflow status of a story (admin & super admin)
     *
     * @param Request $request
     * @param Story $story
     * @return JsonResponse
     */
    public function updateWorkflowStatus(Request $request, Story $story)
    {
        $request->validate([
            'workflow_status' => [
                'required',
                'in:written,characters_made,recorded,timeline_created,published',
            ],
        ], [
            'workflow_status.required' => 'وضعیت گردش کار الزامی است',
            'workflow_status.in' => 'وضعیت گردش کار معتبر نیست',
        ]);

        $newStatus = $request->workflow_status;
        $success = $story->transitionWorkflowStatus($newStatus);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'انتقال به این وضعیت مجاز نیست. وضعیت فعلی: ' . ($story->workflow_status ?? 'written'),
            ], 400);
        }

        $story->refresh();

        return response()->json([
            'success' => true,
            'message' => 'وضعیت گردش کار با موفقیت به‌روزرسانی شد.',
            'data' => [
                'story' => $story,
                'workflow_status_label' => $story->workflow_status_label,
            ]
        ]);
    }

    /**
     * Upload script file for a story (admin & super admin)
     *
     * @param Request $request
     * @param Story $story
     * @return JsonResponse
     */
    public function uploadScript(Request $request, Story $story)
    {
        $request->validate([
            'script' => [
                'required',
                'file',
                'mimes:md,txt',
                'max:5120', // 5MB max
            ],
        ], [
            'script.required' => 'فایل اسکریپت الزامی است',
            'script.file' => 'فایل باید یک فایل معتبر باشد',
            'script.mimes' => 'فرمت فایل باید .md یا .txt باشد',
            'script.max' => 'حجم فایل نمی‌تواند بیش از 5 مگابایت باشد',
        ]);

        try {
            $file = $request->file('script');
            $filename = time() . '_' . $story->id . '_' . Str::slug($story->title) . '.' . $file->getClientOriginalExtension();
            $directory = 'stories/scripts';

            $path = $file->storeAs($directory, $filename, 'public');
            $url = Storage::url($path);

            $story->update(['script_file_url' => $url]);

            return response()->json([
                'success' => true,
                'message' => 'فایل اسکریپت با موفقیت آپلود شد.',
                'data' => [
                    'script_file_url' => $url,
                    'filename' => $filename,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Script upload failed', [
                'error' => $e->getMessage(),
                'story_id' => $story->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود فایل اسکریپت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get story script content
     *
     * @param Story $story
     * @return JsonResponse
     */
    /**
     * Create a new story (admin & super admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'description' => 'required|string|max:5000',
            'category_id' => 'required|integer|exists:categories,id',
            'age_group' => 'required|string|max:20',
            'duration' => 'nullable|integer|min:0',
            'total_episodes' => 'nullable|integer|min:0',
            'free_episodes' => 'nullable|integer|min:0',
            'is_premium' => 'nullable|boolean',
            'is_completely_free' => 'nullable|boolean',
            'status' => 'required|in:draft,pending,approved,rejected,published',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'title.required' => 'عنوان داستان الزامی است',
            'title.max' => 'عنوان داستان نمی‌تواند بیشتر از 200 کاراکتر باشد',
            'subtitle.max' => 'زیرعنوان نمی‌تواند بیشتر از 300 کاراکتر باشد',
            'description.required' => 'توضیحات داستان الزامی است',
            'description.max' => 'توضیحات نمی‌تواند بیشتر از 5000 کاراکتر باشد',
            'category_id.required' => 'انتخاب دسته‌بندی الزامی است',
            'category_id.exists' => 'دسته‌بندی انتخاب شده معتبر نیست',
            'age_group.required' => 'گروه سنی الزامی است',
            'status.required' => 'وضعیت الزامی است',
            'status.in' => 'وضعیت نامعتبر است',
            'image.required' => 'تصویر داستان الزامی است',
            'image.image' => 'فایل باید یک تصویر معتبر باشد',
            'image.mimes' => 'فرمت تصویر باید jpeg، png، jpg یا webp باشد',
            'image.max' => 'حجم تصویر نمی‌تواند بیشتر از 5 مگابایت باشد',
        ]);

        try {
            $validated = $request->only([
                'title', 'subtitle', 'description', 'category_id', 'age_group',
                'duration', 'total_episodes', 'free_episodes', 'is_premium',
                'is_completely_free', 'status', 'tags'
            ]);

            // Set default language (all stories are in Persian)
            $validated['language'] = 'persian';

            // Set default workflow status
            $validated['workflow_status'] = 'written';

            // Ensure duration has a default value if not provided
            if (!isset($validated['duration']) || empty($validated['duration'])) {
                $validated['duration'] = 0;
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . Str::slug($request->title) . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                // Ensure directory exists
                $directory = public_path('images/stories');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                $image->move($directory, $imageName);
                $validated['image_url'] = 'stories/' . $imageName;
            }

            // Handle cover image upload
            if ($request->hasFile('cover_image')) {
                $coverImage = $request->file('cover_image');
                $coverImageName = time() . '_cover_' . Str::slug($request->title) . '_' . uniqid() . '.' . $coverImage->getClientOriginalExtension();

                $coverImage->move($directory, $coverImageName);
                $validated['cover_image_url'] = 'stories/' . $coverImageName;
            }

            $story = Story::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'داستان با موفقیت ایجاد شد.',
                'data' => $story->load(['category'])
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Story creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد داستان: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getScript(Story $story)
    {
        if (!$story->script_file_url) {
            return response()->json([
                'success' => false,
                'message' => 'فایل اسکریپت برای این داستان موجود نیست.'
            ], 404);
        }

        try {
            // Extract path from URL
            $path = str_replace('/storage/', '', parse_url($story->script_file_url, PHP_URL_PATH));

            if (!Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'فایل اسکریپت یافت نشد.'
                ], 404);
            }

            $content = Storage::disk('public')->get($path);

            return response()->json([
                'success' => true,
                'message' => 'محتوای اسکریپت با موفقیت دریافت شد.',
                'data' => [
                    'script_content' => $content,
                    'script_file_url' => $story->script_file_url,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Script retrieval failed', [
                'error' => $e->getMessage(),
                'story_id' => $story->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فایل اسکریپت: ' . $e->getMessage()
            ], 500);
        }
    }
}
