<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EpisodeController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ContentModerationController;
use App\Http\Controllers\Admin\UserAnalyticsController;
use App\Http\Controllers\Admin\ContentAnalyticsController;
use App\Http\Controllers\Admin\RevenueAnalyticsController;
use App\Http\Controllers\Admin\SystemAnalyticsController;
use App\Http\Controllers\PaymentCallbackController;

Route::get('/', function () {
    return view('welcome');
});

// Admin Authentication Routes
Route::prefix('admin/auth')->name('admin.auth.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Stories
    Route::resource('stories', StoryController::class);
    Route::post('stories/bulk-action', [StoryController::class, 'bulkAction'])->name('stories.bulk-action');
    Route::post('stories/{story}/duplicate', [StoryController::class, 'duplicate'])->name('stories.duplicate');
    Route::get('stories/export', [StoryController::class, 'export'])->name('stories.export');
    Route::get('stories/statistics', [StoryController::class, 'statistics'])->name('stories.statistics');
    Route::post('stories/{story}/publish', [StoryController::class, 'publish'])->name('stories.publish');
    
    // Episodes
    Route::resource('episodes', EpisodeController::class);
    Route::post('episodes/bulk-action', [EpisodeController::class, 'bulkAction'])->name('episodes.bulk-action');
    Route::post('episodes/{episode}/duplicate', [EpisodeController::class, 'duplicate'])->name('episodes.duplicate');
    Route::get('episodes/export', [EpisodeController::class, 'export'])->name('episodes.export');
    Route::get('episodes/statistics', [EpisodeController::class, 'statistics'])->name('episodes.statistics');
    Route::post('episodes/{episode}/publish', [EpisodeController::class, 'publish'])->name('episodes.publish');
    Route::post('stories/{story}/episodes/reorder', [EpisodeController::class, 'reorder'])->name('stories.episodes.reorder');
    
    // Categories
    Route::resource('categories', CategoryController::class);
    
    // People
    Route::resource('people', PersonController::class);
    
    // Users
    Route::resource('users', UserController::class);
    Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('users/statistics', [UserController::class, 'statistics'])->name('users.statistics');
    Route::get('users/{user}/activity', [UserController::class, 'activity'])->name('users.activity');
    Route::post('users/{user}/send-notification', [UserController::class, 'sendNotification'])->name('users.send-notification');
    Route::get('users/{user}/profile', [UserController::class, 'profile'])->name('users.profile');
    
    // File Management
    Route::get('files/upload', function () {
        return view('admin.files.upload');
    })->name('files.upload');
    
    // Notifications
    Route::resource('notifications', NotificationController::class);
    Route::post('notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('users/{user}/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('users.notifications.mark-all-read');
    Route::post('notifications/send-test', [NotificationController::class, 'sendTest'])->name('notifications.send-test');
    Route::get('notifications/statistics', [NotificationController::class, 'statistics'])->name('notifications.statistics');
    
    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('analytics/data', [AnalyticsController::class, 'getAnalyticsData'])->name('analytics.data');
    
    // User Analytics
    Route::prefix('user-analytics')->name('user-analytics.')->group(function () {
        Route::get('/', [UserAnalyticsController::class, 'index'])->name('index');
        Route::get('overview', [UserAnalyticsController::class, 'overview'])->name('overview');
        Route::get('registration', [UserAnalyticsController::class, 'registration'])->name('registration');
        Route::get('engagement', [UserAnalyticsController::class, 'engagement'])->name('engagement');
        Route::get('subscription', [UserAnalyticsController::class, 'subscription'])->name('subscription');
        Route::get('activity', [UserAnalyticsController::class, 'activity'])->name('activity');
        Route::get('retention', [UserAnalyticsController::class, 'retention'])->name('retention');
        Route::get('demographics', [UserAnalyticsController::class, 'demographics'])->name('demographics');
        Route::get('behavior', [UserAnalyticsController::class, 'behavior'])->name('behavior');
        Route::get('trends', [UserAnalyticsController::class, 'trends'])->name('trends');
        Route::get('segments', [UserAnalyticsController::class, 'segments'])->name('segments');
        Route::get('real-time', [UserAnalyticsController::class, 'realTime'])->name('real-time');
        Route::get('export', [UserAnalyticsController::class, 'export'])->name('export');
        Route::get('summary', [UserAnalyticsController::class, 'summary'])->name('summary');
    });
    
    // Content Analytics
    Route::prefix('content-analytics')->name('content-analytics.')->group(function () {
        Route::get('/', [ContentAnalyticsController::class, 'index'])->name('index');
        Route::get('overview', [ContentAnalyticsController::class, 'overview'])->name('overview');
        Route::get('performance', [ContentAnalyticsController::class, 'performance'])->name('performance');
        Route::get('popularity', [ContentAnalyticsController::class, 'popularity'])->name('popularity');
        Route::get('engagement', [ContentAnalyticsController::class, 'engagement'])->name('engagement');
        Route::get('trends', [ContentAnalyticsController::class, 'trends'])->name('trends');
        Route::get('categories', [ContentAnalyticsController::class, 'categories'])->name('categories');
        Route::get('creators', [ContentAnalyticsController::class, 'creators'])->name('creators');
        Route::get('export', [ContentAnalyticsController::class, 'export'])->name('export');
        Route::get('summary', [ContentAnalyticsController::class, 'summary'])->name('summary');
    });
    
    // Revenue Analytics
    Route::prefix('revenue-analytics')->name('revenue-analytics.')->group(function () {
        Route::get('/', [RevenueAnalyticsController::class, 'index'])->name('index');
        Route::get('overview', [RevenueAnalyticsController::class, 'overview'])->name('overview');
        Route::get('subscriptions', [RevenueAnalyticsController::class, 'subscriptions'])->name('subscriptions');
        Route::get('payments', [RevenueAnalyticsController::class, 'payments'])->name('payments');
        Route::get('trends', [RevenueAnalyticsController::class, 'trends'])->name('trends');
        Route::get('customers', [RevenueAnalyticsController::class, 'customers'])->name('customers');
        Route::get('conversions', [RevenueAnalyticsController::class, 'conversions'])->name('conversions');
        Route::get('export', [RevenueAnalyticsController::class, 'export'])->name('export');
        Route::get('summary', [RevenueAnalyticsController::class, 'summary'])->name('summary');
    });
    
    // System Analytics
    Route::prefix('system-analytics')->name('system-analytics.')->group(function () {
        Route::get('/', [SystemAnalyticsController::class, 'index'])->name('index');
        Route::get('overview', [SystemAnalyticsController::class, 'overview'])->name('overview');
        Route::get('api-performance', [SystemAnalyticsController::class, 'apiPerformance'])->name('api-performance');
        Route::get('database-performance', [SystemAnalyticsController::class, 'databasePerformance'])->name('database-performance');
        Route::get('server-health', [SystemAnalyticsController::class, 'serverHealth'])->name('server-health');
        Route::get('error-tracking', [SystemAnalyticsController::class, 'errorTracking'])->name('error-tracking');
        Route::get('resource-usage', [SystemAnalyticsController::class, 'resourceUsage'])->name('resource-usage');
        Route::get('uptime-monitoring', [SystemAnalyticsController::class, 'uptimeMonitoring'])->name('uptime-monitoring');
        Route::get('security', [SystemAnalyticsController::class, 'security'])->name('security');
        Route::get('export', [SystemAnalyticsController::class, 'export'])->name('export');
        Route::get('summary', [SystemAnalyticsController::class, 'summary'])->name('summary');
        Route::get('real-time', [SystemAnalyticsController::class, 'realTime'])->name('real-time');
        Route::get('health-check', [SystemAnalyticsController::class, 'healthCheck'])->name('health-check');
    });
    
    // Content Moderation
    Route::prefix('moderation')->name('moderation.')->group(function () {
        Route::get('/', [ContentModerationController::class, 'index'])->name('index');
        Route::get('{story}', [ContentModerationController::class, 'show'])->name('show');
        Route::post('{story}/approve', [ContentModerationController::class, 'approve'])->name('approve');
        Route::post('{story}/reject', [ContentModerationController::class, 'reject'])->name('reject');
        Route::post('{story}/flag', [ContentModerationController::class, 'flag'])->name('flag');
        Route::post('bulk-action', [ContentModerationController::class, 'bulkAction'])->name('bulk-action');
        Route::get('statistics', [ContentModerationController::class, 'statistics'])->name('statistics');
        Route::get('reports', [ContentModerationController::class, 'reports'])->name('reports');
        Route::post('reports/{report}/resolve', [ContentModerationController::class, 'resolveReport'])->name('reports.resolve');
    });
});

// Payment Callback Routes
Route::prefix('payment')->group(function () {
    Route::get('zarinpal/callback', [PaymentCallbackController::class, 'zarinpalCallback']);
    Route::get('success', [PaymentCallbackController::class, 'success'])->name('payment.success');
    Route::get('failure', [PaymentCallbackController::class, 'failure'])->name('payment.failure');
});
