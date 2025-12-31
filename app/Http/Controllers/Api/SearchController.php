<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search stories
     */
    public function searchStories(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'age_group' => 'nullable|string|in:3-5,6-9,10-12,13+',
            'min_duration' => 'nullable|integer|min:0',
            'max_duration' => 'nullable|integer|min:0',
            'is_premium' => 'nullable|boolean',
            'min_rating' => 'nullable|numeric|min:1|max:5',
            'person_id' => 'nullable|integer|exists:people,id',
            'sort_by' => 'nullable|string|in:created_at,title,duration,play_count,rating',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ], [
            'q.max' => 'عبارت جستجو نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'category_id.exists' => 'دسته‌بندی یافت نشد',
            'age_group.in' => 'گروه سنی نامعتبر است',
            'min_duration.min' => 'حداقل مدت زمان نمی‌تواند منفی باشد',
            'max_duration.min' => 'حداکثر مدت زمان نمی‌تواند منفی باشد',
            'min_rating.min' => 'حداقل امتیاز نمی‌تواند کمتر از 1 باشد',
            'min_rating.max' => 'حداقل امتیاز نمی‌تواند بیشتر از 5 باشد',
            'person_id.exists' => 'شخص یافت نشد',
            'sort_by.in' => 'مرتب‌سازی نامعتبر است',
            'sort_order.in' => 'ترتیب مرتب‌سازی نامعتبر است',
            'per_page.min' => 'تعداد در هر صفحه نمی‌تواند کمتر از 1 باشد',
            'per_page.max' => 'تعداد در هر صفحه نمی‌تواند بیشتر از 100 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $params = $request->all();
            $results = $this->searchService->searchStories($params);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'جستجوی داستان‌ها انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Search stories failed', [
                'params' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در جستجوی داستان‌ها'
            ], 500);
        }
    }

    /**
     * Search episodes
     */
    public function searchEpisodes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|max:255',
            'story_id' => 'nullable|integer|exists:stories,id',
            'min_duration' => 'nullable|integer|min:0',
            'max_duration' => 'nullable|integer|min:0',
            'is_premium' => 'nullable|boolean',
            'episode_number' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:title,duration,play_count,episode_number',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ], [
            'q.max' => 'عبارت جستجو نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'story_id.exists' => 'داستان یافت نشد',
            'min_duration.min' => 'حداقل مدت زمان نمی‌تواند منفی باشد',
            'max_duration.min' => 'حداکثر مدت زمان نمی‌تواند منفی باشد',
            'episode_number.min' => 'شماره قسمت نمی‌تواند کمتر از 1 باشد',
            'sort_by.in' => 'مرتب‌سازی نامعتبر است',
            'sort_order.in' => 'ترتیب مرتب‌سازی نامعتبر است',
            'per_page.min' => 'تعداد در هر صفحه نمی‌تواند کمتر از 1 باشد',
            'per_page.max' => 'تعداد در هر صفحه نمی‌تواند بیشتر از 100 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $params = $request->all();
            $results = $this->searchService->searchEpisodes($params);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'جستجوی قسمت‌ها انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Search episodes failed', [
                'params' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در جستجوی قسمت‌ها'
            ], 500);
        }
    }

    /**
     * Search people
     */
    public function searchPeople(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:director,author,narrator,voice_actor',
            'sort_by' => 'nullable|string|in:name,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ], [
            'q.max' => 'عبارت جستجو نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'role.in' => 'نقش نامعتبر است',
            'sort_by.in' => 'مرتب‌سازی نامعتبر است',
            'sort_order.in' => 'ترتیب مرتب‌سازی نامعتبر است',
            'per_page.min' => 'تعداد در هر صفحه نمی‌تواند کمتر از 1 باشد',
            'per_page.max' => 'تعداد در هر صفحه نمی‌تواند بیشتر از 100 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $params = $request->all();
            $results = $this->searchService->searchPeople($params);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'جستجوی افراد انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Search people failed', [
                'params' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در جستجوی افراد'
            ], 500);
        }
    }

    /**
     * Global search across all content types
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:50'
        ], [
            'q.required' => 'عبارت جستجو الزامی است',
            'q.max' => 'عبارت جستجو نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'limit.min' => 'حد تعداد نتایج نمی‌تواند کمتر از 1 باشد',
            'limit.max' => 'حد تعداد نتایج نمی‌تواند بیشتر از 50 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $params = $request->all();
            $results = $this->searchService->globalSearch($params);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'جستجوی جامع انجام شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Global search failed', [
                'params' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در جستجوی جامع'
            ], 500);
        }
    }

    /**
     * Get search suggestions
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:20'
        ], [
            'q.required' => 'عبارت جستجو الزامی است',
            'q.max' => 'عبارت جستجو نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'limit.min' => 'حد تعداد پیشنهادات نمی‌تواند کمتر از 1 باشد',
            'limit.max' => 'حد تعداد پیشنهادات نمی‌تواند بیشتر از 20 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = $request->input('q');
            $limit = $request->input('limit', 10);
            
            $suggestions = $this->searchService->getSearchSuggestions($query, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestions' => $suggestions,
                    'query' => $query,
                    'limit' => $limit
                ],
                'message' => 'پیشنهادات جستجو دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Get search suggestions failed', [
                'query' => $request->input('q'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت پیشنهادات جستجو'
            ], 500);
        }
    }

    /**
     * Get trending searches
     */
    public function getTrending(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:20'
        ], [
            'limit.min' => 'حد تعداد ترندها نمی‌تواند کمتر از 1 باشد',
            'limit.max' => 'حد تعداد ترندها نمی‌تواند بیشتر از 20 باشد'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $limit = $request->input('limit', 10);
            $trending = $this->searchService->getTrendingSearches($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'trending_searches' => $trending,
                    'limit' => $limit
                ],
                'message' => 'جستجوهای ترند دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Get trending searches failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت جستجوهای ترند'
            ], 500);
        }
    }

    /**
     * Get search filters and options
     */
    public function getFilters(): JsonResponse
    {
        try {
            $filters = $this->searchService->getSearchFilters();

            return response()->json([
                'success' => true,
                'data' => $filters,
                'message' => 'فیلترهای جستجو دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Get search filters failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت فیلترهای جستجو'
            ], 500);
        }
    }

    /**
     * Get search statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_stories' => \App\Models\Story::where('status', 'published')->count(),
                'total_episodes' => \App\Models\Episode::where('status', 'published')->count(),
                'total_categories' => \App\Models\Category::count(),
                'total_people' => \App\Models\Person::count(),
                'most_searched_categories' => \App\Models\Category::orderBy('story_count', 'desc')
                                                               ->limit(5)
                                                               ->select('id', 'name', 'story_count')
                                                               ->get(),
                'most_searched_people' => \App\Models\Person::withCount('stories')
                                                           ->orderBy('stories_count', 'desc')
                                                           ->limit(5)
                                                           ->select('id', 'name', 'role')
                                                           ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'آمار جستجو دریافت شد'
            ]);

        } catch (\Exception $e) {
            Log::error('Get search stats failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار جستجو'
            ], 500);
        }
    }
}
