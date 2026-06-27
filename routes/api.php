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
use App\Http\Controllers\Api\SponsorController as PublicSponsorController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\SearchHistoryController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Api\StorySearchController;
use App\Http\Controllers\Api\AdminPanelController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\VoiceActorPanelController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\ContactController;
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

    // Public routes (no auth required)
    Route::prefix('public')->group(function () {
        Route::get('team-members', [UserController::class, 'getTeamMembers']);
        Route::post('contact', [ContactController::class, 'submit']);
    });

    // Authentication routes
    Route::prefix('auth')->group(function () {
        // SMS verification
        Route::post('send-verification-code', [AuthController::class, 'sendVerificationCode']);

        // User authentication (SMS-based)
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        // Admin authentication (OTP-based)
        Route::post('admin/send-otp', [AuthController::class, 'sendAdminOtp']);
        Route::post('admin/login', [AuthController::class, 'adminLogin']);
    });

    // Public content routes (with caching)
    Route::get('categories', [CategoryController::class, 'index'])->middleware('cache.api:180'); // 3 minutes
    Route::get('categories/{category}/stories', [CategoryController::class, 'stories'])->middleware('cache.api:180'); // 3 minutes

    Route::get('stories', [StoryController::class, 'index'])->middleware('cache.api:180'); // 3 minutes
    // Static story routes must be before stories/{story} so "featured"/"popular" etc. are not matched as IDs
    Route::get('stories/featured', [StoryController::class, 'featured'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/popular', [StoryController::class, 'popular'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/recent', [StoryController::class, 'recent'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/recommendations', [StoryController::class, 'recommendations'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/{story}', [StoryController::class, 'show'])->middleware('cache.api:180'); // 3 minutes
    Route::get('stories/{story}/episodes', [StoryController::class, 'episodes'])->middleware('cache.api:180'); // 3 minutes

    Route::get('sponsors/{sponsor}', [PublicSponsorController::class, 'show'])->middleware('cache.api:180');

    /** Web app advanced search (no auth; same handler as authenticated mobile route). */
    Route::get('search/stories', [\App\Http\Controllers\Api\SearchController::class, 'searchStories'])->middleware('cache.api:60');

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
        Route::post('{user}/track-view', [UserController::class, 'trackProfileView']);
        Route::get('{user}/view-count', [UserController::class, 'getProfileViewCount']);
    });

    // Dashboard API routes (for AJAX data loading)
    Route::prefix('dashboard')->middleware('role:admin,super_admin')->group(function () {
        Route::get('stats', [\App\Http\Controllers\Api\DashboardApiController::class, 'getStats']);
        Route::get('charts', [\App\Http\Controllers\Api\DashboardApiController::class, 'getChartData']);
    });

    // Admin Panel routes
    Route::prefix('admin-panel')->middleware('role:admin,super_admin')->group(function () {
        // Stats (super admin only)
        Route::get('stats', [AdminPanelController::class, 'getStats'])->middleware('role:super_admin');

        // Stories management (admin & super admin)
        Route::get('stories', [AdminPanelController::class, 'getStories']);
        Route::post('stories/search', [StorySearchController::class, 'searchStories']);

        // Episodes management (admin & super admin)
        Route::get('episodes', [AdminPanelController::class, 'getEpisodes']);

        // Users management (super admin only)
        Route::prefix('users')->middleware('role:super_admin')->group(function () {
            Route::get('/', [AdminPanelController::class, 'getUsers']);
            Route::post('{user}/assign-voice-actor-role', [AdminPanelController::class, 'assignVoiceActorRole']);
        });

        // User role management (admin & super admin)
        Route::prefix('users')->middleware('role:admin,super_admin')->group(function () {
            Route::post('{user}/demote-role', [AdminPanelController::class, 'demoteUserRole']);
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

// Canonical Admin Auth API (for separate Next.js dashboard)
Route::prefix('admin/v1/auth')->middleware(['security', 'admin.origin'])->group(function () {
    // Public admin auth endpoints (rate-limited)
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('send-otp', [AuthController::class, 'sendAdminOtp']);
        Route::post('login', [AuthController::class, 'adminLogin']);
    });

    // Authenticated admin endpoints
    Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
        Route::get('me', [AuthController::class, 'profile']);
        Route::middleware('api.audit')->group(function () {
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('onboarding-profile', [\App\Http\Controllers\Api\UserController::class, 'updateUserProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAllSessions']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::post('2fa/send-code', [AuthController::class, 'sendAdminTwoFactorCode']);
            Route::post('2fa/verify', [AuthController::class, 'verifyAdminTwoFactorCode']);
        });
    });
});

Route::prefix('admin/v1/dashboard')
    ->middleware(['security', 'admin.origin', 'auth:sanctum', 'role:admin,super_admin'])
    ->group(function () {
        Route::get('stats', [AdminDashboardController::class, 'stats']);
        Route::get('charts', [AdminDashboardController::class, 'charts']);
        Route::get('export', [AdminDashboardController::class, 'export']);
        Route::get('online-users', [AdminDashboardController::class, 'onlineUsers']);
    });

// Optional legacy compatibility aliases for old dashboard clients.
// Disabled by default; enable only for temporary migration windows.
if (filter_var(env('ADMIN_ENABLE_LEGACY_API_ALIASES', false), FILTER_VALIDATE_BOOL)) {
    Route::prefix('v1/admin/auth')
        ->middleware(['security', 'admin.origin', 'legacy.api.deprecation'])
        ->group(function () {
            Route::middleware('throttle:20,1')->group(function () {
                Route::post('send-otp', [AuthController::class, 'sendAdminOtp']);
                Route::post('login', [AuthController::class, 'adminLogin']);
            });

            Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
                Route::get('me', [AuthController::class, 'profile']);
                Route::middleware('api.audit')->group(function () {
                    Route::post('logout', [AuthController::class, 'logout']);
                    Route::post('logout-all', [AuthController::class, 'logoutAllSessions']);
                    Route::post('2fa/send-code', [AuthController::class, 'sendAdminTwoFactorCode']);
                    Route::post('2fa/verify', [AuthController::class, 'verifyAdminTwoFactorCode']);
                });
            });
        });

    Route::prefix('v1/auth/admin')
        ->middleware(['security', 'admin.origin', 'legacy.api.deprecation'])
        ->group(function () {
            Route::middleware('throttle:20,1')->group(function () {
                Route::post('send-otp', [AuthController::class, 'sendAdminOtp']);
                Route::post('login', [AuthController::class, 'adminLogin']);
            });

            Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
                Route::get('me', [AuthController::class, 'profile']);
                Route::middleware('api.audit')->group(function () {
                    Route::post('logout', [AuthController::class, 'logout']);
                    Route::post('logout-all', [AuthController::class, 'logoutAllSessions']);
                    Route::post('2fa/send-code', [AuthController::class, 'sendAdminTwoFactorCode']);
                    Route::post('2fa/verify', [AuthController::class, 'verifyAdminTwoFactorCode']);
                });
            });
        });

    Route::prefix('admin/dashboard')
        ->middleware(['security', 'admin.origin', 'auth:sanctum', 'role:admin,super_admin', 'legacy.api.deprecation'])
        ->group(function () {
            Route::get('stats', [AdminDashboardController::class, 'stats']);
            Route::get('charts', [AdminDashboardController::class, 'charts']);
        });

    Route::prefix('v1/dashboard')
        ->middleware(['security', 'admin.origin', 'auth:sanctum', 'role:admin,super_admin', 'legacy.api.deprecation'])
        ->group(function () {
            Route::get('stats', [AdminDashboardController::class, 'stats']);
            Route::get('charts', [AdminDashboardController::class, 'charts']);
        });
}

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAllSessions']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('sessions', [AuthController::class, 'listSessions']);
        Route::delete('sessions/{id}', [AuthController::class, 'revokeSession']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::get('debug-premium', [AuthController::class, 'debugPremium']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });

    // Mobile app activity telemetry
    Route::prefix('activity')->middleware('throttle:60,1')->group(function () {
        Route::post('batch', [\App\Http\Controllers\Api\ActivityIngestController::class, 'batch']);
        Route::get('/', [\App\Http\Controllers\Api\ActivityIngestController::class, 'index']);
    });

    // User routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'getUserProfile']);
        Route::put('profile', [UserController::class, 'updateUserProfile']);
        Route::get('favorites', [UserController::class, 'favorites']);
        Route::get('history', [UserController::class, 'history']);
        Route::post('profiles', [UserController::class, 'createProfile']);
        Route::get('profiles', [UserController::class, 'profiles']);
        Route::put('profiles/{profile}', [UserController::class, 'updateProfile']);
        Route::delete('profiles/{profile}', [UserController::class, 'deleteProfile']);
        Route::post('profile/photo', [\App\Http\Controllers\Api\AuthController::class, 'uploadProfilePhoto']);
        Route::post('profile/background-photo', [\App\Http\Controllers\Api\AuthController::class, 'uploadBackgroundPhoto']);
        Route::put('notification-preferences', [UserController::class, 'updateNotificationPreferences']);
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

        // Episode navigation (next/previous)
        Route::get('{episode}/next', [EpisodeController::class, 'getNextEpisode']);
        Route::get('{episode}/previous', [EpisodeController::class, 'getPreviousEpisode'])->middleware('auth:sanctum');

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

            // Myket subscription routes (unified subscriptions table — same pattern as cafebazaar)
            Route::prefix('myket')->group(function () {
                Route::post('verify', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'verifySubscription']);
                Route::get('status', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'getSubscriptionStatus']);
                Route::post('restore', [\App\Http\Controllers\Api\MyketSubscriptionController::class, 'restorePurchases']);
                // Legacy routes (myket_plans table — deprecated, kept for backward compatibility)
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
        Route::get('devices', [\App\Http\Controllers\Api\MobileController::class, 'listDevices']);
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

    // Home feed routes
    Route::prefix('home')->group(function () {
        Route::get('personalized', [HomeController::class, 'personalized']);
    });

    // Search history routes
    Route::prefix('search')->group(function () {
        Route::post('history', [SearchHistoryController::class, 'store']);
        Route::get('history', [SearchHistoryController::class, 'recent']);
        Route::get('history/trending', [SearchHistoryController::class, 'trending']);
        Route::put('history/{searchHistory}/click', [SearchHistoryController::class, 'recordClick']);
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
Route::prefix('admin')->middleware(['auth:sanctum', 'api.admin', 'api.permission', 'throttle:120,1', 'api.audit'])->group(function () {

    Route::get('/search', [\App\Http\Controllers\Admin\DashboardController::class, 'globalSearch']);

    // Activity / audit logs (static paths before /{activityLog})
    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index']);
        Route::get('/stats', [\App\Http\Controllers\Admin\ActivityLogController::class, 'stats']);
        Route::get('/export', [\App\Http\Controllers\Admin\ActivityLogController::class, 'export']);
        Route::get('/{activityLog}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show']);
    });

    // Coin Management API (static paths before /{coin})
    Route::prefix('coins')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CoinController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\CoinController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\CoinController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\CoinController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CoinController::class, 'apiBulkAction']);
        Route::get('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'apiShow']);
        Route::put('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'apiUpdate']);
        Route::delete('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'apiDestroy']);
    });

    // Coupon Management API (static paths before /{coupon})
    Route::prefix('coupons')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CouponController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\CouponController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\CouponController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\CouponController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CouponController::class, 'apiBulkAction']);
        Route::get('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'apiShow']);
        Route::put('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'apiUpdate']);
        Route::delete('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'apiDestroy']);
    });

    // Commission Payment API
    Route::prefix('commission-payments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiBulkAction']);
        Route::get('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiShow']);
        Route::put('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiUpdate']);
        Route::delete('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'apiDestroy']);
    });

    // Payments API
    Route::prefix('payments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PaymentController::class, 'apiIndex']);
        Route::get('/export', [\App\Http\Controllers\Admin\PaymentController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\PaymentController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\PaymentController::class, 'apiBulkAction']);
        Route::get('/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'apiShow']);
        Route::put('/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'apiUpdate']);
        Route::delete('/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'apiDestroy']);
    });

    // Affiliate Program API (static paths before /{affiliate})
    Route::prefix('affiliate')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiBulkAction']);
        Route::get('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiShow']);
        Route::put('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiUpdate']);
        Route::delete('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'apiDestroy']);
    });

    // Subscription Plan API (static paths before /{subscriptionPlan})
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiBulkAction']);
        Route::get('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiShow']);
        Route::put('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiUpdate']);
        Route::delete('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'apiDestroy']);
    });

    // Subscriptions API
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SubscriptionController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\SubscriptionController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\SubscriptionController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\SubscriptionController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\SubscriptionController::class, 'apiBulkAction']);
        Route::get('/{subscription}', [\App\Http\Controllers\Admin\SubscriptionController::class, 'apiShow']);
        Route::put('/{subscription}', [\App\Http\Controllers\Admin\SubscriptionController::class, 'apiUpdate']);
    });

    // Role Management API (static paths before /{role})
    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RoleController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\RoleController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\RoleController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\RoleController::class, 'apiStatistics']);
        Route::get('/permissions', [\App\Http\Controllers\Admin\RoleController::class, 'apiPermissions']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\RoleController::class, 'apiBulkAction']);
        Route::get('/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'apiShow']);
        Route::put('/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'apiUpdate']);
        Route::delete('/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'apiDestroy']);
    });

    // Media Library API (static paths before /{mediaAsset})
    Route::prefix('media')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'store']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'statistics']);
        Route::get('/import-legacy/preview', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'legacyImportPreview']);
        Route::post('/import-legacy', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'legacyImport']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'bulkAction']);
        Route::get('/{mediaAsset}/stream', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'stream']);
        Route::get('/{mediaAsset}', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'show']);
        Route::put('/{mediaAsset}', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'update']);
        Route::delete('/{mediaAsset}', [\App\Http\Controllers\Admin\MediaLibraryController::class, 'destroy']);
    });

    // Category Management API
    // Category Management API (static paths before /{category})
    Route::prefix('categories')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CategoryController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\CategoryController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\CategoryController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\CategoryController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CategoryController::class, 'apiBulkAction']);
        Route::get('/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'apiShow']);
        Route::put('/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'apiUpdate']);
        Route::delete('/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'apiDestroy']);
    });

    // Sponsor Management API (static paths before /{sponsor})
    Route::prefix('sponsors')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SponsorController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\SponsorController::class, 'apiStore']);
        Route::get('/{sponsor}', [\App\Http\Controllers\Admin\SponsorController::class, 'apiShow']);
        Route::put('/{sponsor}', [\App\Http\Controllers\Admin\SponsorController::class, 'apiUpdate']);
        Route::patch('/{sponsor}', [\App\Http\Controllers\Admin\SponsorController::class, 'apiUpdate']);
        Route::delete('/{sponsor}', [\App\Http\Controllers\Admin\SponsorController::class, 'apiDestroy']);
        Route::post('/{sponsor}/logo', [\App\Http\Controllers\Admin\SponsorController::class, 'apiReplaceLogo']);
        Route::get('/{sponsor}/stories', [\App\Http\Controllers\Admin\SponsorController::class, 'apiStories']);
    });

    // SMS overview + management API
    Route::prefix('sms')->group(function () {
        Route::get('/overview/statistics/data', [\App\Http\Controllers\Admin\SmsOverviewController::class, 'apiStatistics']);
    });

    // SMS Template Management API
    Route::prefix('sms-templates')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiBulkAction']);
        Route::get('/{smsTemplate}', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiShow']);
        Route::put('/{smsTemplate}', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiUpdate']);
        Route::delete('/{smsTemplate}', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiDestroy']);
        Route::post('/{smsTemplate}/test-send', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'apiTestSend']);
    });

    // SMS Campaign Management API
    Route::prefix('sms-campaigns')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiStatistics']);
        Route::get('/{smsCampaign}/recipients/export', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiExportRecipients']);
        Route::get('/{smsCampaign}/recipients', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiRecipients']);
        Route::post('/{smsCampaign}/preview', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiPreview']);
        Route::post('/{smsCampaign}/dispatch', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiDispatch']);
        Route::post('/{smsCampaign}/cancel', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiCancel']);
        Route::get('/{smsCampaign}', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiShow']);
        Route::put('/{smsCampaign}', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiUpdate']);
        Route::delete('/{smsCampaign}', [\App\Http\Controllers\Admin\SmsCampaignController::class, 'apiDestroy']);
    });

        // User Management API
        // User Management API (static paths before /{user})
        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\UserController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\UserController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\UserController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\UserController::class, 'apiBulkAction']);
            Route::get('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'apiShow']);
            Route::put('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'apiUpdate']);
            Route::delete('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'apiDestroy']);
            Route::post('/{user}/send-notification', [\App\Http\Controllers\Admin\UserController::class, 'apiSendNotification']);
        });

        // User Analytics API
        Route::prefix('user-analytics')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiOverview']);
            Route::get('/registration', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiRegistration']);
            Route::get('/engagement', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiEngagement']);
            Route::get('/demographics', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiDemographics']);
            Route::get('/retention', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiRetention']);
            Route::get('/behavior', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiBehavior']);
            Route::get('/trends', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiTrends']);
            Route::get('/segments', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiSegments']);
            Route::get('/real-time', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiRealTime']);
            Route::get('/activity', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiActivity']);
            Route::get('/subscription', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiSubscription']);
            Route::get('/summary', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiSummary']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiStatistics']);
            Route::get('/export', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'apiExport']);
        });

        // Revenue Analytics API
        Route::prefix('revenue-analytics')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiOverview']);
            Route::get('/subscriptions', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiSubscriptions']);
            Route::get('/payments', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiPayments']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiStatistics']);
            Route::get('/export', [\App\Http\Controllers\Admin\RevenueAnalyticsController::class, 'apiExport']);
        });

        // Content Analytics API
        Route::prefix('content-analytics')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Admin\ContentAnalyticsController::class, 'apiOverview']);
            Route::get('/performance', [\App\Http\Controllers\Admin\ContentAnalyticsController::class, 'apiPerformance']);
            Route::get('/popularity', [\App\Http\Controllers\Admin\ContentAnalyticsController::class, 'apiPopularity']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\ContentAnalyticsController::class, 'apiStatistics']);
            Route::get('/export', [\App\Http\Controllers\Admin\ContentAnalyticsController::class, 'apiExport']);
        });

        // System Analytics API
        Route::prefix('system-analytics')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiOverview']);
            Route::get('/performance', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiPerformance']);
            Route::get('/health', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiHealth']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiStatistics']);
            Route::get('/export', [\App\Http\Controllers\Admin\SystemAnalyticsController::class, 'apiExport']);
        });

        // Quiz System API
        Route::prefix('quiz')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\QuizController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\QuizController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\QuizController::class, 'apiExport']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\QuizController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\QuizController::class, 'apiStatistics']);
            Route::get('/episodes', [\App\Http\Controllers\Admin\QuizController::class, 'getEpisodes']);
            Route::get('/{quizQuestion}', [\App\Http\Controllers\Admin\QuizController::class, 'apiShow']);
            Route::put('/{quizQuestion}', [\App\Http\Controllers\Admin\QuizController::class, 'apiUpdate']);
            Route::delete('/{quizQuestion}', [\App\Http\Controllers\Admin\QuizController::class, 'apiDestroy']);
        });

        // Referral System API
        Route::prefix('referrals')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ReferralController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\ReferralController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\ReferralController::class, 'apiExport']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\ReferralController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\ReferralController::class, 'apiStatistics']);
            Route::get('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'apiShow']);
            Route::put('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'apiUpdate']);
            Route::delete('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'apiDestroy']);
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
            Route::get('/export', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiExport']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiBulkAction']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiStatistics']);
            Route::get('/{contentModeration}', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiShow']);
            Route::put('/{contentModeration}', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiUpdate']);
            Route::delete('/{contentModeration}', [\App\Http\Controllers\Admin\ContentModerationController::class, 'apiDestroy']);
        });

        // Comment Moderation API (static paths before /{comment})
        Route::prefix('comment-moderation')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CommentModerationController::class, 'apiIndex']);
            Route::get('/export', [\App\Http\Controllers\Admin\CommentModerationController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\CommentModerationController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\CommentModerationController::class, 'apiBulkAction']);
            Route::get('/{comment}', [\App\Http\Controllers\Admin\CommentModerationController::class, 'apiShow']);
        });

        // Backup System API (static paths before /{backup})
        Route::prefix('backup')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\BackupController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\BackupController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\BackupController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\BackupController::class, 'apiBulkAction']);
            Route::get('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'apiShow']);
            Route::put('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'apiUpdate']);
            Route::delete('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'apiDestroy']);
            Route::get('/{backup}/download', [\App\Http\Controllers\Admin\BackupController::class, 'apiDownload']);
            Route::post('/{backup}/restore', [\App\Http\Controllers\Admin\BackupController::class, 'apiRestore']);
        });

        // Performance Monitoring API (static paths before /{performanceAlert})
        Route::prefix('performance-monitoring')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiStatistics']);
            Route::get('/overview', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiOverview']);
            Route::get('/metrics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiMetrics']);
            Route::get('/health', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiHealth']);
            Route::get('/real-time', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiRealTime']);
            Route::get('/report', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiReport']);
            Route::post('/cleanup', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiCleanup']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiBulkAction']);
            Route::get('/{performanceAlert}', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiShow']);
            Route::put('/{performanceAlert}', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiUpdate']);
            Route::delete('/{performanceAlert}', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'apiDestroy']);
        });

        // Version Management API (static paths before /{appVersion})
        Route::prefix('app-versions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AppVersionController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\AppVersionController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\AppVersionController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\AppVersionController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\AppVersionController::class, 'apiBulkAction']);
            Route::get('/{appVersion}', [\App\Http\Controllers\Admin\AppVersionController::class, 'apiShow']);
            Route::put('/{appVersion}', [\App\Http\Controllers\Admin\AppVersionController::class, 'apiUpdate']);
            Route::delete('/{appVersion}', [\App\Http\Controllers\Admin\AppVersionController::class, 'apiDestroy']);
        });

        // Audio Management API (static paths before /{audioFile})
        Route::prefix('audio-management')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiBulkAction']);
            Route::get('/{audioFile}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiShow']);
            Route::put('/{audioFile}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiUpdate']);
            Route::delete('/{audioFile}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiDestroy']);
            Route::get('/{audioFile}/download', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiDownload']);
            Route::post('/{audioFile}/process', [\App\Http\Controllers\Admin\AudioManagementController::class, 'apiProcess']);
        });

        // Timeline Management API (static paths before /{timeline})
        Route::prefix('timeline-management')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiStatistics']);
            Route::post('/upload-image', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiUploadImage']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiBulkAction']);
            Route::get('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiShow']);
            Route::put('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiUpdate']);
            Route::delete('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'apiDestroy']);
        });

        // File Upload API (static paths before /{fileUpload})
        Route::prefix('file-upload')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiBulkAction']);
            Route::get('/{fileUpload}', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiShow']);
            Route::put('/{fileUpload}', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiUpdate']);
            Route::delete('/{fileUpload}', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiDestroy']);
            Route::get('/{fileUpload}/download', [\App\Http\Controllers\Admin\FileUploadController::class, 'apiDownload']);
        });

        // Notifications API (static paths before /{notification})
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\NotificationController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\NotificationController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\NotificationController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\NotificationController::class, 'apiBulkAction']);
            Route::get('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'apiShow']);
            Route::put('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'apiUpdate']);
            Route::delete('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'apiDestroy']);
            Route::post('/{notification}/send', [\App\Http\Controllers\Admin\NotificationController::class, 'apiSend']);
        });

    // People API (static paths before /{person})
    Route::prefix('people')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PersonController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\PersonController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\PersonController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\PersonController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\PersonController::class, 'apiBulkAction']);
        Route::get('/{person}', [\App\Http\Controllers\Admin\PersonController::class, 'apiShow']);
        Route::put('/{person}', [\App\Http\Controllers\Admin\PersonController::class, 'apiUpdate']);
        Route::delete('/{person}', [\App\Http\Controllers\Admin\PersonController::class, 'apiDestroy']);
    });

    // Voice Actors API (static paths before /{voiceActor})
    Route::prefix('voice-actors')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VoiceActorController::class, 'apiIndex']);
        Route::post('/', [\App\Http\Controllers\Admin\VoiceActorController::class, 'apiStore']);
        Route::get('/export', [\App\Http\Controllers\Admin\VoiceActorController::class, 'apiExport']);
        Route::get('/statistics/data', [\App\Http\Controllers\Admin\VoiceActorController::class, 'apiStatistics']);
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\VoiceActorController::class, 'apiBulkAction']);
        Route::get('/{voiceActor}', [\App\Http\Controllers\Admin\VoiceActorController::class, 'apiShow']);
        Route::put('/{voiceActor}', [\App\Http\Controllers\Admin\VoiceActorController::class, 'apiUpdate']);
        Route::delete('/{voiceActor}', [\App\Http\Controllers\Admin\VoiceActorController::class, 'apiDestroy']);
    });

    // Specialized Dashboards API
    Route::prefix('specialized-dashboards')->group(function () {
        Route::get('/stories/export', [\App\Http\Controllers\Admin\StoriesDashboardController::class, 'apiExport']);
        Route::get('/stories', [\App\Http\Controllers\Admin\StoriesDashboardController::class, 'apiOverview']);
        Route::get('/stories/analytics', [\App\Http\Controllers\Admin\StoriesDashboardController::class, 'apiAnalytics']);
        Route::get('/partners/export', [\App\Http\Controllers\Admin\PartnersDashboardController::class, 'apiExport']);
        Route::get('/partners', [\App\Http\Controllers\Admin\PartnersDashboardController::class, 'apiOverview']);
        Route::get('/partners/analytics', [\App\Http\Controllers\Admin\PartnersDashboardController::class, 'apiAnalytics']);
        Route::get('/sales/export', [\App\Http\Controllers\Admin\SalesDashboardController::class, 'apiExport']);
        Route::get('/sales', [\App\Http\Controllers\Admin\SalesDashboardController::class, 'apiOverview']);
        Route::get('/sales/analytics', [\App\Http\Controllers\Admin\SalesDashboardController::class, 'apiAnalytics']);
    });

        // Story markdown editor API (filesystem source in manji-stories)
        Route::prefix('story-editor')->group(function () {
            Route::get('/resolve-slug', [\App\Http\Controllers\Admin\StoryEditorController::class, 'resolveSlug']);
            Route::post('/stories', [\App\Http\Controllers\Admin\StoryEditorController::class, 'storeStory']);
            Route::get('/stories', [\App\Http\Controllers\Admin\StoryEditorController::class, 'index']);
            Route::get('/stories/{storyId}/package', [\App\Http\Controllers\Admin\StoryEditorController::class, 'package']);
            Route::get('/stories/{storyId}/assets', [\App\Http\Controllers\Admin\StoryEditorController::class, 'assets']);
            Route::post('/stories/{storyId}/import', [\App\Http\Controllers\Admin\StoryEditorController::class, 'import']);
            Route::post('/stories/{storyId}/episodes', [\App\Http\Controllers\Admin\StoryEditorController::class, 'storeEpisode']);
            Route::post('/stories/{storyId}/episodes/{episodeId}/import', [\App\Http\Controllers\Admin\StoryEditorController::class, 'import']);
            Route::post('/stories/{storyId}/assets/{assetType}/{assetKey}/image', [\App\Http\Controllers\Admin\StoryEditorController::class, 'uploadAssetImage']);
            Route::get('/stories/{storyId}/episodes', [\App\Http\Controllers\Admin\StoryEditorController::class, 'episodes']);
            Route::get('/stories/{storyId}/episodes/{episodeId}', [\App\Http\Controllers\Admin\StoryEditorController::class, 'show']);
            Route::put('/stories/{storyId}/episodes/{episodeId}', [\App\Http\Controllers\Admin\StoryEditorController::class, 'update']);
        });

        // Story Management API (static paths before /{story})
        Route::prefix('stories')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\StoryController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\StoryController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\StoryController::class, 'apiExport']);
            Route::get('/export/json', [\App\Http\Controllers\Admin\StoryExportController::class, 'apiExportJson']);
            Route::post('/import/json', [\App\Http\Controllers\Admin\StoryExportController::class, 'apiImportJson']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\StoryController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\StoryController::class, 'apiBulkAction']);
            Route::get('/{story}', [\App\Http\Controllers\Admin\StoryController::class, 'apiShow']);
            Route::put('/{story}', [\App\Http\Controllers\Admin\StoryController::class, 'apiUpdate']);
            Route::put('/{story}/sponsor', [\App\Http\Controllers\Admin\StoryController::class, 'apiUpdateSponsor']);
            Route::delete('/{story}', [\App\Http\Controllers\Admin\StoryController::class, 'apiDestroy']);
            Route::post('/{story}/publish', [\App\Http\Controllers\Admin\StoryController::class, 'apiPublish']);
            Route::post('/{story}/duplicate', [\App\Http\Controllers\Admin\StoryController::class, 'apiDuplicate']);
        });

        // Episode Management API (static paths before /{episode})
        Route::prefix('episodes')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiIndex']);
            Route::post('/', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiStore']);
            Route::get('/export', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiBulkAction']);
            Route::post('/reorder', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiReorder']);
            Route::get('/{episode}', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiShow']);
            Route::put('/{episode}', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiUpdate']);
            Route::delete('/{episode}', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiDestroy']);
            Route::post('/{episode}/publish', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiPublish']);
            Route::post('/{episode}/duplicate', [\App\Http\Controllers\Admin\EpisodeController::class, 'apiDuplicate']);
        });

        // Dashboard API (legacy aliases — canonical paths are /api/admin/v1/dashboard/*)
        Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/dashboard/charts', [AdminDashboardController::class, 'charts']);
        Route::get('/dashboard/export', [AdminDashboardController::class, 'export']);
        Route::get('/online-users', [AdminDashboardController::class, 'onlineUsers']);

        // Flavor analytics
        Route::get('/flavor-analytics/export', [\App\Http\Controllers\Admin\FlavorAnalyticsController::class, 'apiExport']);
        Route::get('/flavor-analytics', [\App\Http\Controllers\Admin\FlavorAnalyticsController::class, 'api']);

        // Partner programs
        Route::prefix('teachers')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'teachersIndex']);
            Route::get('/export', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'teachersExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'teachersStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'teachersBulkAction']);
            Route::get('/{teacher}', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'teachersShow']);
        });
        Route::prefix('schools')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'schoolsIndex']);
            Route::get('/export', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'schoolsExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'schoolsStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'schoolsBulkAction']);
            Route::get('/{school}', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'schoolsShow']);
        });
        Route::prefix('influencers')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'influencersIndex']);
            Route::get('/export', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'influencersExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'influencersStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'influencersBulkAction']);
            Route::get('/{influencer}', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'influencersShow']);
        });
        Route::prefix('corporate')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'corporateIndex']);
            Route::get('/export', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'corporateExport']);
            Route::get('/statistics/data', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'corporateStatistics']);
            Route::post('/bulk-action', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'corporateBulkAction']);
            Route::get('/{corporate}', [\App\Http\Controllers\Admin\PartnerProgramsApiController::class, 'corporateShow']);
        });
    });

