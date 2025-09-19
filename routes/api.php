<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\EpisodeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\ContentPersonalizationController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\GamificationController;
use App\Http\Controllers\Api\ImageTimelineController;
use App\Http\Controllers\Api\EpisodeVoiceActorController;
use App\Http\Controllers\Api\StoryCommentController;
use App\Http\Controllers\Admin\PersonController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('v1')->middleware('security')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        // SMS verification
        Route::post('send-verification-code', [AuthController::class, 'sendVerificationCode']);
        
        // User authentication (SMS-based)
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        
        // Admin authentication (phone + password)
        Route::post('admin/login', [AuthController::class, 'adminLogin']);
    });

    // Public content routes (with caching)
    Route::get('categories', [CategoryController::class, 'index'])->middleware('cache.api:1800'); // 30 minutes
    Route::get('categories/{category}/stories', [CategoryController::class, 'stories'])->middleware('cache.api:900'); // 15 minutes
    
    Route::get('stories', [StoryController::class, 'index'])->middleware('cache.api:900'); // 15 minutes
    Route::get('stories/{story}', [StoryController::class, 'show'])->middleware('cache.api:1800'); // 30 minutes
    Route::get('stories/{story}/episodes', [StoryController::class, 'episodes'])->middleware('cache.api:900'); // 15 minutes
    
    Route::get('episodes/{episode}', [EpisodeController::class, 'show'])->middleware('cache.api:1800'); // 30 minutes
    
    // People routes
    Route::get('people', [PersonController::class, 'index'])->middleware('cache.api:900'); // 15 minutes
    Route::get('people/search', [PersonController::class, 'search'])->middleware('cache.api:300'); // 5 minutes
    Route::get('people/role/{role}', [PersonController::class, 'getByRole'])->middleware('cache.api:900'); // 15 minutes
    Route::get('people/{person}', [PersonController::class, 'show'])->middleware('cache.api:1800'); // 30 minutes
    Route::get('people/{person}/statistics', [PersonController::class, 'statistics'])->middleware('cache.api:300'); // 5 minutes
    
    // File upload routes (DISABLED - Admin only)
    // Route::post('upload/image', [FileUploadController::class, 'uploadImage']);
    // Route::post('upload/audio', [FileUploadController::class, 'uploadAudio']);
    // Route::post('upload/document', [FileUploadController::class, 'uploadDocument']);
    // Route::post('upload/multiple', [FileUploadController::class, 'uploadMultiple']);
    // Route::get('upload/config', [FileUploadController::class, 'getUploadConfig']);
    
    // Health check routes
    Route::get('health', [HealthController::class, 'health']);
    Route::get('health/metrics', [HealthController::class, 'metrics']);
    Route::get('health/report', [HealthController::class, 'report']);
    Route::get('health/errors', [HealthController::class, 'errorRates']);
    Route::get('health/performance', [HealthController::class, 'apiPerformance']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('favorites', [UserController::class, 'favorites']);
        Route::get('history', [UserController::class, 'history']);
        Route::post('profiles', [UserController::class, 'createProfile']);
        Route::get('profiles', [UserController::class, 'profiles']);
        Route::put('profiles/{profile}', [UserController::class, 'updateProfile']);
        Route::delete('profiles/{profile}', [UserController::class, 'deleteProfile']);
    });

    // Story routes
    Route::prefix('stories')->group(function () {
        Route::post('{story}/favorite', [StoryController::class, 'addFavorite']);
        Route::delete('{story}/favorite', [StoryController::class, 'removeFavorite']);
        Route::post('{story}/rating', [StoryController::class, 'rate']);
    });

    // Episode routes
    Route::prefix('episodes')->group(function () {
        Route::post('{episode}/play', [EpisodeController::class, 'play']);
        Route::post('{episode}/bookmark', [EpisodeController::class, 'bookmark']);
        Route::delete('{episode}/bookmark', [EpisodeController::class, 'removeBookmark']);
    });

    // Subscription routes
    Route::prefix('subscriptions')->group(function () {
        Route::get('plans', [SubscriptionController::class, 'plans']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::get('current', [SubscriptionController::class, 'current']);
        Route::post('cancel', [SubscriptionController::class, 'cancel']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('initiate', [PaymentController::class, 'initiate']);
        Route::post('verify', [PaymentController::class, 'verify']);
        Route::get('history', [PaymentController::class, 'history']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
    });

    // File upload routes (DISABLED - Admin only)
    // Route::prefix('files')->group(function () {
    //     Route::post('upload/image', [FileUploadController::class, 'uploadImage']);
    //     Route::post('upload/audio', [FileUploadController::class, 'uploadAudio']);
    //     Route::post('upload/file', [FileUploadController::class, 'uploadFile']);
    //     Route::delete('delete', [FileUploadController::class, 'deleteFile']);
    //     Route::get('info', [FileUploadController::class, 'getFileInfo']);
    //     Route::get('config', [FileUploadController::class, 'getStorageConfig']);
    // });

    // Mobile-specific routes
    Route::prefix('mobile')->group(function () {
        // App configuration
        Route::get('config', [\App\Http\Controllers\Api\MobileController::class, 'getAppConfig']);
        Route::get('version', [\App\Http\Controllers\Api\MobileController::class, 'getAppVersion']);
        
        // Offline content
        Route::get('offline/stories', [\App\Http\Controllers\Api\MobileController::class, 'getOfflineStories']);
        Route::get('offline/episodes', [\App\Http\Controllers\Api\MobileController::class, 'getOfflineEpisodes']);
        
        // Search and discovery
        Route::get('search', [\App\Http\Controllers\Api\MobileController::class, 'search']);
        Route::get('recommendations', [\App\Http\Controllers\Api\MobileController::class, 'getRecommendations']);
        Route::get('trending', [\App\Http\Controllers\Api\MobileController::class, 'getTrending']);
        
        // User preferences
        Route::get('preferences', [\App\Http\Controllers\Api\MobileController::class, 'getPreferences']);
        Route::put('preferences', [\App\Http\Controllers\Api\MobileController::class, 'updatePreferences']);
        
        // Parental controls
        Route::get('parental-controls', [\App\Http\Controllers\Api\MobileController::class, 'getParentalControls']);
        Route::put('parental-controls', [\App\Http\Controllers\Api\MobileController::class, 'updateParentalControls']);
        
        // Analytics and tracking
        Route::post('track/play', [\App\Http\Controllers\Api\MobileController::class, 'trackPlay']);
        Route::post('track/download', [\App\Http\Controllers\Api\MobileController::class, 'trackDownload']);
        Route::post('track/share', [\App\Http\Controllers\Api\MobileController::class, 'trackShare']);
        
        // Device management
        Route::post('device/register', [\App\Http\Controllers\Api\MobileController::class, 'registerDevice']);
        Route::post('device/token', [\App\Http\Controllers\Api\MobileController::class, 'updateFcmToken']);
        Route::delete('device/unregister', [\App\Http\Controllers\Api\MobileController::class, 'unregisterDevice']);
        
        // File upload routes (DISABLED - Admin only)
        // Route::post('upload/image', [\App\Http\Controllers\Api\FileUploadController::class, 'uploadImage']);
        // Route::post('upload/audio', [\App\Http\Controllers\Api\FileUploadController::class, 'uploadAudio']);
        // Route::post('upload/document', [\App\Http\Controllers\Api\FileUploadController::class, 'uploadDocument']);
        // Route::post('upload/multiple', [\App\Http\Controllers\Api\FileUploadController::class, 'uploadMultiple']);
        // Route::delete('upload/delete', [\App\Http\Controllers\Api\FileUploadController::class, 'deleteFile']);
        // Route::get('upload/info', [\App\Http\Controllers\Api\FileUploadController::class, 'getFileInfo']);
        // Route::post('upload/cleanup', [\App\Http\Controllers\Api\FileUploadController::class, 'cleanupTempFiles']);
        // Route::get('upload/config', [\App\Http\Controllers\Api\FileUploadController::class, 'getUploadConfig']);
        
        // Audio processing routes (MOVED TO ADMIN ONLY)
        // Route::post('audio/process', [\App\Http\Controllers\Api\AudioProcessingController::class, 'processAudio']);
        // Route::post('audio/extract-metadata', [\App\Http\Controllers\Api\AudioProcessingController::class, 'extractMetadata']);
        // Route::post('audio/convert', [\App\Http\Controllers\Api\AudioProcessingController::class, 'convertFormat']);
        // Route::post('audio/normalize', [\App\Http\Controllers\Api\AudioProcessingController::class, 'normalizeAudio']);
        // Route::post('audio/trim', [\App\Http\Controllers\Api\AudioProcessingController::class, 'trimAudio']);
        // Route::post('audio/validate', [\App\Http\Controllers\Api\AudioProcessingController::class, 'validateAudio']);
        // Route::get('audio/stats', [\App\Http\Controllers\Api\AudioProcessingController::class, 'getStats']);
        // Route::post('audio/cleanup', [\App\Http\Controllers\Api\AudioProcessingController::class, 'cleanup']);
        
        // Image processing routes (MOVED TO ADMIN ONLY)
        // Route::post('image/process', [\App\Http\Controllers\Api\ImageProcessingController::class, 'processImage']);
        // Route::post('image/resize', [\App\Http\Controllers\Api\ImageProcessingController::class, 'resizeImage']);
        // Route::post('image/crop', [\App\Http\Controllers\Api\ImageProcessingController::class, 'cropImage']);
        // Route::post('image/watermark', [\App\Http\Controllers\Api\ImageProcessingController::class, 'addWatermark']);
        // Route::post('image/optimize', [\App\Http\Controllers\Api\ImageProcessingController::class, 'optimizeImage']);
        // Route::post('image/thumbnail', [\App\Http\Controllers\Api\ImageProcessingController::class, 'generateThumbnail']);
        // Route::post('image/multiple-sizes', [\App\Http\Controllers\Api\ImageProcessingController::class, 'generateMultipleSizes']);
        // Route::get('image/info', [\App\Http\Controllers\Api\ImageProcessingController::class, 'getImageInfo']);
        // Route::post('image/validate', [\App\Http\Controllers\Api\ImageProcessingController::class, 'validateImage']);
        // Route::get('image/stats', [\App\Http\Controllers\Api\ImageProcessingController::class, 'getStats']);
        // Route::post('image/cleanup', [\App\Http\Controllers\Api\ImageProcessingController::class, 'cleanup']);
        
        // Favorites routes
        Route::get('favorites', [\App\Http\Controllers\Api\FavoriteController::class, 'index']);
        Route::post('favorites', [\App\Http\Controllers\Api\FavoriteController::class, 'store']);
        Route::delete('favorites/{storyId}', [\App\Http\Controllers\Api\FavoriteController::class, 'destroy']);
        Route::post('favorites/toggle', [\App\Http\Controllers\Api\FavoriteController::class, 'toggle']);
        Route::get('favorites/check/{storyId}', [\App\Http\Controllers\Api\FavoriteController::class, 'check']);
        Route::get('favorites/most-favorited', [\App\Http\Controllers\Api\FavoriteController::class, 'mostFavorited']);
        Route::get('favorites/stats', [\App\Http\Controllers\Api\FavoriteController::class, 'stats']);
        Route::post('favorites/bulk', [\App\Http\Controllers\Api\FavoriteController::class, 'bulk']);
        
        // Play History routes
        Route::get('play-history', [\App\Http\Controllers\Api\PlayHistoryController::class, 'index']);
        Route::post('play-history/record', [\App\Http\Controllers\Api\PlayHistoryController::class, 'record']);
        Route::put('play-history/{playHistoryId}/progress', [\App\Http\Controllers\Api\PlayHistoryController::class, 'updateProgress']);
        Route::get('play-history/recent', [\App\Http\Controllers\Api\PlayHistoryController::class, 'recent']);
        Route::get('play-history/completed', [\App\Http\Controllers\Api\PlayHistoryController::class, 'completed']);
        Route::get('play-history/in-progress', [\App\Http\Controllers\Api\PlayHistoryController::class, 'inProgress']);
        Route::get('play-history/stats', [\App\Http\Controllers\Api\PlayHistoryController::class, 'stats']);
        Route::get('play-history/episode/{episodeId}/stats', [\App\Http\Controllers\Api\PlayHistoryController::class, 'episodeStats']);
        Route::get('play-history/story/{storyId}/stats', [\App\Http\Controllers\Api\PlayHistoryController::class, 'storyStats']);
        Route::get('play-history/most-played', [\App\Http\Controllers\Api\PlayHistoryController::class, 'mostPlayed']);
        Route::get('play-history/most-played-stories', [\App\Http\Controllers\Api\PlayHistoryController::class, 'mostPlayedStories']);
        Route::get('play-history/analytics', [\App\Http\Controllers\Api\PlayHistoryController::class, 'analytics']);
        
        // Rating & Review routes
        Route::get('ratings', [\App\Http\Controllers\Api\RatingController::class, 'index']);
        Route::post('ratings/story', [\App\Http\Controllers\Api\RatingController::class, 'submitStoryRating']);
        Route::post('ratings/episode', [\App\Http\Controllers\Api\RatingController::class, 'submitEpisodeRating']);
        Route::get('ratings/story/{storyId}', [\App\Http\Controllers\Api\RatingController::class, 'getStoryRatings']);
        Route::get('ratings/episode/{episodeId}', [\App\Http\Controllers\Api\RatingController::class, 'getEpisodeRatings']);
        Route::get('ratings/story/{storyId}/user', [\App\Http\Controllers\Api\RatingController::class, 'getUserStoryRating']);
        Route::get('ratings/episode/{episodeId}/user', [\App\Http\Controllers\Api\RatingController::class, 'getUserEpisodeRating']);
        Route::get('ratings/highest-rated-stories', [\App\Http\Controllers\Api\RatingController::class, 'getHighestRatedStories']);
        Route::get('ratings/highest-rated-episodes', [\App\Http\Controllers\Api\RatingController::class, 'getHighestRatedEpisodes']);
        Route::get('ratings/recent-reviews', [\App\Http\Controllers\Api\RatingController::class, 'getRecentReviews']);
        Route::get('ratings/user-stats', [\App\Http\Controllers\Api\RatingController::class, 'getUserStats']);
        Route::get('ratings/analytics', [\App\Http\Controllers\Api\RatingController::class, 'getAnalytics']);
        
        // Search & Discovery routes
        Route::get('search/stories', [\App\Http\Controllers\Api\SearchController::class, 'searchStories']);
        Route::get('search/episodes', [\App\Http\Controllers\Api\SearchController::class, 'searchEpisodes']);
        Route::get('search/people', [\App\Http\Controllers\Api\SearchController::class, 'searchPeople']);
        Route::get('search/global', [\App\Http\Controllers\Api\SearchController::class, 'globalSearch']);
        Route::get('search/suggestions', [\App\Http\Controllers\Api\SearchController::class, 'getSuggestions']);
        Route::get('search/trending', [\App\Http\Controllers\Api\SearchController::class, 'getTrending']);
        Route::get('search/filters', [\App\Http\Controllers\Api\SearchController::class, 'getFilters']);
        Route::get('search/stats', [\App\Http\Controllers\Api\SearchController::class, 'getStats']);
        
        // Subscription Management routes
        Route::get('subscriptions', [\App\Http\Controllers\Api\SubscriptionController::class, 'index']);
        Route::get('subscriptions/status', [\App\Http\Controllers\Api\SubscriptionController::class, 'status']);
        Route::get('subscriptions/plans', [\App\Http\Controllers\Api\SubscriptionController::class, 'plans']);
        Route::post('subscriptions/calculate-price', [\App\Http\Controllers\Api\SubscriptionController::class, 'calculatePrice']);
        Route::post('subscriptions', [\App\Http\Controllers\Api\SubscriptionController::class, 'create']);
        Route::post('subscriptions/{subscriptionId}/activate', [\App\Http\Controllers\Api\SubscriptionController::class, 'activate']);
        Route::post('subscriptions/{subscriptionId}/cancel', [\App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
        Route::post('subscriptions/{subscriptionId}/renew', [\App\Http\Controllers\Api\SubscriptionController::class, 'renew']);
        Route::post('subscriptions/{subscriptionId}/upgrade', [\App\Http\Controllers\Api\SubscriptionController::class, 'upgrade']);
        Route::post('subscriptions/trial', [\App\Http\Controllers\Api\SubscriptionController::class, 'createTrial']);
        Route::get('subscriptions/{subscriptionId}', [\App\Http\Controllers\Api\SubscriptionController::class, 'show']);
        Route::get('subscriptions/stats', [\App\Http\Controllers\Api\SubscriptionController::class, 'stats']);
        
        // Access Control routes
        Route::get('access/level', [\App\Http\Controllers\Api\AccessControlController::class, 'getUserAccessLevel']);
        Route::get('access/story/{storyId}', [\App\Http\Controllers\Api\AccessControlController::class, 'checkStoryAccess']);
        Route::get('access/episode/{episodeId}', [\App\Http\Controllers\Api\AccessControlController::class, 'checkEpisodeAccess']);
        Route::post('access/download', [\App\Http\Controllers\Api\AccessControlController::class, 'checkDownloadAccess']);
        Route::get('access/premium-features', [\App\Http\Controllers\Api\AccessControlController::class, 'getPremiumFeatures']);
        Route::post('access/validate', [\App\Http\Controllers\Api\AccessControlController::class, 'validateContentAccess']);
        Route::post('access/filtered-content', [\App\Http\Controllers\Api\AccessControlController::class, 'getFilteredContent']);
        Route::get('access/statistics', [\App\Http\Controllers\Api\AccessControlController::class, 'getAccessStatistics']);
        
        // SMS Notifications routes
        Route::post('sms/send', [\App\Http\Controllers\Api\SmsController::class, 'send']);
        Route::post('sms/verification-code', [\App\Http\Controllers\Api\SmsController::class, 'sendVerificationCode']);
        Route::post('sms/template', [\App\Http\Controllers\Api\SmsController::class, 'sendTemplate']);
        Route::post('sms/bulk', [\App\Http\Controllers\Api\SmsController::class, 'sendBulk']);
        Route::post('sms/welcome', [\App\Http\Controllers\Api\SmsController::class, 'sendWelcome']);
        Route::post('sms/subscription-notification', [\App\Http\Controllers\Api\SmsController::class, 'sendSubscriptionNotification']);
        Route::post('sms/payment-notification', [\App\Http\Controllers\Api\SmsController::class, 'sendPaymentNotification']);
        Route::post('sms/content-notification', [\App\Http\Controllers\Api\SmsController::class, 'sendContentNotification']);
        Route::get('sms/statistics', [\App\Http\Controllers\Api\SmsController::class, 'getStatistics']);
        Route::get('sms/templates', [\App\Http\Controllers\Api\SmsController::class, 'getTemplates']);
        Route::get('sms/providers', [\App\Http\Controllers\Api\SmsController::class, 'getProviders']);
        
        // In-App Notifications routes
        Route::get('notifications', [\App\Http\Controllers\Api\InAppNotificationController::class, 'index']);
        Route::get('notifications/unread-count', [\App\Http\Controllers\Api\InAppNotificationController::class, 'unreadCount']);
        Route::post('notifications/{notificationId}/mark-read', [\App\Http\Controllers\Api\InAppNotificationController::class, 'markAsRead']);
        Route::post('notifications/mark-all-read', [\App\Http\Controllers\Api\InAppNotificationController::class, 'markAllAsRead']);
        Route::delete('notifications/{notificationId}', [\App\Http\Controllers\Api\InAppNotificationController::class, 'destroy']);
        Route::get('notifications/types', [\App\Http\Controllers\Api\InAppNotificationController::class, 'getTypes']);
        Route::get('notifications/priorities', [\App\Http\Controllers\Api\InAppNotificationController::class, 'getPriorities']);
        Route::get('notifications/categories', [\App\Http\Controllers\Api\InAppNotificationController::class, 'getCategories']);
    });
    
    // Recommendation routes
    Route::prefix('recommendations')->middleware('auth:sanctum')->group(function () {
        Route::get('personalized', [RecommendationController::class, 'getPersonalizedRecommendations']);
        Route::get('new-user', [RecommendationController::class, 'getNewUserRecommendations']);
        Route::get('trending', [RecommendationController::class, 'getTrendingRecommendations']);
        Route::get('similar/{storyId}', [RecommendationController::class, 'getSimilarContent']);
        Route::get('preferences', [RecommendationController::class, 'getUserPreferences']);
        Route::get('behavior', [RecommendationController::class, 'getUserBehavior']);
        Route::get('similar-users', [RecommendationController::class, 'getSimilarUsers']);
        Route::post('clear-cache', [RecommendationController::class, 'clearRecommendationCache']);
        Route::get('explanation/{storyId}', [RecommendationController::class, 'getRecommendationExplanation']);
    });
    
    // Content Personalization routes
    Route::prefix('personalization')->middleware('auth:sanctum')->group(function () {
        Route::get('feed', [ContentPersonalizationController::class, 'getPersonalizedFeed']);
        Route::get('search', [ContentPersonalizationController::class, 'getPersonalizedSearch']);
        Route::get('category/{categoryId}/recommendations', [ContentPersonalizationController::class, 'getPersonalizedCategoryRecommendations']);
        Route::get('dashboard', [ContentPersonalizationController::class, 'getPersonalizedDashboard']);
        Route::post('learn-preferences', [ContentPersonalizationController::class, 'learnPreferences']);
        Route::post('update-preferences', [ContentPersonalizationController::class, 'updatePreferencesFromInteraction']);
        Route::get('preferences', [ContentPersonalizationController::class, 'getUserPreferences']);
        Route::get('behavior', [ContentPersonalizationController::class, 'getUserBehavior']);
        Route::get('suggestions', [ContentPersonalizationController::class, 'getContentSuggestions']);
        Route::get('insights', [ContentPersonalizationController::class, 'getPersonalizedInsights']);
        Route::post('clear-cache', [ContentPersonalizationController::class, 'clearPersonalizationCache']);
        Route::get('stats', [ContentPersonalizationController::class, 'getPersonalizationStats']);
    });
    
    // Social Features routes
    Route::prefix('social')->middleware('auth:sanctum')->group(function () {
        Route::post('follow/{userId}', [SocialController::class, 'followUser']);
        Route::delete('unfollow/{userId}', [SocialController::class, 'unfollowUser']);
        Route::post('share', [SocialController::class, 'shareContent']);
        Route::get('followers/{userId}', [SocialController::class, 'getUserFollowers']);
        Route::get('following/{userId}', [SocialController::class, 'getUserFollowing']);
        Route::get('activity-feed', [SocialController::class, 'getUserActivityFeed']);
        Route::post('playlists', [SocialController::class, 'createPlaylist']);
        Route::post('playlists/{playlistId}/add', [SocialController::class, 'addToPlaylist']);
        Route::post('comments', [SocialController::class, 'addComment']);
        // Route::post('comments/{commentId}/like', [SocialController::class, 'likeComment']); // DISABLED
        Route::get('stats/{userId}', [SocialController::class, 'getUserSocialStats']);
        Route::get('trending', [SocialController::class, 'getTrendingContent']);
        Route::get('follow-status/{userId}', [SocialController::class, 'checkFollowStatus']);
    });
    
    // Gamification routes (DISABLED)
    // Route::prefix('gamification')->middleware('auth:sanctum')->group(function () {
    //     Route::get('profile', [GamificationController::class, 'getUserProfile']);
    //     Route::post('award-points', [GamificationController::class, 'awardPoints']);
    //     Route::get('leaderboard/{slug}', [GamificationController::class, 'getLeaderboard']);
    //     Route::post('leaderboard/{slug}/update', [GamificationController::class, 'updateLeaderboard']);
    //     Route::post('streak', [GamificationController::class, 'updateStreak']);
    //     Route::get('challenges', [GamificationController::class, 'getAvailableChallenges']);
    //     Route::post('challenges/{challengeId}/join', [GamificationController::class, 'joinChallenge']);
    //     Route::get('achievements', [GamificationController::class, 'getUserAchievements']);
    //     Route::get('badges', [GamificationController::class, 'getUserBadges']);
    //     Route::get('streaks', [GamificationController::class, 'getUserStreaks']);
    //     Route::get('all-achievements', [GamificationController::class, 'getAllAchievements']);
    //     Route::get('all-badges', [GamificationController::class, 'getAllBadges']);
    // });
    
    // Voice Actor routes
    Route::prefix('episodes')->middleware('auth:sanctum')->group(function () {
        Route::get('{episodeId}/voice-actors', [EpisodeVoiceActorController::class, 'getVoiceActors']);
        Route::post('{episodeId}/voice-actors', [EpisodeVoiceActorController::class, 'addVoiceActor']);
        Route::put('{episodeId}/voice-actors/{voiceActorId}', [EpisodeVoiceActorController::class, 'updateVoiceActor']);
        Route::delete('{episodeId}/voice-actors/{voiceActorId}', [EpisodeVoiceActorController::class, 'deleteVoiceActor']);
        Route::get('{episodeId}/voice-actor-for-time', [EpisodeVoiceActorController::class, 'getVoiceActorForTime']);
        Route::get('{episodeId}/voice-actors-at-time', [EpisodeVoiceActorController::class, 'getVoiceActorsAtTime']);
        Route::get('{episodeId}/voice-actors-by-role', [EpisodeVoiceActorController::class, 'getVoiceActorsByRole']);
        Route::get('{episodeId}/voice-actor-statistics', [EpisodeVoiceActorController::class, 'getVoiceActorStatistics']);
    });

    // Enhanced Image Timeline routes (User read-only access)
    Route::prefix('episodes')->middleware('auth:sanctum')->group(function () {
        Route::get('{episodeId}/image-timeline', [ImageTimelineController::class, 'getTimeline']);
        Route::get('{episodeId}/image-timeline-with-voice-actors', [ImageTimelineController::class, 'getTimelineWithVoiceActors']);
        Route::get('{episodeId}/image-timeline-for-voice-actor', [ImageTimelineController::class, 'getTimelineForVoiceActor']);
        Route::get('{episodeId}/key-frames', [ImageTimelineController::class, 'getKeyFrames']);
        Route::get('{episodeId}/timeline-by-transition-type', [ImageTimelineController::class, 'getTimelineByTransitionType']);
        Route::get('{episodeId}/image-for-time', [ImageTimelineController::class, 'getImageForTime']);
        Route::get('{episodeId}/timeline-statistics', [ImageTimelineController::class, 'getStatistics']);
    });
    
    // Story Comments routes
    Route::prefix('stories')->middleware('auth:sanctum')->group(function () {
        Route::get('{storyId}/comments', [StoryCommentController::class, 'getComments']);
        Route::post('{storyId}/comments', [StoryCommentController::class, 'addComment']);
        Route::get('{storyId}/comments/statistics', [StoryCommentController::class, 'getCommentStatistics']);
    });
    
    Route::prefix('comments')->middleware('auth:sanctum')->group(function () {
        Route::get('my-comments', [StoryCommentController::class, 'getUserComments']);
        Route::delete('{commentId}', [StoryCommentController::class, 'deleteComment']);
    });
});

