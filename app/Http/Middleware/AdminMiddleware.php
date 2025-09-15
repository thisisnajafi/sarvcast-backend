<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        if (!$request->user()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            return redirect()->route('admin.auth.login')->with('error', 'لطفاً ابتدا وارد شوید.');
        }

        // Check if user is admin
        if (!$request->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], 403);
            }
            
            return redirect()->route('admin.auth.login')->with('error', 'دسترسی غیرمجاز. نیاز به دسترسی مدیر.');
        }

        return $next($request);
    }
}
