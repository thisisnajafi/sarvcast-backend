<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AccessControlService;
use Symfony\Component\HttpFoundation\Response;

class CheckContentAccess
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $contentType): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است'
            ], 401);
        }

        // Get content ID from route parameters
        $contentId = $request->route('story') ?? $request->route('episode') ?? $request->route('id');
        
        if (!$contentId) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه محتوا یافت نشد'
            ], 400);
        }

        // Validate content access
        $accessInfo = $this->accessControlService->validateContentAccess($user->id, $contentType, $contentId);
        
        if (!$accessInfo['has_access']) {
            return response()->json([
                'success' => false,
                'message' => $accessInfo['message'],
                'error_code' => strtoupper($accessInfo['reason']),
                'data' => [
                    'access_info' => $accessInfo,
                    'upgrade_required' => $accessInfo['reason'] === 'premium_required'
                ]
            ], 403);
        }

        // Add access info to request for use in controller
        $request->merge(['access_info' => $accessInfo]);

        return $next($request);
    }
}
