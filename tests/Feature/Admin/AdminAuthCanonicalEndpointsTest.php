<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminAuthCanonicalEndpointsTest extends TestCase
{
    public function test_canonical_admin_auth_endpoints_are_registered(): void
    {
        $cases = [
            ['POST', '/api/admin/v1/auth/send-otp'],
            ['POST', '/api/admin/v1/auth/login'],
            ['GET', '/api/admin/v1/auth/me'],
            ['POST', '/api/admin/v1/auth/logout'],
            ['POST', '/api/admin/v1/auth/logout-all'],
            ['POST', '/api/admin/v1/auth/2fa/send-code'],
            ['POST', '/api/admin/v1/auth/2fa/verify'],
        ];

        foreach ($cases as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $this->assertNotSame(
                404,
                $response->getStatusCode(),
                sprintf('Expected canonical route to exist: [%s] %s', $method, $uri)
            );
        }
    }

    public function test_legacy_admin_auth_endpoint_patterns_are_not_registered(): void
    {
        $legacyCases = [
            ['POST', '/api/v1/admin/auth/send-otp'],
            ['POST', '/api/v1/admin/auth/login'],
            ['GET', '/api/v1/admin/auth/me'],
            ['POST', '/api/v1/admin/auth/logout'],
            ['POST', '/api/v1/admin/auth/logout-all'],
            ['POST', '/api/v1/admin/auth/2fa/send-code'],
            ['POST', '/api/v1/admin/auth/2fa/verify'],
            ['POST', '/api/v1/auth/admin/send-otp'],
            ['POST', '/api/v1/auth/admin/login'],
            ['GET', '/api/v1/auth/admin/me'],
            ['POST', '/api/v1/auth/admin/logout'],
            ['POST', '/api/v1/auth/admin/logout-all'],
            ['POST', '/api/v1/auth/admin/2fa/send-code'],
            ['POST', '/api/v1/auth/admin/2fa/verify'],
        ];

        foreach ($legacyCases as [$method, $uri]) {
            $this->json($method, $uri)->assertNotFound();
        }
    }
}
