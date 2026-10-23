<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('admin.auth.login');
        }
        
        // Check if 2FA is required and not completed
        // Admin 2FA is disabled; requires_2fa is no longer enforced.
        if (false) {
            // Skip 2FA for the 2FA verification route itself
            if ($request->routeIs('admin.2fa.*')) {
                return $next($request);
            }
            
            return redirect()->route('admin.2fa.verify');
        }
        
        return $next($request);
    }
}
