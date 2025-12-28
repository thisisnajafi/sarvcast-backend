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
use App\Http\Controllers\Api\CoinController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\AffiliateController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\InfluencerController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\CorporateController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Api\AdminPanelController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\VoiceActorPanelController;
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
    Route::get('categories', [CategoryController::class, 'index'])->middleware('cache.api:180'); // 3 minutes
    Route::get('categories/{category}/stories', [CategoryController::class, 'stories'])->middleware('cache.api:180'); // 3 minutes

    Route::get('stories', [StoryController::class, 'index'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/{story}', [StoryController::class, 'show'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/{story}/episodes', [StoryController::class, 'episodes'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/featured', [StoryController::class, 'featured'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/popular', [StoryController::class, 'popular'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/recent', [StoryController::class, 'recent'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/recommendations', [StoryController::class, 'recommendations'])->middleware('cache.api:180'); // 3 minutes

    Route::get('episodes', [EpisodeController::class, 'index'])->middleware('cache.api:180'); // 3 minutes
    Route::get('episodes/{episode}', [EpisodeController::class, 'show'])->middleware(['auth:sanctum', 'cache.api:180']); // 3 minutes

    // Story ratings routes
    Route::get('stories/{story}/ratings', [\App\Http\Controllers\Api\StoryRatingController::class, 'index'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/{story}/ratings/statistics', [\App\Http\Controllers\Api\StoryRatingController::class, 'statistics'])->middleware('cache.api:180'); // 3 minutes

    // Episode play count routes
    Route::post('episodes/{episode}/play', [\App\Http\Controllers\Api\EpisodePlayCountController::class, 'increment']);
    Route::get('episodes/{episode}/play/statistics', [\App\Http\Controllers\Api\EpisodePlayCountController::class, 'statistics'])->middleware('cache.api:180'); // 3 minutes

    // People routes
    Route::get('people', [PersonController::class, 'index'])->middleware('cache.api:180'); // 3 minutes
    Route::get('people/search', [PersonController::class, 'search'])->middleware('cache.api:180'); // 3 minutes
    Route::get('people/role/{role}', [PersonController::class, 'getByRole'])->middleware('cache.api:180'); // 3 minutes
    Route::get('people/{person}', [PersonController::class, 'show'])->middleware('cache.api:180'); // 3 minutes
    Route::get('people/{person}/stories', [PersonController::class, 'stories'])->middleware('cache.api:180'); // 3 minutes
    Route::get('people/{person}/statistics', [PersonController::class, 'statistics'])->middleware('cache.api:180'); // 3 minutes

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

    // Version management routes
    Route::prefix('version')->group(function () {
        Route::get('check', [VersionController::class, 'check']);
        Route::post('check', [VersionController::class, 'checkForUpdates']);
        Route::get('latest', [VersionController::class, 'getLatestVersion']);
        Route::get('statistics', [VersionController::class, 'getStatistics']);
        Route::get('config', [VersionController::class, 'getAppConfig']);
        Route::post('report-usage', [VersionController::class, 'reportUsage']);
    });

    // User search routes
    Route::prefix('users')->group(function () {
        Route::post('search', [UserSearchController::class, 'searchUsers']);
        Route::get('details', [UserSearchController::class, 'getUserDetails']);
        Route::get('teachers/available', [UserSearchController::class, 'getAvailableTeachers']);
        Route::get('{user}/stories', [UserController::class, 'getUserStories']);
    });

    // Admin Panel routes
    Route::prefix('admin-panel')->middleware('role:admin,super_admin')->group(function () {
        // Stats (super admin only)
        Route::get('stats', [AdminPanelController::class, 'getStats'])->middleware('role:super_admin');

        // Stories management (admin & super admin)
        Route::get('stories', [AdminPanelController::class, 'getStories']);

        // Episodes management (admin & super admin)
        Route::get('episodes', [AdminPanelController::class, 'getEpisodes']);

        // Users management (super admin only)
        Route::prefix('users')->middleware('role:super_admin')->group(function () {
            Route::get('/', [AdminPanelController::class, 'getUsers']);
            Route::post('{user}/assign-voice-actor-role', [AdminPanelController::class, 'assignVoiceActorRole']);
        });

        // Voice actors search (admin & super admin)
        Route::get('voice-actors/search', [CharacterController::class, 'searchVoiceActors']);
    });

    // Character management routes (admin & super admin)
    Route::prefix('stories/{storyId}')->middleware('role:admin,super_admin')->group(function () {
        Route::get('characters', [CharacterController::class, 'index']);
        Route::post('characters', [CharacterController::class, 'store']);
    });

    Route::prefix('characters')->middleware('role:admin,super_admin')->group(function () {
        Route::get('{character}', [CharacterController::class, 'show']);
        Route::put('{character}', [CharacterController::class, 'update']);
        Route::delete('{character}', [CharacterController::class, 'destroy']);
        Route::post('{character}/assign-voice-actor', [CharacterController::class, 'assignVoiceActor']);
    });
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::get('debug-premium', [AuthController::class, 'debugPremium']);
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
        Route::post('profile/photo', [\App\Http\Controllers\Api\AuthController::class, 'uploadProfilePhoto']);
    });

    // Favorites routes (outside mobile group for easier access)
    Route::prefix('favorites')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\FavoriteController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\FavoriteController::class, 'store']);
        Route::delete('/{storyId}', [\App\Http\Controllers\Api\FavoriteController::class, 'destroy']);
        Route::post('/toggle', [\App\Http\Controllers\Api\FavoriteController::class, 'toggle']);
        Route::get('/check/{storyId}', [\App\Http\Controllers\Api\FavoriteController::class, 'check']);
        Route::get('/most-favorited', [\App\Http\Controllers\Api\FavoriteController::class, 'mostFavorited']);
        Route::get('/stats', [\App\Http\Controllers\Api\FavoriteController::class, 'stats']);
        Route::post('/bulk', [\App\Http\Controllers\Api\FavoriteController::class, 'bulk']);
    });

    // Voice Actor Panel routes (voice actor, admin & super admin)
    Route::prefix('voice-actor-panel')->middleware('role:voice_actor,admin,super_admin')->group(function () {
        Route::get('stories', [VoiceActorPanelController::class, 'getStories']);
        Route::get('stories/{story}', [VoiceActorPanelController::class, 'getStoryDetails']);
        Route::get('stories/{story}/episodes/{episode}/script', [VoiceActorPanelController::class, 'getEpisodeScript']);
    });

    // Story routes
    Route::prefix('stories')->group(function () {
        Route::post('{story}/favorite', [StoryController::class, 'addFavorite']);
        Route::delete('{story}/favorite', [StoryController::class, 'removeFavorite']);
        Route::post('{story}/rating', [StoryController::class, 'rate']);

        // Story ratings (authenticated)
        Route::get('{story}/ratings/my', [\App\Http\Controllers\Api\StoryRatingController::class, 'show']);
        Route::post('{story}/ratings', [\App\Http\Controllers\Api\StoryRatingController::class, 'store']);
        Route::delete('{story}/ratings', [\App\Http\Controllers\Api\StoryRatingController::class, 'destroy']);

        // Story script retrieval (voice actor, admin, super admin)
        Route::middleware('role:voice_actor,admin,super_admin')->group(function () {
            Route::get('{story}/script', [StoryController::class, 'getScript']);
        });

        // Story management (admin & super admin)
        Route::middleware('role:admin,super_admin')->group(function () {
            Route::post('/', [StoryController::class, 'store']);
            Route::post('{story}/assign-narrator', [StoryController::class, 'assignNarrator']);
            Route::post('{story}/assign-author', [StoryController::class, 'assignAuthor']);
            Route::put('{story}/workflow-status', [StoryController::class, 'updateWorkflowStatus']);
            Route::post('{story}/upload-script', [StoryController::class, 'uploadScript']);
        });
    });

    // Episode routes
    Route::prefix('episodes')->group(function () {
        Route::post('{episode}/play', [EpisodeController::class, 'play']);
        Route::post('{episode}/bookmark', [EpisodeController::class, 'bookmark']);

        // Episode play count (authenticated)
        Route::get('{episode}/play/history', [\App\Http\Controllers\Api\EpisodePlayCountController::class, 'userHistory']);
        Route::post('{episode}/play/completed', [\App\Http\Controllers\Api\EpisodePlayCountController::class, 'markCompleted']);
        Route::delete('{episode}/bookmark', [EpisodeController::class, 'removeBookmark']);

        // Episode script retrieval (voice actor, admin, super admin)
        Route::middleware('role:voice_actor,admin,super_admin')->group(function () {
            Route::get('{episode}/script', [EpisodeController::class, 'getScript']);
        });

        // Episode management (admin & super admin)
        Route::middleware('role:admin,super_admin')->group(function () {
            Route::post('/', [EpisodeController::class, 'store']);
            Route::put('{episode}', [EpisodeController::class, 'update']);
            Route::delete('{episode}', [EpisodeController::class, 'destroy']);
            Route::post('{episode}/upload-script', [EpisodeController::class, 'uploadScript']);
        });
    });

    // Myket subscription routes (root level)
    Route::get('plans', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'listPlans']);
    Route::post('subscribe', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'subscribe'])->middleware('auth:sanctum');
    Route::get('subscription-status', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'subscriptionStatus'])->middleware('auth:sanctum');
    Route::post('cancel-subscription', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'cancelSubscription'])->middleware('auth:sanctum');

    // Subscription routes
        Route::prefix('subscriptions')->group(function () {
            Route::get('plans', [SubscriptionController::class, 'plans']);
            Route::post('calculate-price', [SubscriptionController::class, 'calculatePrice']);
            Route::post('/', [SubscriptionController::class, 'store']);
            Route::get('current', [SubscriptionController::class, 'current']);
            Route::get('{subscriptionId}', [SubscriptionController::class, 'show']);
            Route::post('cancel', [SubscriptionController::class, 'cancel']);
            Route::get('debug/subscription', [SubscriptionController::class, 'debugSubscription']);
            Route::post('debug/subscription/{subscriptionId}/activate', [SubscriptionController::class, 'manuallyActivateSubscription']);
            Route::get('debug/zarinpal', [SubscriptionController::class, 'debugZarinPal']);

            // CafeBazaar subscription routes (flavor-aware)
            Route::prefix('cafebazaar')->group(function () {
                Route::post('verify', [\App\Http\Controllers\Api\CafeBazaarSubscriptionController::class, 'verifySubscription']);
                Route::get('status', [\App\Http\Controllers\Api\CafeBazaarSubscriptionController::class, 'getSubscriptionStatus']);
                Route::post('restore', [\App\Http\Controllers\Api\CafeBazaarSubscriptionController::class, 'restorePurchases']);
            });

            // Myket subscription routes
            Route::prefix('myket')->group(function () {
                Route::get('plans', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'listPlans']);
                Route::post('subscribe', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'subscribe']);
                Route::get('subscription-status', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'subscriptionStatus']);
                Route::post('cancel-subscription', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'cancelSubscription']);
            });
        });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('initiate', [PaymentController::class, 'initiate']);
        Route::post('verify', [PaymentController::class, 'verify']);
        Route::get('history', [PaymentController::class, 'history']);

        // In-app purchase verification
        Route::post('cafebazaar/verify', [\App\Http\Controllers\Api\InAppPurchaseController::class, 'verifyCafeBazaarPurchase']);
        Route::post('myket/verify', [\App\Http\Controllers\Api\InAppPurchaseController::class, 'verifyMyketPurchase']);
    });

    // Billing platform configuration
    Route::get('billing/platform-config', [\App\Http\Controllers\Api\InAppPurchaseController::class, 'getPlatformConfig']);

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
        Route::post('{commentId}/like', [StoryCommentController::class, 'toggleLike']);
        Route::get('{commentId}/replies', [StoryCommentController::class, 'getReplies']);

        // Admin delete comment (admins can delete any comment)
        Route::middleware('role:admin,super_admin')->group(function () {
            Route::delete('{commentId}/admin', [StoryCommentController::class, 'adminDeleteComment']);
        });
    });

    // Coin System routes
    Route::prefix('coins')->middleware('auth:sanctum')->group(function () {
        Route::get('balance', [CoinController::class, 'getBalance']);
        Route::get('transactions', [CoinController::class, 'getTransactions']);
        Route::get('statistics', [CoinController::class, 'getStatistics']);
        Route::post('spend', [CoinController::class, 'spendCoins']);
        Route::get('redemption-options', [CoinController::class, 'getRedemptionOptions']);

        // Admin only routes
        Route::post('award', [CoinController::class, 'awardCoins']);
        Route::get('global-statistics', [CoinController::class, 'getGlobalStatistics']);
        Route::get('admin-transactions', [CoinController::class, 'getAdminTransactions']);
        Route::get('admin-users', [CoinController::class, 'getAdminUsers']);
        Route::get('admin-redemption-options', [CoinController::class, 'getAdminRedemptionOptions']);
        Route::post('admin-redemption-options', [CoinController::class, 'createRedemptionOption']);
        Route::put('admin-redemption-options/{id}/toggle', [CoinController::class, 'toggleRedemptionOption']);
        Route::delete('admin-redemption-options/{id}', [CoinController::class, 'deleteRedemptionOption']);
    });

    // Coin Analytics routes
    Route::prefix('coin-analytics')->middleware('auth:sanctum')->group(function () {
        Route::get('overview', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getOverview']);
        Route::get('earning-sources', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getEarningSources']);
        Route::get('spending-patterns', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getSpendingPatterns']);
        Route::get('transaction-trends', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getTransactionTrends']);
        Route::get('user-distribution', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getUserDistribution']);
        Route::get('quiz-performance', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getQuizPerformance']);
        Route::get('referral-performance', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getReferralPerformance']);
        Route::get('top-earners', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getTopEarners']);
        Route::get('system-health', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getSystemHealth']);
        Route::get('comprehensive-report', [\App\Http\Controllers\Api\CoinAnalyticsController::class, 'getComprehensiveReport']);
    });

    // Quiz System routes
    Route::prefix('quiz')->middleware('auth:sanctum')->group(function () {
        Route::get('episodes/{episodeId}/questions', [QuizController::class, 'getEpisodeQuestions']);
        Route::post('submit-answer', [QuizController::class, 'submitAnswer']);
        Route::get('statistics', [QuizController::class, 'getUserStatistics']);
        Route::get('episodes/{episodeId}/statistics', [QuizController::class, 'getEpisodeStatistics']);

        // Admin only routes
        Route::get('global-statistics', [QuizController::class, 'getGlobalStatistics']);
        Route::post('questions', [QuizController::class, 'createQuestion']);
        Route::put('questions/{questionId}', [QuizController::class, 'updateQuestion']);
        Route::delete('questions/{questionId}', [QuizController::class, 'deleteQuestion']);
    });

    // Referral System routes
    Route::prefix('referral')->middleware('auth:sanctum')->group(function () {
        Route::get('code', [ReferralController::class, 'getReferralCode']);
        Route::get('statistics', [ReferralController::class, 'getReferralStatistics']);
        Route::get('referrals', [ReferralController::class, 'getReferrals']);
        Route::post('use-code', [ReferralController::class, 'useReferralCode']);
        Route::post('check-completion', [ReferralController::class, 'checkReferralCompletion']);

        // Admin only routes
        Route::get('global-statistics', [ReferralController::class, 'getGlobalStatistics']);
        Route::get('top-referrers', [ReferralController::class, 'getTopReferrers']);
    });

    // Referral Analytics routes
    Route::prefix('referral-analytics')->middleware('auth:sanctum')->group(function () {
        Route::get('overview', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getOverview']);
        Route::get('trends', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getTrends']);
        Route::get('top-referrers', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getTopReferrers']);
        Route::get('sources', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getSources']);
        Route::get('performance-by-timeframe', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getPerformanceByTimeframe']);
        Route::get('funnel-analysis', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getFunnelAnalysis']);
        Route::get('geographic-distribution', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getGeographicDistribution']);
        Route::get('revenue-analysis', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getRevenueAnalysis']);
        Route::get('system-health', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getSystemHealth']);
        Route::get('comprehensive-report', [\App\Http\Controllers\Api\ReferralAnalyticsController::class, 'getComprehensiveReport']);
    });

    // Coupon System routes
    Route::prefix('coupons')->middleware('auth:sanctum')->group(function () {
        Route::post('validate', [\App\Http\Controllers\Api\CouponController::class, 'validateCoupon']);
        Route::post('use', [\App\Http\Controllers\Api\CouponController::class, 'useCoupon']);
        Route::get('my-coupons', [\App\Http\Controllers\Api\CouponController::class, 'getMyCoupons']);
        Route::get('my-usage', [\App\Http\Controllers\Api\CouponController::class, 'getCouponUsage']);
        Route::get('my-statistics', [\App\Http\Controllers\Api\CouponController::class, 'getCouponStatistics']);

        // Admin only routes
        Route::post('create', [\App\Http\Controllers\Api\CouponController::class, 'createCoupon']);
        Route::get('all', [\App\Http\Controllers\Api\CouponController::class, 'getCoupons']);
        Route::get('usage', [\App\Http\Controllers\Api\CouponController::class, 'getAllCouponUsage']);
        Route::get('global-statistics', [\App\Http\Controllers\Api\CouponController::class, 'getGlobalStatistics']);
    });

    // Commission Payment routes
    Route::prefix('commission-payments')->middleware('auth:sanctum')->group(function () {
        Route::get('my-payments', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'getMyPayments']);
        Route::get('my-history', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'getPaymentHistory']);

        // Admin only routes
        Route::get('pending', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'getPendingPayments']);
        Route::post('process', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'processPayment']);
        Route::post('mark-paid', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'markAsPaid']);
        Route::post('mark-failed', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'markAsFailed']);
        Route::post('create-manual', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'createManualPayment']);
        Route::post('bulk-process', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'bulkProcessPayments']);
        Route::get('all', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'getAllPayments']);
        Route::get('statistics', [\App\Http\Controllers\Api\CommissionPaymentController::class, 'getPaymentStatistics']);
    });

    // Affiliate Program routes
    Route::prefix('affiliate')->middleware('auth:sanctum')->group(function () {
        Route::post('partners', [AffiliateController::class, 'createPartner']);
        Route::get('partners/{type}', [AffiliateController::class, 'getPartnersByType']);
        Route::get('requirements', [AffiliateController::class, 'getProgramRequirements']);
        Route::get('commission-rates', [AffiliateController::class, 'getTierCommissionRates']);
        Route::post('commissions', [AffiliateController::class, 'createCommission']);
        Route::get('partners/{partnerId}/statistics', [AffiliateController::class, 'getPartnerStatistics']);

        // Admin only routes
        Route::put('partners/{partnerId}/verify', [AffiliateController::class, 'verifyPartner']);
        Route::put('partners/{partnerId}/suspend', [AffiliateController::class, 'suspendPartner']);
        Route::put('commissions/{commissionId}/approve', [AffiliateController::class, 'approveCommission']);
        Route::put('commissions/{commissionId}/pay', [AffiliateController::class, 'markCommissionAsPaid']);
        Route::get('commissions/pending', [AffiliateController::class, 'getPendingCommissions']);
        Route::post('commissions/bulk-approve', [AffiliateController::class, 'processBulkCommissionApprovals']);
        Route::get('global-statistics', [AffiliateController::class, 'getGlobalStatistics']);
    });

    // Teacher/Educator Program routes
    Route::prefix('teacher')->middleware('auth:sanctum')->group(function () {
        Route::post('account', [TeacherController::class, 'createTeacherAccount']);
        Route::get('account', [TeacherController::class, 'getTeacherAccount']);
        Route::post('student-license', [TeacherController::class, 'createStudentLicense']);
        Route::get('student-licenses', [TeacherController::class, 'getStudentLicenses']);
        Route::get('benefits', [TeacherController::class, 'getProgramBenefits']);
        Route::get('institution-types', [TeacherController::class, 'getInstitutionTypes']);
        Route::get('teaching-subjects', [TeacherController::class, 'getTeachingSubjects']);
        Route::get('teacher-accounts/{teacherAccountId}/student-licenses', [TeacherController::class, 'getTeacherStudentLicenses']);

        // Admin only routes
        Route::put('teacher-accounts/{teacherAccountId}/verify', [TeacherController::class, 'verifyTeacherAccount']);
        Route::get('global-statistics', [TeacherController::class, 'getGlobalStatistics']);
        Route::post('process-expired-licenses', [TeacherController::class, 'processExpiredLicenses']);
    });

    // Influencer Program routes
    Route::prefix('influencer')->middleware('auth:sanctum')->group(function () {
        Route::post('campaigns', [InfluencerController::class, 'createCampaign']);
        Route::post('content', [InfluencerController::class, 'submitContent']);
        Route::get('campaigns/{campaignId}', [InfluencerController::class, 'getCampaign']);
        Route::get('partners/{partnerId}/campaigns', [InfluencerController::class, 'getPartnerCampaigns']);
        Route::get('campaigns/{campaignId}/content', [InfluencerController::class, 'getCampaignContent']);
        Route::get('campaign-types', [InfluencerController::class, 'getCampaignTypes']);
        Route::get('content-types', [InfluencerController::class, 'getContentTypes']);
        Route::get('platforms', [InfluencerController::class, 'getPlatforms']);
        Route::get('compensation-rates', [InfluencerController::class, 'getTierCompensationRates']);
        Route::put('content/{contentId}/metrics', [InfluencerController::class, 'updateContentMetrics']);

        // Admin only routes
        Route::put('content/{contentId}/approve', [InfluencerController::class, 'approveContent']);
        Route::put('content/{contentId}/reject', [InfluencerController::class, 'rejectContent']);
        Route::get('global-statistics', [InfluencerController::class, 'getGlobalStatistics']);
    });

    // School Partnership Program routes
    Route::prefix('school')->middleware('auth:sanctum')->group(function () {
        Route::post('partnerships', [SchoolController::class, 'createPartnership']);
        Route::post('licenses', [SchoolController::class, 'createLicense']);
        Route::get('partnerships/{partnershipId}', [SchoolController::class, 'getPartnership']);
        Route::get('partnerships/{partnershipId}/licenses', [SchoolController::class, 'getPartnershipLicenses']);
        Route::get('user-licenses', [SchoolController::class, 'getUserLicenses']);
        Route::get('partnership-models', [SchoolController::class, 'getPartnershipModels']);
        Route::get('school-types', [SchoolController::class, 'getSchoolTypes']);
        Route::get('school-levels', [SchoolController::class, 'getSchoolLevels']);
        Route::get('partnership-benefits', [SchoolController::class, 'getPartnershipBenefits']);

        // Admin only routes
        Route::put('partnerships/{partnershipId}/verify', [SchoolController::class, 'verifyPartnership']);
        Route::get('global-statistics', [SchoolController::class, 'getGlobalStatistics']);
        Route::post('process-expired-licenses', [SchoolController::class, 'processExpiredLicenses']);
    });

    // Corporate Sponsorship Program routes
    Route::prefix('corporate')->middleware('auth:sanctum')->group(function () {
        Route::post('sponsorships', [CorporateController::class, 'createSponsorship']);
        Route::post('content', [CorporateController::class, 'createContent']);
        Route::get('sponsorships/{sponsorshipId}', [CorporateController::class, 'getSponsorship']);
        Route::get('sponsorships/{sponsorshipId}/content', [CorporateController::class, 'getSponsorshipContent']);
        Route::get('content/{contentId}/analytics', [CorporateController::class, 'getContentAnalytics']);
        Route::get('sponsorships/{sponsorshipId}/analytics', [CorporateController::class, 'getSponsorshipAnalytics']);
        Route::get('sponsorship-types', [CorporateController::class, 'getSponsorshipTypes']);
        Route::get('company-types', [CorporateController::class, 'getCompanyTypes']);
        Route::get('industries', [CorporateController::class, 'getIndustries']);
        Route::get('company-sizes', [CorporateController::class, 'getCompanySizes']);
        Route::get('payment-frequencies', [CorporateController::class, 'getPaymentFrequencies']);
        Route::get('sponsorship-benefits', [CorporateController::class, 'getSponsorshipBenefits']);
        Route::get('content-types', [CorporateController::class, 'getContentTypes']);
        Route::get('placement-types', [CorporateController::class, 'getPlacementTypes']);
        Route::put('content/{contentId}/event', [CorporateController::class, 'recordEvent']);

        // Admin only routes
        Route::put('content/{contentId}/approve', [CorporateController::class, 'approveContent']);
        Route::put('content/{contentId}/reject', [CorporateController::class, 'rejectContent']);
        Route::get('global-statistics', [CorporateController::class, 'getGlobalStatistics']);
    });
});

// Admin API Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    // Coin Management API
    Route::prefix('coins')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CoinController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\CoinController::class, 'apiStore']);
        Route::get('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'apiShow']);
        Route::put('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'apiUpdate']);
        Route::delete('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'apiDestroy']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CoinController::class, 'apiBulkAction']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\CoinController::class, 'apiStatistics']);
    });

    // Coupon Management API
    Route::prefix('coupons')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CouponController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\CouponController::class, 'apiStore']);
        Route::get('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'apiShow']);
        Route::put('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'apiUpdate']);
        Route::delete('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'apiDestroy']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CouponController::class, 'apiBulkAction']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\CouponController::class, 'apiStatistics']);
    });

    // Commission Payment API
    Route::prefix('commission-payments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiStore']);
        Route::get('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiShow']);
        Route::put('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiUpdate']);
        Route::delete('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiDestroy']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiBulkAction']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiStatistics']);
    });

    // Affiliate Program API
    Route::prefix('affiliate')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiStore']);
        Route::get('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiShow']);
        Route::put('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiUpdate']);
        Route::delete('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiDestroy']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiBulkAction']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiStatistics']);
    });

    // Subscription Plan API
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiStore']);
        Route::get('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiShow']);
        Route::put('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiUpdate']);
        Route::delete('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiDestroy']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiBulkAction']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiStatistics']);
    });

    // Role Management API
    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RoleController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\RoleController::class, 'apiStore']);
        Route::get('/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'apiShow']);
        Route::put('/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'apiUpdate']);
        Route::delete('/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'apiDestroy']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\RoleController::class, 'apiBulkAction']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\RoleController::class, 'apiStatistics']);
    });

        // User Management API
        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\UserController::class, 'apiStore']);
            Route::get('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'apiShow']);
            Route::put('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'apiUpdate']);
            Route::delete('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\UserController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\UserController::class, 'apiStatistics']);
        });

        // User Analytics API
        Route::prefix('user-analytics')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiOverview']);
            Route::get('/registration', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiRegistration']);
            Route::get('/engagement', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiEngagement']);
            Route::get('/demographics', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiDemographics']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiStatistics']);
        });

        // Revenue Analytics API
        Route::prefix('revenue-analytics')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiOverview']);
            Route::get('/subscriptions', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiSubscriptions']);
            Route::get('/payments', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiPayments']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiStatistics']);
        });

        // System Analytics API
        Route::prefix('system-analytics')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiOverview']);
            Route::get('/performance', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiPerformance']);
            Route::get('/health', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiHealth']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiStatistics']);
        });

        // Quiz System API
        Route::prefix('quiz')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\QuizController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\QuizController::class, 'apiStore']);
            Route::get('/{quizQuestion}', [\App\Http\Controllers\Admin\QuizController::class, 'apiShow']);
            Route::put('/{quizQuestion}', [\App\Http\Controllers\Admin\QuizController::class, 'apiUpdate']);
            Route::delete('/{quizQuestion}', [\App\Http\Controllers\Admin\QuizController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\QuizController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\QuizController::class, 'apiStatistics']);
            Route::get('/episodes', [\App\Http\Controllers\Admin\QuizController::class, 'getEpisodes']);
        });

        // Referral System API
        Route::prefix('referrals')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ReferralController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\ReferralController::class, 'apiStore']);
            Route::get('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'apiShow']);
            Route::put('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'apiUpdate']);
            Route::delete('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\ReferralController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\ReferralController::class, 'apiStatistics']);
        });

        // Gamification System API - DISABLED
        // Route::prefix('gamification')->group(function () {
        //     Route::get('/', [\App\Http\Controllers\Admin\GamificationController::class, 'apiIndex']);
        //     Route::post('/', [\App\Http\Controllers\Admin\GamificationController::class, 'apiStore']);
        //     Route::get('/{gamification}', [\App\Http\Controllers\Admin\GamificationController::class, 'apiShow']);
        //     Route::put('/{gamification}', [\App\Http\Controllers\Admin\GamificationController::class, 'apiUpdate']);
        //     Route::delete('/{gamification}', [\App\Http\Controllers\Admin\GamificationController::class, 'apiDestroy']);
        //     Route::post('/bulk-action', [\App\Http\Controllers\Admin\GamificationController::class, 'apiBulkAction']);
        //     Route::get('/statistics/data', [\App\Http\Controllers\Admin\GamificationController::class, 'apiStatistics']);
        // });

        // Content Moderation API
        Route::prefix('content-moderation')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiStore']);
            Route::get('/{contentModeration}', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiShow']);
            Route::put('/{contentModeration}', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiUpdate']);
            Route::delete('/{contentModeration}', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiStatistics']);
        });

        // Backup System API
        Route::prefix('backup')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\BackupController::class, 'apiStore']);
            Route::get('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'apiShow']);
            Route::put('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'apiUpdate']);
            Route::delete('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\BackupController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\BackupController::class, 'apiStatistics']);
            Route::get('/{backup}/download', [\App\Http\Controllers\Admin\BackupController::class, 'apiDownload']);
            Route::post('/{backup}/restore', [\App\Http\Controllers\Admin\BackupController::class, 'apiRestore']);
        });

        // Performance Monitoring API
        Route::prefix('performance-monitoring')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiStore']);
            Route::get('/{performanceAlert}', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiShow']);
            Route::put('/{performanceAlert}', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiUpdate']);
            Route::delete('/{performanceAlert}', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiStatistics']);
            Route::get('/overview', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiOverview']);
            Route::get('/metrics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiMetrics']);
            Route::get('/health', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiHealth']);
        });

        // Audio Management API
        Route::prefix('audio-management')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiStore']);
            Route::get('/{audioFile}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiShow']);
            Route::put('/{audioFile}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiUpdate']);
            Route::delete('/{audioFile}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiStatistics']);
            Route::get('/{audioFile}/download', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiDownload']);
            Route::post('/{audioFile}/process', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiProcess']);
        });

        // Timeline Management API
        Route::prefix('timeline-management')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiStore']);
            Route::get('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiShow']);
            Route::put('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiUpdate']);
            Route::delete('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiStatistics']);
        });

        // File Upload API
        Route::prefix('file-upload')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiStore']);
            Route::get('/{fileUpload}', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiShow']);
            Route::put('/{fileUpload}', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiUpdate']);
            Route::delete('/{fileUpload}', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiStatistics']);
            Route::get('/{fileUpload}/download', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiDownload']);
        });

        // Notifications API
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\NotificationController::class, 'apiStore']);
            Route::get('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'apiShow']);
            Route::put('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'apiUpdate']);
            Route::delete('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\NotificationController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\NotificationController::class, 'apiStatistics']);
            Route::post('/{notification}/send', [\App\Http\Controllers\Admin\NotificationController::class, 'apiSend']);
        });

        // Story Management API
        Route::prefix('stories')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\StoryController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\StoryController::class, 'apiStore']);
            Route::get('/{story}', [\App\Http\Controllers\Admin\StoryController::class, 'apiShow']);
            Route::put('/{story}', [\App\Http\Controllers\Admin\StoryController::class, 'apiUpdate']);
            Route::delete('/{story}', [\App\Http\Controllers\Admin\StoryController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\StoryController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\StoryController::class, 'apiStatistics']);
        });

        // Episode Management API
        Route::prefix('episodes')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiStore']);
            Route::get('/{episode}', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiShow']);
            Route::put('/{episode}', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiUpdate']);
            Route::delete('/{episode}', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiDestroy']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiStatistics']);
        });

        // Dashboard API
        Route::get('/dashboard/stats', [\App\Http\Controllers\Admin\DashboardController::class, 'apiStats']);
        Route::get('/dashboard/charts', [\App\Http\Controllers\Admin\DashboardController::class, 'apiCharts']);
        Route::get('/online-users', [\App\Http\Controllers\Admin\DashboardController::class, 'apiOnlineUsers']);
    });

