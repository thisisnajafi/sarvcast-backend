<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAdminMiddleware
{
    /**
     * Validate admin/super_admin access for Sanctum API routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication is required.',
                'error' => 'UNAUTHENTICATED',
            ], 401);
        }

        $user = auth('sanctum')->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'error' => 'FORBIDDEN',
            ], 403);
        }

        if (! in_array($user->status ?? null, User::loginAllowedStatuses(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive.',
                'error' => 'ACCOUNT_INACTIVE',
            ], 403);
        }

        return $next($request);
    }
}

