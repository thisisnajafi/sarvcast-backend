<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SecurityService
{
    /**
     * Check for suspicious activity
     */
    public function checkSuspiciousActivity(Request $request, User $user = null): array
    {
        $suspicious = [];
        
        // Check for multiple failed login attempts
        if ($this->hasMultipleFailedAttempts($request)) {
            $suspicious[] = 'Multiple failed login attempts detected';
        }
        
        // Check for unusual IP address
        if ($this->isUnusualIpAddress($request->ip())) {
            $suspicious[] = 'Unusual IP address detected';
        }
        
        // Check for rapid requests
        if ($this->hasRapidRequests($request)) {
            $suspicious[] = 'Rapid requests detected';
        }
        
        // Check for suspicious user agent
        if ($this->isSuspiciousUserAgent($request->userAgent())) {
            $suspicious[] = 'Suspicious user agent detected';
        }
        
        return $suspicious;
    }

    /**
     * Check for multiple failed login attempts
     */
    private function hasMultipleFailedAttempts(Request $request): bool
    {
        $key = 'failed_login_' . $request->ip();
        $attempts = Cache::get($key, 0);
        
        return $attempts >= 5;
    }

    /**
     * Check for unusual IP address
     */
    private function isUnusualIpAddress(string $ip): bool
    {
        // Check if IP is from known VPN/proxy services
        $suspiciousRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ];
        
        foreach ($suspiciousRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check for rapid requests
     */
    private function hasRapidRequests(Request $request): bool
    {
        $key = 'rapid_requests_' . $request->ip();
        $requests = Cache::get($key, 0);
        
        return $requests >= 100; // More than 100 requests per minute
    }

    /**
     * Check for suspicious user agent
     */
    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if IP is in range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        
        return ($ip & $mask) == $subnet;
    }

    /**
     * Rate limit requests
     */
    public function rateLimit(Request $request, string $key, int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        $key = $key . ':' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'key' => $key,
                'attempts' => RateLimiter::attempts($key),
            ]);
            
            return false;
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        return true;
    }

    /**
     * Generate secure token
     */
    public function generateSecureToken(int $length = 32): string
    {
        return Str::random($length);
    }

    /**
     * Hash sensitive data
     */
    public function hashSensitiveData(string $data): string
    {
        return Hash::make($data);
    }

    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }

    /**
     * Sanitize input data
     */
    public function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove potentially dangerous characters
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $value = trim($value);
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $data = []): void
    {
        Log::channel('security')->info($event, array_merge([
            'timestamp' => now(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $data));
    }

    /**
     * Check for SQL injection attempts
     */
    public function detectSqlInjection(string $input): bool
    {
        $sqlPatterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/i',
            '/(\b(OR|AND)\s+\d+\s*=\s*\d+)/i',
            '/(\b(OR|AND)\s+\'\s*=\s*\')/i',
            '/(\b(OR|AND)\s+\"\s*=\s*\")/i',
            '/(\b(OR|AND)\s+[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[a-zA-Z_][a-zA-Z0-9_]*)/i',
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check for XSS attempts
     */
    public function detectXss(string $input): bool
    {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/i',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/i',
            '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate security report
     */
    public function generateSecurityReport(): array
    {
        return [
            'timestamp' => now(),
            'failed_login_attempts' => $this->getFailedLoginAttempts(),
            'suspicious_ips' => $this->getSuspiciousIps(),
            'rate_limited_ips' => $this->getRateLimitedIps(),
            'security_events' => $this->getSecurityEvents(),
            'recommendations' => $this->getSecurityRecommendations(),
        ];
    }

    /**
     * Get failed login attempts
     */
    private function getFailedLoginAttempts(): int
    {
        // This would typically query a security events table
        return 0;
    }

    /**
     * Get suspicious IPs
     */
    private function getSuspiciousIps(): array
    {
        // This would typically query a security events table
        return [];
    }

    /**
     * Get rate limited IPs
     */
    private function getRateLimitedIps(): array
    {
        // This would typically query rate limiter data
        return [];
    }

    /**
     * Get security events
     */
    private function getSecurityEvents(): array
    {
        // This would typically query a security events table
        return [];
    }

    /**
     * Get security recommendations
     */
    private function getSecurityRecommendations(): array
    {
        $recommendations = [];
        
        // Check if HTTPS is enabled
        if (!request()->secure()) {
            $recommendations[] = 'Enable HTTPS for all communications';
        }
        
        // Check if strong passwords are enforced
        $recommendations[] = 'Enforce strong password policies';
        
        // Check if two-factor authentication is available
        $recommendations[] = 'Implement two-factor authentication';
        
        return $recommendations;
    }
}

