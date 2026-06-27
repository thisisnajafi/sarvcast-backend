<?php

namespace App\Http\Middleware;

use App\Models\User;
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
        if (! auth('sanctum')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'احراز هویت الزامی است.',
                    'error' => 'UNAUTHENTICATED',
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = auth('sanctum')->user();

        if (! in_array($user->status, User::loginAllowedStatuses(), true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حساب کاربری شما غیرفعال است.',
                    'error' => 'ACCOUNT_INACTIVE',
                ], 403);
            }

            abort(403, 'حساب کاربری غیرفعال');
        }

        $hasRole = false;

        foreach ($roles as $role) {
            if ($user->role === $role) {
                $hasRole = true;
                break;
            }

            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if ($user->isSuperAdmin()) {
            $hasRole = true;
        }

        if (! $hasRole) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز. شما مجوز دسترسی به این بخش را ندارید.',
                    'error' => 'FORBIDDEN',
                    'required_roles' => $roles,
                ], 403);
            }

            abort(403, 'دسترسی غیرمجاز');
        }

        return $next($request);
    }
}
