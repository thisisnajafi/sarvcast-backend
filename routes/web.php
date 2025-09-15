<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EpisodeController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AnalyticsController;
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
    
    // Episodes
    Route::resource('episodes', EpisodeController::class);
    
    // Categories
    Route::resource('categories', CategoryController::class);
    
    // Users
    Route::resource('users', UserController::class);
    Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    
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
});

// Payment Callback Routes
Route::prefix('payment')->group(function () {
    Route::get('zarinpal/callback', [PaymentCallbackController::class, 'zarinpalCallback']);
    Route::get('success', [PaymentCallbackController::class, 'success'])->name('payment.success');
    Route::get('failure', [PaymentCallbackController::class, 'failure'])->name('payment.failure');
});
