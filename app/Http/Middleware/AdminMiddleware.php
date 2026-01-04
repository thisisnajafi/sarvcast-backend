<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth('web')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'احراز هویت الزامی است.',
                    'error' => 'UNAUTHENTICATED'
                ], 401);
            }

            return redirect()->route('admin.auth.login');
        }

        $user = auth('web')->user();

        // Check if user has admin role
        if ($user->role !== 'admin' && $user->role !== 'super_admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز. شما مجوز دسترسی به این بخش را ندارید.',
                    'error' => 'FORBIDDEN'
                ], 403);
            }

            abort(403, 'دسترسی غیرمجاز');
        }

        // Check if admin account is active
        if ($user->status !== 'active') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حساب کاربری شما غیرفعال است.',
                    'error' => 'ACCOUNT_INACTIVE'
                ], 403);
            }

            abort(403, 'حساب کاربری غیرفعال');
        }

        // Check for super admin permissions for sensitive operations
        $sensitiveRoutes = [
            'admin.coins.store',
            'admin.coins.update',
            'admin.coins.destroy',
            // 'admin.coupons.store',     // Removed - allow regular admins to create coupons
            // 'admin.coupons.update',    // Removed - allow regular admins to update coupons
            // 'admin.coupons.destroy',   // Removed - allow regular admins to delete coupons
            'admin.roles.store',
            'admin.roles.update',
            'admin.roles.destroy',
            'admin.backup.destroy',
            'admin.backup.restore',
        ];

        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, $sensitiveRoutes) && $user->role !== 'super_admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'این عملیات فقط برای ادمین کل مجاز است.',
                    'error' => 'SUPER_ADMIN_REQUIRED'
                ], 403);
            }

            abort(403, 'این عملیات فقط برای ادمین کل مجاز است');
        }

        // Rate limiting for admin operations
        $key = 'admin:' . $user->id . ':' . $request->ip();
        $maxAttempts = 100; // 100 requests per minute
        $decayMinutes = 1;

        if (app('cache')->has($key)) {
            $attempts = app('cache')->get($key);
            if ($attempts >= $maxAttempts) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.',
                        'error' => 'RATE_LIMITED'
                    ], 429);
                }

                abort(429, 'تعداد درخواست‌های شما بیش از حد مجاز است');
            }
            app('cache')->increment($key);
        } else {
            app('cache')->put($key, 1, $decayMinutes * 60);
        }

        // Log admin activity (don't let logging errors break the request)
        try {
            $this->logAdminActivity($request, $user);
        } catch (\Exception $e) {
            // Log the logging error but don't break the request
            Log::error('Failed to log admin activity in middleware: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * Log admin activity for security monitoring
     */
    private function logAdminActivity(Request $request, $user): void
    {
        // Prepare log data
        $logData = [
            'user_id' => $user->id,
            'user_phone' => $user->phone_number ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'timestamp' => now(),
        ];

        // Log to file (always log to file for debugging)
        Log::info('Admin Activity', $logData);

        // Store in database for advanced monitoring (if table exists)
        // Use cache to avoid checking table existence on every request
        $tableExists = cache()->remember('admin_activity_logs_table_exists', 3600, function () {
            return Schema::hasTable('admin_activity_logs');
        });

        if ($tableExists) {
            try {
                DB::table('admin_activity_logs')->insert([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'route_name' => $request->route()?->getName(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // If database logging fails, log the error but don't break the request
                Log::error('Failed to log admin activity to database: ' . $e->getMessage(), [
                    'exception' => $e,
                    'log_data' => $logData
                ]);

                // Invalidate cache if table check fails (might have been dropped)
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, "doesn't exist") !== false || strpos($errorMessage, 'Unknown table') !== false) {
                    cache()->forget('admin_activity_logs_table_exists');
                }
            }
        }
    }
}
