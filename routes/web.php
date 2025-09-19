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
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ContentModerationController;
use App\Http\Controllers\Admin\UserAnalyticsController;
use App\Http\Controllers\Admin\ContentAnalyticsController;
use App\Http\Controllers\Admin\RevenueAnalyticsController;
use App\Http\Controllers\Admin\SystemAnalyticsController;
use App\Http\Controllers\PaymentCallbackController;

// Redirect root to admin login if not authenticated
Route::get('/', function () {
    if (auth('web')->check() && auth('web')->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('admin.auth.login');
})->name('home');

// Admin Authentication Routes
Route::prefix('admin/auth')->name('admin.auth.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Admin Dashboard Route
Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard')->middleware(['auth:web', 'admin']);

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth:web', 'admin'])->group(function () {
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
    
    // Timeline Management
    Route::get('episodes/{episode}/timeline', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'show'])->name('episodes.timeline');
    
    // Voice Actor Management
    Route::prefix('episodes/{episode}/voice-actors')->name('episodes.voice-actors.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'store'])->name('store');
        Route::get('/{voiceActor}/edit', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'edit'])->name('edit');
        Route::put('/{voiceActor}', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'update'])->name('update');
        Route::delete('/{voiceActor}', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'bulkAction'])->name('bulk-action');
    });
    
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
    
    // Timeline Management
    Route::get('timeline', function () {
        return view('admin.timeline.index');
    })->name('timeline.index');
    
    // Audio Management
    Route::get('audio', function () {
        return view('admin.audio.index');
    })->name('audio.index');
    
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
    
    // Admin Dashboard API Routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('dashboard/stats', [\App\Http\Controllers\Admin\DashboardController::class, 'stats'])->name('dashboard.stats');
        
        // Admin Image Timeline API routes
        Route::prefix('episodes/{episode}/timeline')->name('timeline.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'store'])->name('store');
            Route::put('/', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'update'])->name('update');
            Route::delete('/', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'destroy'])->name('destroy');
            Route::get('/statistics', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'statistics'])->name('statistics');
        });
        
        Route::post('timeline/validate', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'validate'])->name('timeline.validate');
        Route::post('timeline/optimize', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'optimize'])->name('timeline.optimize');
        Route::post('timeline/bulk-action', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'bulkAction'])->name('timeline.bulk-action');
        
        // Admin Voice Actor API routes
        Route::prefix('episodes/{episode}/voice-actors')->name('voice-actors.')->group(function () {
            Route::get('/data', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'getVoiceActorsData'])->name('data');
            Route::get('/statistics', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'getVoiceActorStatistics'])->name('statistics');
            Route::post('/validate-time-range', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'validateTimeRange'])->name('validate-time-range');
            Route::get('/available-people', [\App\Http\Controllers\Admin\EpisodeVoiceActorController::class, 'getAvailablePeople'])->name('available-people');
        });
        
        // Admin Audio Processing API routes
        Route::prefix('audio')->name('audio.')->group(function () {
            Route::post('process', [\App\Http\Controllers\Api\AudioProcessingController::class, 'processAudio'])->name('process');
            Route::post('extract-metadata', [\App\Http\Controllers\Api\AudioProcessingController::class, 'extractMetadata'])->name('extract-metadata');
            Route::post('convert', [\App\Http\Controllers\Api\AudioProcessingController::class, 'convertFormat'])->name('convert');
            Route::post('normalize', [\App\Http\Controllers\Api\AudioProcessingController::class, 'normalizeAudio'])->name('normalize');
            Route::post('trim', [\App\Http\Controllers\Api\AudioProcessingController::class, 'trimAudio'])->name('trim');
            Route::post('validate', [\App\Http\Controllers\Api\AudioProcessingController::class, 'validateAudio'])->name('validate');
            Route::get('stats', [\App\Http\Controllers\Api\AudioProcessingController::class, 'getStats'])->name('stats');
            Route::post('cleanup', [\App\Http\Controllers\Api\AudioProcessingController::class, 'cleanup'])->name('cleanup');
        });
        
        // Admin Image Processing API routes
        Route::prefix('image')->name('image.')->group(function () {
            Route::post('process', [\App\Http\Controllers\Api\ImageProcessingController::class, 'processImage'])->name('process');
            Route::post('resize', [\App\Http\Controllers\Api\ImageProcessingController::class, 'resizeImage'])->name('resize');
            Route::post('crop', [\App\Http\Controllers\Api\ImageProcessingController::class, 'cropImage'])->name('crop');
            Route::post('watermark', [\App\Http\Controllers\Api\ImageProcessingController::class, 'addWatermark'])->name('watermark');
            Route::post('optimize', [\App\Http\Controllers\Api\ImageProcessingController::class, 'optimizeImage'])->name('optimize');
            Route::post('thumbnail', [\App\Http\Controllers\Api\ImageProcessingController::class, 'generateThumbnail'])->name('thumbnail');
            Route::post('multiple-sizes', [\App\Http\Controllers\Api\ImageProcessingController::class, 'generateMultipleSizes'])->name('multiple-sizes');
            Route::get('info', [\App\Http\Controllers\Api\ImageProcessingController::class, 'getImageInfo'])->name('info');
            Route::post('validate', [\App\Http\Controllers\Api\ImageProcessingController::class, 'validateImage'])->name('validate');
            Route::get('stats', [\App\Http\Controllers\Api\ImageProcessingController::class, 'getStats'])->name('stats');
            Route::post('cleanup', [\App\Http\Controllers\Api\ImageProcessingController::class, 'cleanup'])->name('cleanup');
        });
        
        // Admin Audio Management API routes
        Route::prefix('audio-management')->name('audio-management.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AudioManagementController::class, 'index'])->name('index');
            Route::post('upload', [\App\Http\Controllers\Admin\AudioManagementController::class, 'uploadAudio'])->name('upload');
            Route::get('stats', [\App\Http\Controllers\Admin\AudioManagementController::class, 'getStats'])->name('stats');
            Route::post('bulk-operation', [\App\Http\Controllers\Admin\AudioManagementController::class, 'bulkOperation'])->name('bulk-operation');
        });
        
        // Admin Performance Monitoring API routes
        Route::prefix('performance')->name('performance.')->group(function () {
            Route::get('dashboard', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'dashboard'])->name('dashboard');
            Route::get('statistics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'statistics'])->name('statistics');
            Route::get('real-time', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'realTime'])->name('real-time');
            Route::get('report', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'report'])->name('report');
            Route::post('cleanup', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'cleanup'])->name('cleanup');
            Route::get('alerts', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'alerts'])->name('alerts');
            Route::get('timeline-metrics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'timelineMetrics'])->name('timeline-metrics');
            Route::get('comment-metrics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'commentMetrics'])->name('comment-metrics');
        });
        
        // Admin Backup and Recovery API routes
        Route::prefix('backup')->name('backup.')->group(function () {
            Route::post('create-full', [\App\Http\Controllers\Admin\BackupController::class, 'createFullBackup'])->name('create-full');
            Route::post('create-incremental', [\App\Http\Controllers\Admin\BackupController::class, 'createIncrementalBackup'])->name('create-incremental');
            Route::get('list', [\App\Http\Controllers\Admin\BackupController::class, 'listBackups'])->name('list');
            Route::post('restore', [\App\Http\Controllers\Admin\BackupController::class, 'restoreBackup'])->name('restore');
            Route::get('download/{backupId}', [\App\Http\Controllers\Admin\BackupController::class, 'downloadBackup'])->name('download');
            Route::delete('{backupId}', [\App\Http\Controllers\Admin\BackupController::class, 'deleteBackup'])->name('delete');
            Route::post('cleanup', [\App\Http\Controllers\Admin\BackupController::class, 'cleanupOldBackups'])->name('cleanup');
            Route::get('stats', [\App\Http\Controllers\Admin\BackupController::class, 'getBackupStats'])->name('stats');
            Route::post('schedule', [\App\Http\Controllers\Admin\BackupController::class, 'scheduleBackups'])->name('schedule');
            Route::get('schedule', [\App\Http\Controllers\Admin\BackupController::class, 'getBackupSchedule'])->name('schedule');
        });
        
        // Admin In-App Notifications API routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::post('create', [\App\Http\Controllers\Api\InAppNotificationController::class, 'create'])->name('create');
            Route::post('send-multiple', [\App\Http\Controllers\Api\InAppNotificationController::class, 'sendToMultiple'])->name('send-multiple');
            Route::get('statistics', [\App\Http\Controllers\Api\InAppNotificationController::class, 'statistics'])->name('statistics');
            Route::post('cleanup-expired', [\App\Http\Controllers\Api\InAppNotificationController::class, 'cleanupExpired'])->name('cleanup-expired');
        });
        
        // Admin Subscriptions API routes
        Route::get('subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('subscriptions');
    });

    // Profile Routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::put('/info', [ProfileController::class, 'updateInfo'])->name('info.update');
    });
});

// Payment Callback Routes
Route::prefix('payment')->group(function () {
    Route::get('zarinpal/callback', [PaymentCallbackController::class, 'zarinpalCallback']);
    Route::get('success', [PaymentCallbackController::class, 'success'])->name('payment.success');
    Route::get('failure', [PaymentCallbackController::class, 'failure'])->name('payment.failure');
});
