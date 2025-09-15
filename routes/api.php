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

    // File upload routes
    Route::prefix('files')->group(function () {
        Route::post('upload/image', [FileUploadController::class, 'uploadImage']);
        Route::post('upload/audio', [FileUploadController::class, 'uploadAudio']);
        Route::post('upload/file', [FileUploadController::class, 'uploadFile']);
        Route::delete('delete', [FileUploadController::class, 'deleteFile']);
        Route::get('info', [FileUploadController::class, 'getFileInfo']);
        Route::get('config', [FileUploadController::class, 'getStorageConfig']);
    });

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
    });
});

// Admin routes
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    
    Route::get('dashboard/stats', [\App\Http\Controllers\Admin\DashboardController::class, 'stats']);
    
    Route::apiResource('stories', \App\Http\Controllers\Admin\StoryController::class);
    Route::apiResource('episodes', \App\Http\Controllers\Admin\EpisodeController::class);
    Route::apiResource('categories', \App\Http\Controllers\Admin\CategoryController::class);
    Route::apiResource('users', \App\Http\Controllers\Admin\UserController::class);
    
    Route::get('subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index']);
    Route::get('analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index']);
});
