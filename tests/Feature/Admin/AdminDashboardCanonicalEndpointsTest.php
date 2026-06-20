<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminDashboardCanonicalEndpointsTest extends TestCase
{
    public function test_canonical_admin_dashboard_endpoints_are_registered(): void
    {
        $cases = [
            ['GET', '/api/admin/v1/dashboard/stats'],
            ['GET', '/api/admin/v1/dashboard/charts'],
            ['GET', '/api/admin/v1/dashboard/export'],
            ['GET', '/api/admin/v1/dashboard/online-users'],
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

    public function test_legacy_admin_dashboard_endpoint_patterns_are_not_registered(): void
    {
        $legacyCases = [
            ['GET', '/api/v1/admin/dashboard/stats'],
            ['GET', '/api/v1/admin/dashboard/charts'],
            ['GET', '/api/admin/dashboard/stats'],
            ['GET', '/api/admin/dashboard/charts'],
            ['GET', '/api/v1/dashboard/stats'],
            ['GET', '/api/v1/dashboard/charts'],
        ];

        foreach ($legacyCases as [$method, $uri]) {
            $this->json($method, $uri)->assertNotFound();
        }
    }
}
