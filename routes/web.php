<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EpisodeController;
use App\Http\Controllers\Admin\EpisodeTimelineController;
use App\Http\Controllers\Admin\TimelineManagementController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ContentModerationController;
use App\Http\Controllers\Admin\UserAnalyticsController;
use App\Http\Controllers\Admin\ContentAnalyticsController;
use App\Http\Controllers\Admin\RevenueAnalyticsController;
use App\Http\Controllers\Admin\SystemAnalyticsController;
use App\Http\Controllers\Admin\AppVersionController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\Admin\StoryExportController;
use App\Http\Controllers\AudioController;

// Redirect root to admin login if not authenticated
Route::get('/', function () {
    if (auth('web')->check() && auth('web')->user()->role === 'admin') {
        return redirect()->route('admin.stories.index');
    }
    return redirect()->route('admin.auth.login');
})->name('home');

// Fallback login route expected by Laravel's auth middleware
// Redirects to the user login form (not the admin panel).
Route::get('/login', [UserAuthController::class, 'showLoginForm'])->name('login');

// User authentication (web UI) – phone + SMS code
Route::get('/user/login', [UserAuthController::class, 'showLoginForm'])->name('user.login');
Route::post('/user/login', [UserAuthController::class, 'login'])->name('user.login.post');

// Public checkout page for subscriptions/payments
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
});

// Public audio file serving route (for episodes)
// This ensures audio files are served with proper MIME types and headers
Route::get('/audio/episodes/{path}', [AudioController::class, 'serve'])
    ->where('path', '.*')
    ->name('audio.episodes.serve');

// Admin Authentication Routes
Route::prefix('admin/auth')->name('admin.auth.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('send-otp', [AdminAuthController::class, 'sendOtp'])->name('send-otp');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Admin 2FA Routes
Route::prefix('admin/2fa')->name('admin.2fa.')->middleware(['auth:web', 'admin'])->group(function () {
    Route::get('verify', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'showVerifyForm'])->name('verify');
    Route::post('verify', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'verify'])->name('verify.post');
    Route::post('send-code', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'sendCode'])->name('send-code');
    Route::post('skip', [\App\Http\Controllers\Admin\TwoFactorAuthController::class, 'skip'])->name('skip');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth:web', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/search', [DashboardController::class, 'globalSearch'])->name('search');

    // Dashboard Routes
    Route::prefix('dashboards')->name('dashboards.')->group(function () {
        Route::get('/stories', [\App\Http\Controllers\Admin\StoriesDashboardController::class, 'index'])->name('stories');
        Route::get('/stories/analytics', [\App\Http\Controllers\Admin\StoriesDashboardController::class, 'analytics'])->name('stories.analytics');
        Route::get('/stories/export', [\App\Http\Controllers\Admin\StoriesDashboardController::class, 'export'])->name('stories.export');

        Route::get('/partners', [\App\Http\Controllers\Admin\PartnersDashboardController::class, 'index'])->name('partners');
        Route::get('/partners/analytics', [\App\Http\Controllers\Admin\PartnersDashboardController::class, 'analytics'])->name('partners.analytics');
        Route::get('/partners/export', [\App\Http\Controllers\Admin\PartnersDashboardController::class, 'export'])->name('partners.export');

        Route::get('/sales', [\App\Http\Controllers\Admin\SalesDashboardController::class, 'index'])->name('sales');
        Route::get('/sales/analytics', [\App\Http\Controllers\Admin\SalesDashboardController::class, 'analytics'])->name('sales.analytics');
        Route::get('/sales/export', [\App\Http\Controllers\Admin\SalesDashboardController::class, 'export'])->name('sales.export');
    });

    // Stories
    Route::resource('stories', StoryController::class);
    Route::post('stories/bulk-action', [StoryController::class, 'bulkAction'])->name('stories.bulk-action');
    Route::post('stories/{story}/duplicate', [StoryController::class, 'duplicate'])->name('stories.duplicate');
    Route::get('stories/export', [StoryController::class, 'export'])->name('stories.export');
    Route::get('stories/statistics', [StoryController::class, 'statistics'])->name('stories.statistics');
    Route::post('stories/{story}/publish', [StoryController::class, 'publish'])->name('stories.publish');

    // Story Timeline Management Routes
    Route::prefix('stories/{story}/timeline')->name('stories.timeline.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\StoryTimelineController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\StoryTimelineController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\StoryTimelineController::class, 'store'])->name('store');
        Route::get('/{timeline}/edit', [\App\Http\Controllers\Admin\StoryTimelineController::class, 'edit'])->name('edit');
        Route::put('/{timeline}', [\App\Http\Controllers\Admin\StoryTimelineController::class, 'update'])->name('update');
        Route::delete('/{timeline}', [\App\Http\Controllers\Admin\StoryTimelineController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\StoryTimelineController::class, 'bulkAction'])->name('bulk-action');
    });

    // Episodes
    Route::resource('episodes', EpisodeController::class);
    Route::post('episodes/bulk-action', [EpisodeController::class, 'bulkAction'])->name('episodes.bulk-action');
    Route::post('episodes/{episode}/duplicate', [EpisodeController::class, 'duplicate'])->name('episodes.duplicate');
    Route::get('episodes/export', [EpisodeController::class, 'export'])->name('episodes.export');
    Route::get('episodes/statistics', [EpisodeController::class, 'statistics'])->name('episodes.statistics');
    Route::post('episodes/{episode}/publish', [EpisodeController::class, 'publish'])->name('episodes.publish');
    Route::post('stories/{story}/episodes/reorder', [EpisodeController::class, 'reorder'])->name('stories.episodes.reorder');

    // Episode Timeline Management Routes
    Route::prefix('episodes/{episode}/timeline')->name('episodes.timeline.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'show'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'store'])->name('store');
        Route::get('/{timeline}/edit', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'edit'])->name('edit');
        Route::put('/{timeline}', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'update'])->name('update');
        Route::delete('/{timeline}', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'bulkAction'])->name('bulk-action');
    });

    // Global Timeline Management Routes
    Route::prefix('timelines')->name('timelines.')->group(function () {
        Route::get('/', [TimelineManagementController::class, 'index'])->name('index');
        Route::get('/statistics', [TimelineManagementController::class, 'statistics'])->name('statistics');
        Route::post('/bulk-action', [TimelineManagementController::class, 'bulkAction'])->name('bulk-action');
    });

    // Comments Management
    Route::prefix('comments')->name('comments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'index'])->name('index');
        Route::get('/pending', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'pending'])->name('pending');
        Route::get('/approved', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'approved'])->name('approved');
        Route::get('/rejected', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'rejected'])->name('rejected');
        Route::get('/{comment}', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'show'])->name('show');
        Route::post('/{comment}/approve', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'approve'])->name('approve');
        Route::post('/{comment}/reject', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'reject'])->name('reject');
        Route::post('/{comment}/pin', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'pin'])->name('pin');
        Route::post('/{comment}/unpin', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'unpin'])->name('unpin');
        Route::delete('/{comment}', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/statistics', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'statistics'])->name('statistics');
        Route::get('/export', [\App\Http\Controllers\Admin\CommentsManagementController::class, 'export'])->name('export');
    });

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
    Route::get('users/search', [UserController::class, 'search'])->name('users.search');
    Route::resource('users', UserController::class);
    Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::post('users/{user}/change-role', [UserController::class, 'changeRole'])->name('users.change-role');
    Route::post('users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('users/statistics', [UserController::class, 'statistics'])->name('users.statistics');
    Route::get('users/{user}/activity', [UserController::class, 'activity'])->name('users.activity');
    Route::post('users/{user}/send-notification', [UserController::class, 'sendNotification'])->name('users.send-notification');
    Route::get('users/{user}/profile', [UserController::class, 'profile'])->name('users.profile');

    // Voice Actors Management
    Route::prefix('voice-actors')->name('voice-actors.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VoiceActorsManagementController::class, 'index'])->name('index');
        Route::get('/{voiceActor}', [\App\Http\Controllers\Admin\VoiceActorsManagementController::class, 'show'])->name('show');
        Route::get('/{voiceActor}/edit', [\App\Http\Controllers\Admin\VoiceActorsManagementController::class, 'edit'])->name('edit');
        Route::put('/{voiceActor}', [\App\Http\Controllers\Admin\VoiceActorsManagementController::class, 'update'])->name('update');
        Route::post('/{voiceActor}/change-role', [\App\Http\Controllers\Admin\VoiceActorsManagementController::class, 'updateRole'])->name('change-role');
    });

    // File Management
    Route::get('files/upload', function () {
        return view('admin.files.upload');
    })->name('files.upload');


    // Audio Management
    Route::get('audio', function () {
        return view('admin.audio.index');
    })->name('audio.index');

    // Notifications
    Route::resource('notifications', NotificationController::class);
    Route::post('notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('users/{user}/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('users.notifications.mark-all-read');
    Route::post('notifications/send-test', [NotificationController::class, 'sendTest'])->name('notifications.send-test');

    // App Versions
    Route::resource('app-versions', AppVersionController::class);
    Route::post('app-versions/{appVersion}/toggle-active', [AppVersionController::class, 'toggleActive'])->name('app-versions.toggle-active');
    Route::post('app-versions/{appVersion}/set-latest', [AppVersionController::class, 'setLatest'])->name('app-versions.set-latest');
    Route::get('notifications/statistics', [NotificationController::class, 'statistics'])->name('notifications.statistics');

    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('analytics/data', [AnalyticsController::class, 'getAnalyticsData'])->name('analytics.data');

    // Coin Analytics
    Route::get('analytics/coin', function () {
        return view('admin.analytics.coin-analytics');
    })->name('analytics.coin');

    // Referral Analytics
    Route::get('analytics/referral', function () {
        return view('admin.analytics.referral-analytics');
    })->name('analytics.referral');

    // Role Management
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\RoleController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [\App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('destroy');
        Route::post('/assign', [\App\Http\Controllers\Admin\RoleController::class, 'assign'])->name('assign');
    });

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

        Route::post('timeline/validate', [\App\Http\Controllers\Admin\ImageTimelineController::class, 'validateTimeline'])->name('timeline.validate');
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
            Route::post('schedule', [\App\Http\Controllers\Admin\BackupController::class, 'scheduleBackups'])->name('schedule.create');
            Route::get('schedule', [\App\Http\Controllers\Admin\BackupController::class, 'getBackupSchedule'])->name('schedule.get');
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

    // Subscription Management Routes
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('index');
        Route::get('/plans', [\App\Http\Controllers\Admin\SubscriptionController::class, 'plans'])->name('plans');
        Route::get('/analytics', [\App\Http\Controllers\Admin\SubscriptionController::class, 'analytics'])->name('analytics');
        Route::get('/export', [\App\Http\Controllers\Admin\SubscriptionController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\SubscriptionController::class, 'statistics'])->name('statistics');
        Route::get('/{subscription}', [\App\Http\Controllers\Admin\SubscriptionController::class, 'show'])->name('show');
        Route::post('/{subscription}/cancel', [\App\Http\Controllers\Admin\SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/reactivate', [\App\Http\Controllers\Admin\SubscriptionController::class, 'reactivate'])->name('reactivate');
    });

    // Plan Management Routes
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PlanController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\PlanController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\PlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [\App\Http\Controllers\Admin\PlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [\App\Http\Controllers\Admin\PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [\App\Http\Controllers\Admin\PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [\App\Http\Controllers\Admin\PlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle-status', [\App\Http\Controllers\Admin\PlanController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Teacher Account Management Routes
    Route::prefix('teachers')->name('teachers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TeacherController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\TeacherController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\TeacherController::class, 'store'])->name('store');
        Route::get('/{teacher}', [\App\Http\Controllers\Admin\TeacherController::class, 'show'])->name('show');
        Route::get('/{teacher}/edit', [\App\Http\Controllers\Admin\TeacherController::class, 'edit'])->name('edit');
        Route::put('/{teacher}', [\App\Http\Controllers\Admin\TeacherController::class, 'update'])->name('update');
        Route::delete('/{teacher}', [\App\Http\Controllers\Admin\TeacherController::class, 'destroy'])->name('destroy');
        Route::post('/{teacher}/verify', [\App\Http\Controllers\Admin\TeacherController::class, 'verify'])->name('verify');
        Route::post('/{teacher}/suspend', [\App\Http\Controllers\Admin\TeacherController::class, 'suspend'])->name('suspend');
        Route::post('/{teacher}/activate', [\App\Http\Controllers\Admin\TeacherController::class, 'activate'])->name('activate');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\TeacherController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\TeacherController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\TeacherController::class, 'statistics'])->name('statistics');
    });

    // Influencer Campaign Management Routes
    Route::prefix('influencers')->name('influencers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\InfluencerController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\InfluencerController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\InfluencerController::class, 'store'])->name('store');
        Route::get('/{influencer}', [\App\Http\Controllers\Admin\InfluencerController::class, 'show'])->name('show');
        Route::get('/{influencer}/edit', [\App\Http\Controllers\Admin\InfluencerController::class, 'edit'])->name('edit');
        Route::put('/{influencer}', [\App\Http\Controllers\Admin\InfluencerController::class, 'update'])->name('update');
        Route::delete('/{influencer}', [\App\Http\Controllers\Admin\InfluencerController::class, 'destroy'])->name('destroy');
        Route::post('/{influencer}/verify', [\App\Http\Controllers\Admin\InfluencerController::class, 'verify'])->name('verify');
        Route::post('/{influencer}/suspend', [\App\Http\Controllers\Admin\InfluencerController::class, 'suspend'])->name('suspend');
        Route::post('/{influencer}/activate', [\App\Http\Controllers\Admin\InfluencerController::class, 'activate'])->name('activate');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\InfluencerController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\InfluencerController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\InfluencerController::class, 'statistics'])->name('statistics');
    });

    // School Partnership Management Routes
    Route::prefix('schools')->name('schools.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SchoolController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\SchoolController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\SchoolController::class, 'store'])->name('store');
        Route::get('/{school}', [\App\Http\Controllers\Admin\SchoolController::class, 'show'])->name('show');
        Route::get('/{school}/edit', [\App\Http\Controllers\Admin\SchoolController::class, 'edit'])->name('edit');
        Route::put('/{school}', [\App\Http\Controllers\Admin\SchoolController::class, 'update'])->name('update');
        Route::delete('/{school}', [\App\Http\Controllers\Admin\SchoolController::class, 'destroy'])->name('destroy');
        Route::post('/{school}/verify', [\App\Http\Controllers\Admin\SchoolController::class, 'verify'])->name('verify');
        Route::post('/{school}/suspend', [\App\Http\Controllers\Admin\SchoolController::class, 'suspend'])->name('suspend');
        Route::post('/{school}/activate', [\App\Http\Controllers\Admin\SchoolController::class, 'activate'])->name('activate');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\SchoolController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\SchoolController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\SchoolController::class, 'statistics'])->name('statistics');

        // Teacher assignment routes
        Route::post('/{school}/assign-teacher', [\App\Http\Controllers\Admin\SchoolController::class, 'assignTeacher'])->name('assign-teacher');
        Route::delete('/{school}/remove-teacher', [\App\Http\Controllers\Admin\SchoolController::class, 'removeTeacherAssignment'])->name('remove-teacher');
        Route::get('/{school}/teacher-assignment', [\App\Http\Controllers\Admin\SchoolController::class, 'getTeacherAssignment'])->name('teacher-assignment');
    });

    // Corporate Sponsorship Management Routes
    Route::prefix('corporate')->name('corporate.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CorporateController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\CorporateController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\CorporateController::class, 'store'])->name('store');
        Route::get('/{corporate}', [\App\Http\Controllers\Admin\CorporateController::class, 'show'])->name('show');
        Route::get('/{corporate}/edit', [\App\Http\Controllers\Admin\CorporateController::class, 'edit'])->name('edit');
        Route::put('/{corporate}', [\App\Http\Controllers\Admin\CorporateController::class, 'update'])->name('update');
        Route::delete('/{corporate}', [\App\Http\Controllers\Admin\CorporateController::class, 'destroy'])->name('destroy');
        Route::post('/{corporate}/verify', [\App\Http\Controllers\Admin\CorporateController::class, 'verify'])->name('verify');
        Route::post('/{corporate}/suspend', [\App\Http\Controllers\Admin\CorporateController::class, 'suspend'])->name('suspend');
        Route::post('/{corporate}/activate', [\App\Http\Controllers\Admin\CorporateController::class, 'activate'])->name('activate');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CorporateController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\CorporateController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\CorporateController::class, 'statistics'])->name('statistics');
    });

    // Quiz System Management Routes
    Route::prefix('quiz')->name('quiz.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\QuizController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\QuizController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\QuizController::class, 'store'])->name('store');
        Route::get('/{quiz}', [\App\Http\Controllers\Admin\QuizController::class, 'show'])->name('show');
        Route::get('/{quiz}/edit', [\App\Http\Controllers\Admin\QuizController::class, 'edit'])->name('edit');
        Route::put('/{quiz}', [\App\Http\Controllers\Admin\QuizController::class, 'update'])->name('update');
        Route::delete('/{quiz}', [\App\Http\Controllers\Admin\QuizController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\QuizController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\QuizController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\QuizController::class, 'statistics'])->name('statistics');
    });

    // Referral System Management Routes
    Route::prefix('referrals')->name('referrals.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReferralController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\ReferralController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\ReferralController::class, 'store'])->name('store');
        Route::get('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'show'])->name('show');
        Route::get('/{referral}/edit', [\App\Http\Controllers\Admin\ReferralController::class, 'edit'])->name('edit');
        Route::put('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'update'])->name('update');
        Route::delete('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\ReferralController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\ReferralController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\ReferralController::class, 'statistics'])->name('statistics');
    });

    // Gamification System Management Routes - DISABLED
    // Route::prefix('gamification')->name('gamification.')->group(function () {
    //     Route::get('/', [\App\Http\Controllers\Admin\GamificationController::class, 'index'])->name('index');
    //     Route::get('/create', [\App\Http\Controllers\Admin\GamificationController::class, 'create'])->name('create');
    //     Route::post('/', [\App\Http\Controllers\Admin\GamificationController::class, 'store'])->name('store');
    //     Route::get('/{gamification}', [\App\Http\Controllers\Admin\GamificationController::class, 'show'])->name('show');
    //     Route::get('/{gamification}/edit', [\App\Http\Controllers\Admin\GamificationController::class, 'edit'])->name('edit');
    //     Route::put('/{gamification}', [\App\Http\Controllers\Admin\GamificationController::class, 'update'])->name('update');
    //     Route::delete('/{gamification}', [\App\Http\Controllers\Admin\GamificationController::class, 'destroy'])->name('destroy');
    //     Route::post('/bulk-action', [\App\Http\Controllers\Admin\GamificationController::class, 'bulkAction'])->name('bulk-action');
    //     Route::get('/export', [\App\Http\Controllers\Admin\GamificationController::class, 'export'])->name('export');
    //     Route::get('/statistics', [\App\Http\Controllers\Admin\GamificationController::class, 'statistics'])->name('statistics');
    // });

    // Backup and Recovery Management Routes
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
        Route::post('/create-full', [\App\Http\Controllers\Admin\BackupController::class, 'createFullBackup'])->name('create-full');
        Route::post('/create-incremental', [\App\Http\Controllers\Admin\BackupController::class, 'createIncrementalBackup'])->name('create-incremental');
        Route::post('/restore', [\App\Http\Controllers\Admin\BackupController::class, 'restoreBackup'])->name('restore');
        Route::get('/download/{backupId}', [\App\Http\Controllers\Admin\BackupController::class, 'downloadBackup'])->name('download');
        Route::delete('/{backupId}', [\App\Http\Controllers\Admin\BackupController::class, 'deleteBackup'])->name('delete');
        Route::post('/cleanup', [\App\Http\Controllers\Admin\BackupController::class, 'cleanupOldBackups'])->name('cleanup');
        Route::get('/statistics', [\App\Http\Controllers\Admin\BackupController::class, 'getBackupStats'])->name('statistics');
        Route::post('/schedule', [\App\Http\Controllers\Admin\BackupController::class, 'scheduleBackups'])->name('schedule');
    });

    // Performance Monitoring Management Routes
    Route::prefix('performance')->name('performance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'index'])->name('index');
        Route::get('/dashboard', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'dashboard'])->name('dashboard');
        Route::get('/statistics', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'statistics'])->name('statistics');
        Route::get('/real-time', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'realTime'])->name('real-time');
        Route::get('/report', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'report'])->name('report');
        Route::post('/cleanup', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'cleanup'])->name('cleanup');
        Route::get('/alerts', [\App\Http\Controllers\Admin\PerformanceMonitoringController::class, 'alerts'])->name('alerts');
    });


    // User Permission Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::post('/{user}/permissions/{permission}', [\App\Http\Controllers\Admin\UserController::class, 'togglePermission'])->name('toggle-permission');
    });

    // Audio Management Routes
    Route::prefix('audio-management')->name('audio-management.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AudioManagementController::class, 'index'])->name('index');
        Route::get('/upload', [\App\Http\Controllers\Admin\AudioManagementController::class, 'upload'])->name('upload');
        Route::post('/upload', [\App\Http\Controllers\Admin\AudioManagementController::class, 'store'])->name('store');
        Route::get('/{audio}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'show'])->name('show');
        Route::get('/{audio}/edit', [\App\Http\Controllers\Admin\AudioManagementController::class, 'edit'])->name('edit');
        Route::put('/{audio}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'update'])->name('update');
        Route::delete('/{audio}', [\App\Http\Controllers\Admin\AudioManagementController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\AudioManagementController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\AudioManagementController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\AudioManagementController::class, 'statistics'])->name('statistics');
    });

    // Timeline Management Routes
    Route::prefix('timeline-management')->name('timeline-management.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'store'])->name('store');
        Route::get('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'show'])->name('show');
        Route::get('/{timeline}/edit', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'edit'])->name('edit');
        Route::put('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'update'])->name('update');
        Route::delete('/{timeline}', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\TimelineManagementController::class, 'statistics'])->name('statistics');
    });

    // File Upload Management Routes
    Route::prefix('file-upload')->name('file-upload.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\FileUploadController::class, 'index'])->name('index');
        Route::get('/upload', [\App\Http\Controllers\Admin\FileUploadController::class, 'upload'])->name('upload');
        Route::post('/upload', [\App\Http\Controllers\Admin\FileUploadController::class, 'store'])->name('store');
        Route::get('/{file}', [\App\Http\Controllers\Admin\FileUploadController::class, 'show'])->name('show');
        Route::get('/{file}/edit', [\App\Http\Controllers\Admin\FileUploadController::class, 'edit'])->name('edit');
        Route::put('/{file}', [\App\Http\Controllers\Admin\FileUploadController::class, 'update'])->name('update');
        Route::delete('/{file}', [\App\Http\Controllers\Admin\FileUploadController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\FileUploadController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\FileUploadController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\FileUploadController::class, 'statistics'])->name('statistics');
    });

    // Notifications Management Routes
    Route::prefix('notifications-management')->name('notifications-management.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\NotificationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\NotificationController::class, 'store'])->name('store');
        Route::get('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'show'])->name('show');
        Route::get('/{notification}/edit', [\App\Http\Controllers\Admin\NotificationController::class, 'edit'])->name('edit');
        Route::put('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'update'])->name('update');
        Route::delete('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\NotificationController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\NotificationController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\NotificationController::class, 'statistics'])->name('statistics');
    });

    // Coin Management Routes
    Route::prefix('coins')->name('coins.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CoinController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\CoinController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\CoinController::class, 'store'])->name('store');
        Route::get('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'show'])->name('show');
        Route::get('/{coin}/edit', [\App\Http\Controllers\Admin\CoinController::class, 'edit'])->name('edit');
        Route::put('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'update'])->name('update');
        Route::delete('/{coin}', [\App\Http\Controllers\Admin\CoinController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CoinController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\CoinController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\CoinController::class, 'statistics'])->name('statistics');
    });

    // Coupon Management Routes
    Route::prefix('coupons')->name('coupons.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CouponController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\CouponController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\CouponController::class, 'store'])->name('store');
        Route::get('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'show'])->name('show');
        Route::get('/{coupon}/edit', [\App\Http\Controllers\Admin\CouponController::class, 'edit'])->name('edit');
        Route::put('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CouponController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\CouponController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\CouponController::class, 'statistics'])->name('statistics');
    });

    // Commission Payment Management Routes
    Route::prefix('commission-payments')->name('commission-payments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'store'])->name('store');
        Route::get('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'show'])->name('show');
        Route::get('/{commissionPayment}/edit', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'edit'])->name('edit');
        Route::put('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'update'])->name('update');
        Route::delete('/{commissionPayment}', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\CommissionPaymentController::class, 'statistics'])->name('statistics');
    });

    // Payment Management Routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('index');
        Route::get('/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'show'])->name('show');
        Route::get('/{payment}/edit', [\App\Http\Controllers\Admin\PaymentController::class, 'edit'])->name('edit');
        Route::put('/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'update'])->name('update');
        Route::delete('/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\PaymentController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\PaymentController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\PaymentController::class, 'statistics'])->name('statistics');
        Route::post('/{payment}/refund', [\App\Http\Controllers\Admin\PaymentController::class, 'processRefund'])->name('refund');
    });

    // Affiliate Program Management Routes
    Route::prefix('affiliate')->name('affiliate.')->group(function () {
        Route::get('/', function () {
            return view('admin.affiliate.dashboard');
        })->name('dashboard');
        Route::get('/index', [\App\Http\Controllers\Admin\AffiliateController::class, 'index'])->name('index');
        Route::get('/partners', function () {
            return view('admin.affiliate.partners');
        })->name('partners');
        Route::get('/commissions', function () {
            return view('admin.affiliate.commissions');
        })->name('commissions');
        Route::get('/create', [\App\Http\Controllers\Admin\AffiliateController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\AffiliateController::class, 'store'])->name('store');
        Route::get('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'show'])->name('show');
        Route::get('/{affiliate}/edit', [\App\Http\Controllers\Admin\AffiliateController::class, 'edit'])->name('edit');
        Route::put('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'update'])->name('update');
        Route::delete('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\AffiliateController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\AffiliateController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\AffiliateController::class, 'statistics'])->name('statistics');
    });

    // Subscription Plan Management Routes
    Route::prefix('subscription-plans')->name('subscription-plans.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'store'])->name('store');
        Route::get('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'show'])->name('show');
        Route::get('/{subscriptionPlan}/edit', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'edit'])->name('edit');
        Route::put('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'update'])->name('update');
        Route::delete('/{subscriptionPlan}', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'export'])->name('export');
        Route::get('/statistics', [\App\Http\Controllers\Admin\SubscriptionPlanController::class, 'statistics'])->name('statistics');
    });

    // Flavor Analytics Routes
    Route::prefix('analytics/flavors')->name('analytics.flavors.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\FlavorAnalyticsController::class, 'index'])->name('index');
        Route::get('/separate', [\App\Http\Controllers\Admin\FlavorAnalyticsController::class, 'separate'])->name('separate');
        Route::get('/combined', [\App\Http\Controllers\Admin\FlavorAnalyticsController::class, 'combined'])->name('combined');
        Route::get('/comprehensive', [\App\Http\Controllers\Admin\FlavorAnalyticsController::class, 'comprehensive'])->name('comprehensive');
        Route::get('/api', [\App\Http\Controllers\Admin\FlavorAnalyticsController::class, 'api'])->name('api');
    });

    // Story / Episode JSON export & import
    Route::get('stories/export/json', [StoryExportController::class, 'exportJson'])->name('stories.export-json');
    Route::post('stories/import/json', [StoryExportController::class, 'importJson'])->name('stories.import-json');

    // Version Management Routes
    Route::prefix('versions')->name('versions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VersionManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\VersionManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\VersionManagementController::class, 'store'])->name('store');
        Route::get('/{version}', [\App\Http\Controllers\Admin\VersionManagementController::class, 'show'])->name('show');
        Route::get('/{version}/edit', [\App\Http\Controllers\Admin\VersionManagementController::class, 'edit'])->name('edit');
        Route::put('/{version}', [\App\Http\Controllers\Admin\VersionManagementController::class, 'update'])->name('update');
        Route::delete('/{version}', [\App\Http\Controllers\Admin\VersionManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{version}/toggle-active', [\App\Http\Controllers\Admin\VersionManagementController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{version}/set-latest', [\App\Http\Controllers\Admin\VersionManagementController::class, 'setAsLatest'])->name('set-latest');
        Route::get('/statistics', [\App\Http\Controllers\Admin\VersionManagementController::class, 'statistics'])->name('statistics');
        Route::post('/clear-cache', [\App\Http\Controllers\Admin\VersionManagementController::class, 'clearCache'])->name('clear-cache');
    });

});

// Payment Callback Routes
Route::prefix('payment')->group(function () {
    Route::get('zarinpal/callback', [PaymentCallbackController::class, 'zarinpalCallback']);
    Route::get('success', [PaymentCallbackController::class, 'success'])->name('payment.success');
    Route::get('failure', [PaymentCallbackController::class, 'failure'])->name('payment.failure');
    Route::get('retry', [PaymentCallbackController::class, 'retry'])->name('payment.retry');
    Route::get('demo', function () {
        return view('payment.demo');
    })->name('payment.demo');
});

// User Coin Routes - DISABLED
// Route::middleware(['auth'])->group(function () {
//     Route::get('/coins', function () {
//         return view('user.coins.dashboard');
//     })->name('user.coins.dashboard');
//
//     Route::get('/coins/transactions', function () {
//         return view('user.coins.transactions');
//     })->name('user.coins.transactions');
//
//     Route::get('/coins/redemption', function () {
//         return view('user.coins.redemption');
//     })->name('user.coins.redemption');
// });

// User Quiz Routes - DISABLED
// Route::middleware(['auth'])->group(function () {
//     Route::get('/quiz/episode', function () {
//         return view('user.quiz.episode-quiz');
//     })->name('user.quiz.episode');
//
//     Route::get('/quiz/history', function () {
//         return view('user.quiz.quiz-history');
//     })->name('user.quiz.history');
//
//     Route::get('/quiz/statistics', function () {
//         return view('user.quiz.quiz-statistics');
//     })->name('user.quiz.statistics');
// });

// Error Routes
Route::get('/error/404', [ErrorController::class, 'notFound'])->name('error.404');
Route::get('/error/403', [ErrorController::class, 'forbidden'])->name('error.403');
Route::get('/error/500', [ErrorController::class, 'serverError'])->name('error.500');
Route::get('/error/419', [ErrorController::class, 'csrfMismatch'])->name('error.419');
Route::get('/error/429', [ErrorController::class, 'tooManyRequests'])->name('error.429');
Route::get('/error/{statusCode}', [ErrorController::class, 'genericError'])->name('error.generic');
