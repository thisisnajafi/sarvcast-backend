<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AccessControlService;
use Symfony\Component\HttpFoundation\Response;

class CheckPremiumAccess
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        // Check if user has premium access
        if (!$this->accessControlService->hasPremiumAccess($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'برای دسترسی به این بخش اشتراک فعال نیاز است',
                'error_code' => 'PREMIUM_REQUIRED',
                'data' => [
                    'access_level' => 'free',
                    'upgrade_required' => true
                ]
            ], 403);
        }

        return $next($request);
    }
}
