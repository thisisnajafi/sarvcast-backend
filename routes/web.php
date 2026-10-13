<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\AudioController;

/*
|--------------------------------------------------------------------------
| Web routes (G10: Blade admin retired)
|--------------------------------------------------------------------------
| Canonical admin UI is Next.js at ADMIN_DASHBOARD_URL (admin.manjiapp.ir).
| All former /admin Blade routes redirect there. /api/admin/* stays in api.php.
*/

$adminDashboardUrl = static function (): string {
    return rtrim((string) config('app.admin_dashboard_url', 'https://admin.manjiapp.ir'), '/');
};

Route::get('/', function () use ($adminDashboardUrl) {
    return redirect()->away($adminDashboardUrl());
})->name('home');

// Fallback login route expected by Laravel's auth middleware
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
Route::get('/audio/episodes/{path}', [AudioController::class, 'serve'])
    ->where('path', '.*')
    ->name('audio.episodes.serve');

// Retired Blade admin: redirect every /admin URL to the Next dashboard
Route::any('/admin/{path?}', function () use ($adminDashboardUrl) {
    return redirect()->away($adminDashboardUrl());
})->where('path', '.*')->name('admin.redirect');

// Payment Callback Routes
Route::prefix('payment')->group(function () {
    Route::get('zarinpal/callback', [PaymentCallbackController::class, 'zarinpalCallback']);
    Route::get('payir/callback', [PaymentCallbackController::class, 'payirCallback']);
    Route::get('success', [PaymentCallbackController::class, 'success'])->name('payment.success');
    Route::get('failure', [PaymentCallbackController::class, 'failure'])->name('payment.failure');
    Route::get('retry', [PaymentCallbackController::class, 'retry'])->name('payment.retry');
    Route::get('demo', function () {
        return view('payment.demo');
    })->name('payment.demo');
});

// Error Routes
Route::get('/error/404', [ErrorController::class, 'notFound'])->name('error.404');
Route::get('/error/403', [ErrorController::class, 'forbidden'])->name('error.403');
Route::get('/error/500', [ErrorController::class, 'serverError'])->name('error.500');
Route::get('/error/419', [ErrorController::class, 'csrfMismatch'])->name('error.419');
Route::get('/error/429', [ErrorController::class, 'tooManyRequests'])->name('error.429');
Route::get('/error/{statusCode}', [ErrorController::class, 'genericError'])->name('error.generic');
