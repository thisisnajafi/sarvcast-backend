<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for suspicious activity
        $suspicious = $this->securityService->checkSuspiciousActivity($request);
        
        if (!empty($suspicious)) {
            $this->securityService->logSecurityEvent('Suspicious activity detected', [
                'suspicious_activities' => $suspicious,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
            
            // Block request if too suspicious
            if (count($suspicious) >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request blocked due to suspicious activity'
                ], 403);
            }
        }

        // Check for SQL injection attempts
        foreach ($request->all() as $key => $value) {
            if (is_string($value) && $this->securityService->detectSqlInjection($value)) {
                $this->securityService->logSecurityEvent('SQL injection attempt detected', [
                    'parameter' => $key,
                    'value' => $value,
                    'url' => $request->fullUrl(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input detected'
                ], 400);
            }
        }

        // Check for XSS attempts
        foreach ($request->all() as $key => $value) {
            if (is_string($value) && $this->securityService->detectXss($value)) {
                $this->securityService->logSecurityEvent('XSS attempt detected', [
                    'parameter' => $key,
                    'value' => $value,
                    'url' => $request->fullUrl(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input detected'
                ], 400);
            }
        }

        // Rate limiting
        if (!$this->securityService->rateLimit($request, 'api_requests', 100, 1)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.'
            ], 429);
        }

        // Add security headers
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");
        
        return $response;
    }
}