<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('web', \App\Http\Middleware\EnsureSessionCookieSent::class);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'api.admin' => \App\Http\Middleware\ApiAdminMiddleware::class,
            'api.contributor' => \App\Http\Middleware\ApiContributorGuardMiddleware::class,
            'api.audit' => \App\Http\Middleware\ApiAdminAuditMiddleware::class,
            'api.permission' => \App\Http\Middleware\ApiAdminPermissionMiddleware::class,
            'admin.origin' => \App\Http\Middleware\EnsureAllowedAdminOrigin::class,
            'legacy.api.deprecation' => \App\Http\Middleware\LegacyApiDeprecationHeaders::class,
            'cache.api' => \App\Http\Middleware\CacheApiResponses::class,
            'security' => \App\Http\Middleware\SecurityMiddleware::class,
            'premium.access' => \App\Http\Middleware\CheckPremiumAccess::class,
            'content.access' => \App\Http\Middleware\CheckContentAccess::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            '2fa' => \App\Http\Middleware\RequireTwoFactorAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            $wantsJson = $request->is('api/*') || $request->expectsJson();

            if ($wantsJson) {
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }

                if ($e instanceof \Illuminate\Database\QueryException) {
                    $sql = $e->getMessage();
                    \Illuminate\Support\Facades\Log::error('API database error', [
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                        'sql' => $sql,
                    ]);
                    if ($request->bearerToken() && str_contains($sql, 'personal_access_tokens')) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unauthenticated.',
                        ], 401);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'سرویس موقتاً در دسترس نیست.',
                    ], 503);
                }
            }

            // Handle specific HTTP exceptions with custom error pages (web only)
            if ($wantsJson) {
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json(['success' => false, 'message' => 'Not found.'], 404);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                    return response()->json(['success' => false, 'message' => 'Too many requests.'], 429);
                }

                if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                    return response()->json(['success' => false, 'message' => 'Session expired.'], 419);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage() ?: 'Request failed.',
                    ], $e->getStatusCode());
                }
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->view('errors.404', [], 404);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                return response()->view('errors.403', [], 403);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
                return response()->view('errors.429', [], 429);
            }

            // Handle CSRF token mismatch (419)
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                return response()->view('errors.419', [], 419);
            }

            // Handle server errors (500)
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() >= 500) {
                return response()->view('errors.500', [], $e->getStatusCode());
            }

            // Handle other HTTP exceptions
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $statusCode = $e->getStatusCode();
                
                // Check if we have a specific error page for this status code
                $errorView = "errors.{$statusCode}";
                if (view()->exists($errorView)) {
                    return response()->view($errorView, [], $statusCode);
                }
                
                // Fallback to generic error page
                return response()->view('errors.error', [
                    'error_code' => $statusCode,
                    'error_title' => 'خطای سیستم',
                    'error_message' => 'خطایی در سیستم رخ داده است. لطفاً بعداً دوباره تلاش کنید.'
                ], $statusCode);
            }
        });
    })->create();
