<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AdminApiThrottle
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'admin-api:'.($request->user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, 100)) {
            return response()->json([
                'success' => false,
                'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.',
                'error' => 'RATE_LIMITED',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
