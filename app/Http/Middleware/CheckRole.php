<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  The roles that are allowed to access the route
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated using Sanctum guard
        // Since routes are protected with auth:sanctum, we should check the sanctum guard
        if (!auth('sanctum')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'احراز هویت الزامی است.',
                    'error' => 'UNAUTHENTICATED'
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = auth('sanctum')->user();

        // Check if user account is active
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

        // Check if user has any of the required roles
        $hasRole = false;

        foreach ($roles as $role) {
            // Check direct role field
            if ($user->role === $role) {
                $hasRole = true;
                break;
            }

            // Check role relationship
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        // Special handling: super_admin has access to everything
        if ($user->isSuperAdmin()) {
            $hasRole = true;
        }

        if (!$hasRole) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز. شما مجوز دسترسی به این بخش را ندارید.',
                    'error' => 'FORBIDDEN',
                    'required_roles' => $roles
                ], 403);
            }

            abort(403, 'دسترسی غیرمجاز');
        }

        return $next($request);
    }
}
