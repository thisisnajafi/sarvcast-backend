<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Episode;
use App\Models\Category;
use App\Models\User;
use App\Models\PlayHistory;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MobileController extends Controller
{
    /**
     * Get app configuration for mobile
     */
    public function getAppConfig()
    {
        $config = [
            'app_name' => config('app.name'),
            'app_version' => config('app.version', '1.0.0'),
            'api_version' => 'v1',
            'features' => [
                'offline_mode' => true,
                'parental_controls' => true,
                'push_notifications' => true,
                'social_sharing' => true,
                'downloads' => true,
                'favorites' => true,
                'ratings' => true,
            ],
            'limits' => [
                'max_downloads' => 50,
                'max_offline_stories' => 20,
                'max_offline_episodes' => 100,
            ],
            'supported_formats' => [
                'audio' => ['mp3', 'm4a', 'wav'],
                'image' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
            'update_required' => false,
            'maintenance_mode' => false,
        ];

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Get app version information
     */
    public function getAppVersion()
    {
        $version = [
            'current_version' => config('app.version', '1.0.0'),
            'minimum_version' => '1.0.0',
            'latest_version' => config('app.version', '1.0.0'),
            'update_available' => false,
            'force_update' => false,
            'update_url' => null,
            'release_notes' => 'نسخه اولیه اپلیکیشن سروکست',
        ];

        return response()->json([
            'success' => true,
            'data' => $version
        ]);
    }

    /**
     * Get offline stories for download
     */
    public function getOfflineStories(Request $request)
    {
        $user = Auth::user();
        
        $query = Story::with(['category', 'episodes'])
            ->where('status', 'published')
            ->where('is_completely_free', true);

        // Apply user preferences
        if ($user->preferences && isset($user->preferences['favorite_categories'])) {
            $query->whereIn('category_id', $user->preferences['favorite_categories']);
        }

        $stories = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stories' => $stories,
                'total' => $stories->count(),
                'download_size' => $stories->sum('file_size'),
            ]
        ]);
    }

    /**
     * Get offline episodes for download
     */
    public function getOfflineEpisodes(Request $request)
    {
        $user = Auth::user();
        
        $query = Episode::with(['story'])
            ->where('status', 'published')
            ->where('is_free', true);

        $episodes = $query->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'episodes' => $episodes,
                'total' => $episodes->count(),
                'download_size' => $episodes->sum('file_size'),
            ]
        ]);
    }

    /**
     * Search content
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type', 'all'); // all, stories, episodes, categories
        $limit = $request->get('limit', 20);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'جستجو نمی‌تواند خالی باشد'
            ], 400);
        }

        $results = [];

        if ($type === 'all' || $type === 'stories') {
            $stories = Story::where('status', 'published')
                ->where(function($q) use ($query) {
                    $q->where('title', 'like', '%' . $query . '%')
                      ->orWhere('subtitle', 'like', '%' . $query . '%')
                      ->orWhere('description', 'like', '%' . $query . '%');
                })
                ->with(['category'])
                ->limit($limit)
                ->get();

            $results['stories'] = $stories;
        }

        if ($type === 'all' || $type === 'episodes') {
            $episodes = Episode::where('status', 'published')
                ->where(function($q) use ($query) {
                    $q->where('title', 'like', '%' . $query . '%')
                      ->orWhere('description', 'like', '%' . $query . '%');
                })
                ->with(['story'])
                ->limit($limit)
                ->get();

            $results['episodes'] = $episodes;
        }

        if ($type === 'all' || $type === 'categories') {
            $categories = Category::where('status', 'active')
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('description', 'like', '%' . $query . '%');
                })
                ->limit($limit)
                ->get();

            $results['categories'] = $categories;
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Get personalized recommendations
     */
    public function getRecommendations(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 10);

        $recommendations = Cache::remember("user_recommendations_{$user->id}", 3600, function() use ($user, $limit) {
            // Get user's favorite categories
            $favoriteCategories = $user->favorites()
                ->with(['story.category'])
                ->get()
                ->pluck('story.category_id')
                ->unique()
                ->filter();

            // Get user's play history for recommendations
            $playedStories = $user->playHistories()
                ->with(['episode.story'])
                ->get()
                ->pluck('episode.story_id')
                ->unique()
                ->filter();

            // Get recommended stories based on user preferences
            $recommendedStories = Story::where('status', 'published')
                ->whereIn('category_id', $favoriteCategories)
                ->whereNotIn('id', $playedStories)
                ->with(['category', 'episodes'])
                ->orderBy('play_count', 'desc')
                ->limit($limit)
                ->get();

            return $recommendedStories;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => $recommendations,
                'total' => $recommendations->count(),
            ]
        ]);
    }

    /**
     * Get trending content
     */
    public function getTrending(Request $request)
    {
        $type = $request->get('type', 'stories'); // stories, episodes
        $period = $request->get('period', 'week'); // day, week, month
        $limit = $request->get('limit', 10);

        $cacheKey = "trending_{$type}_{$period}_{$limit}";
        
        $trending = Cache::remember($cacheKey, 1800, function() use ($type, $period, $limit) {
            $dateFrom = now()->sub($period === 'day' ? 1 : ($period === 'week' ? 7 : 30), 'day');

            if ($type === 'stories') {
                return Story::where('status', 'published')
                    ->whereHas('playHistories', function($query) use ($dateFrom) {
                        $query->where('created_at', '>=', $dateFrom);
                    })
                    ->with(['category'])
                    ->withCount(['playHistories' => function($query) use ($dateFrom) {
                        $query->where('created_at', '>=', $dateFrom);
                    }])
                    ->orderBy('play_histories_count', 'desc')
                    ->limit($limit)
                    ->get();
            } else {
                return Episode::where('status', 'published')
                    ->whereHas('playHistories', function($query) use ($dateFrom) {
                        $query->where('created_at', '>=', $dateFrom);
                    })
                    ->with(['story'])
                    ->withCount(['playHistories' => function($query) use ($dateFrom) {
                        $query->where('created_at', '>=', $dateFrom);
                    }])
                    ->orderBy('play_histories_count', 'desc')
                    ->limit($limit)
                    ->get();
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'trending' => $trending,
                'total' => $trending->count(),
                'period' => $period,
            ]
        ]);
    }

    /**
     * Get user preferences
     */
    public function getPreferences()
    {
        $user = Auth::user();
        
        $preferences = [
            'language' => 'fa',
            'notifications' => [
                'push' => true,
                'email' => true,
                'sms' => false,
            ],
            'audio' => [
                'quality' => 'high', // low, medium, high
                'download_quality' => 'medium',
                'auto_play' => false,
            ],
            'parental_controls' => [
                'enabled' => false,
                'age_limit' => null,
                'content_filter' => 'moderate', // strict, moderate, lenient
            ],
            'offline' => [
                'auto_download' => false,
                'wifi_only' => true,
                'max_storage' => 1000, // MB
            ],
            'accessibility' => [
                'font_size' => 'medium',
                'high_contrast' => false,
                'screen_reader' => false,
            ],
        ];

        // Merge with user's saved preferences
        if ($user->preferences) {
            $preferences = array_merge($preferences, $user->preferences);
        }

        return response()->json([
            'success' => true,
            'data' => $preferences
        ]);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'language' => 'nullable|string|in:fa,en',
            'notifications' => 'nullable|array',
            'notifications.push' => 'boolean',
            'notifications.email' => 'boolean',
            'notifications.sms' => 'boolean',
            'audio' => 'nullable|array',
            'audio.quality' => 'string|in:low,medium,high',
            'audio.download_quality' => 'string|in:low,medium,high',
            'audio.auto_play' => 'boolean',
            'parental_controls' => 'nullable|array',
            'parental_controls.enabled' => 'boolean',
            'parental_controls.age_limit' => 'nullable|integer|min:3|max:18',
            'parental_controls.content_filter' => 'string|in:strict,moderate,lenient',
            'offline' => 'nullable|array',
            'offline.auto_download' => 'boolean',
            'offline.wifi_only' => 'boolean',
            'offline.max_storage' => 'integer|min:100|max:5000',
            'accessibility' => 'nullable|array',
            'accessibility.font_size' => 'string|in:small,medium,large',
            'accessibility.high_contrast' => 'boolean',
            'accessibility.screen_reader' => 'boolean',
        ]);

        // Update user preferences
        $currentPreferences = $user->preferences ?? [];
        $newPreferences = array_merge($currentPreferences, $validated);
        
        $user->update(['preferences' => $newPreferences]);

        return response()->json([
            'success' => true,
            'message' => 'تنظیمات با موفقیت به‌روزرسانی شد',
            'data' => $newPreferences
        ]);
    }

    /**
     * Get parental controls
     */
    public function getParentalControls()
    {
        $user = Auth::user();
        
        $controls = [
            'enabled' => false,
            'age_limit' => null,
            'content_filter' => 'moderate',
            'time_limits' => [
                'daily_limit' => null, // minutes
                'bedtime_start' => null,
                'bedtime_end' => null,
            ],
            'restrictions' => [
                'premium_content' => false,
                'social_features' => false,
                'downloads' => true,
            ],
        ];

        if ($user->preferences && isset($user->preferences['parental_controls'])) {
            $controls = array_merge($controls, $user->preferences['parental_controls']);
        }

        return response()->json([
            'success' => true,
            'data' => $controls
        ]);
    }

    /**
     * Update parental controls
     */
    public function updateParentalControls(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'enabled' => 'boolean',
            'age_limit' => 'nullable|integer|min:3|max:18',
            'content_filter' => 'string|in:strict,moderate,lenient',
            'time_limits' => 'nullable|array',
            'time_limits.daily_limit' => 'nullable|integer|min:15|max:480',
            'time_limits.bedtime_start' => 'nullable|date_format:H:i',
            'time_limits.bedtime_end' => 'nullable|date_format:H:i',
            'restrictions' => 'nullable|array',
            'restrictions.premium_content' => 'boolean',
            'restrictions.social_features' => 'boolean',
            'restrictions.downloads' => 'boolean',
        ]);

        // Update parental controls in user preferences
        $currentPreferences = $user->preferences ?? [];
        $currentPreferences['parental_controls'] = $validated;
        
        $user->update(['preferences' => $currentPreferences]);

        return response()->json([
            'success' => true,
            'message' => 'کنترل والدین با موفقیت به‌روزرسانی شد',
            'data' => $validated
        ]);
    }

    /**
     * Track play event
     */
    public function trackPlay(Request $request)
    {
        $validated = $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'duration' => 'required|integer|min:1',
            'completed' => 'boolean',
            'device_info' => 'nullable|array',
        ]);

        $user = Auth::user();
        
        // Create or update play history
        $playHistory = PlayHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'episode_id' => $validated['episode_id'],
            ],
            [
                'listened_duration' => $validated['duration'],
                'completed' => $validated['completed'] ?? false,
                'device_info' => $validated['device_info'] ?? null,
                'played_at' => now(),
            ]
        );

        // Update episode play count
        Episode::where('id', $validated['episode_id'])
            ->increment('play_count');

        return response()->json([
            'success' => true,
            'message' => 'پخش ثبت شد',
            'data' => $playHistory
        ]);
    }

    /**
     * Track download event
     */
    public function trackDownload(Request $request)
    {
        $validated = $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'download_size' => 'required|integer|min:1',
            'device_info' => 'nullable|array',
        ]);

        $user = Auth::user();
        
        // Log download (you might want to create a downloads table)
        DB::table('download_logs')->insert([
            'user_id' => $user->id,
            'episode_id' => $validated['episode_id'],
            'download_size' => $validated['download_size'],
            'device_info' => json_encode($validated['device_info'] ?? []),
            'downloaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'دانلود ثبت شد'
        ]);
    }

    /**
     * Track share event
     */
    public function trackShare(Request $request)
    {
        $validated = $request->validate([
            'content_type' => 'required|string|in:story,episode',
            'content_id' => 'required|integer',
            'platform' => 'required|string|in:whatsapp,telegram,instagram,facebook,twitter,other',
            'device_info' => 'nullable|array',
        ]);

        $user = Auth::user();
        
        // Log share (you might want to create a shares table)
        DB::table('share_logs')->insert([
            'user_id' => $user->id,
            'content_type' => $validated['content_type'],
            'content_id' => $validated['content_id'],
            'platform' => $validated['platform'],
            'device_info' => json_encode($validated['device_info'] ?? []),
            'shared_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'اشتراک‌گذاری ثبت شد'
        ]);
    }

    /**
     * Register device for push notifications
     */
    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'device_type' => 'required|string|in:android,ios',
            'device_model' => 'nullable|string',
            'os_version' => 'nullable|string',
            'app_version' => 'nullable|string',
            'fcm_token' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        // Store device information (you might want to create a devices table)
        DB::table('user_devices')->updateOrInsert(
            [
                'user_id' => $user->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'device_type' => $validated['device_type'],
                'device_model' => $validated['device_model'],
                'os_version' => $validated['os_version'],
                'app_version' => $validated['app_version'],
                'fcm_token' => $validated['fcm_token'],
                'last_active' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'دستگاه ثبت شد'
        ]);
    }

    /**
     * Update FCM token
     */
    public function updateFcmToken(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user();
        
        DB::table('user_devices')
            ->where('user_id', $user->id)
            ->where('device_id', $validated['device_id'])
            ->update([
                'fcm_token' => $validated['fcm_token'],
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'توکن FCM به‌روزرسانی شد'
        ]);
    }

    /**
     * Unregister device
     */
    public function unregisterDevice(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
        ]);

        $user = Auth::user();
        
        DB::table('user_devices')
            ->where('user_id', $user->id)
            ->where('device_id', $validated['device_id'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'دستگاه حذف شد'
        ]);
    }
}