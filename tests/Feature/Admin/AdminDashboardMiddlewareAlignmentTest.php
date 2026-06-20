<?php

namespace Tests\Feature\Admin;

use Illuminate\Http\Request;
use Tests\TestCase;

class AdminDashboardMiddlewareAlignmentTest extends TestCase
{
    public function test_admin_v1_auth_and_dashboard_routes_enforce_expected_security_middleware(): void
    {
        $this->assertRouteHasMiddleware('POST', '/api/admin/v1/auth/send-otp', [
            'security',
            'admin.origin',
            'throttle:20,1',
        ]);

        $this->assertRouteHasMiddleware('POST', '/api/admin/v1/auth/login', [
            'security',
            'admin.origin',
            'throttle:20,1',
        ]);

        $this->assertRouteHasMiddleware('GET', '/api/admin/v1/auth/me', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
        ]);

        $this->assertRouteHasMiddleware('POST', '/api/admin/v1/auth/logout', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
            'api.audit',
        ]);

        $this->assertRouteHasMiddleware('POST', '/api/admin/v1/auth/logout-all', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
            'api.audit',
        ]);

        $this->assertRouteHasMiddleware('POST', '/api/admin/v1/auth/2fa/send-code', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
            'api.audit',
        ]);

        $this->assertRouteHasMiddleware('POST', '/api/admin/v1/auth/2fa/verify', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
            'api.audit',
        ]);

        $this->assertRouteHasMiddleware('GET', '/api/admin/v1/dashboard/stats', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
        ]);

        $this->assertRouteHasMiddleware('GET', '/api/admin/v1/dashboard/charts', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
        ]);

        $this->assertRouteHasMiddleware('GET', '/api/admin/v1/dashboard/export', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
        ]);

        $this->assertRouteHasMiddleware('GET', '/api/admin/v1/dashboard/online-users', [
            'security',
            'admin.origin',
            'auth:sanctum',
            'role:admin,super_admin',
        ]);
    }

    public function test_admin_resource_routes_enforce_auth_and_admin_permission_stack(): void
    {
        $this->assertRouteHasMiddleware('GET', '/api/admin/stories', [
            'auth:sanctum',
            'api.admin',
            'api.permission',
            'throttle:120,1',
            'api.audit',
        ]);

        $this->assertRouteHasMiddleware('POST', '/api/admin/stories', [
            'auth:sanctum',
            'api.admin',
            'api.permission',
            'throttle:120,1',
            'api.audit',
        ]);
    }

    private function assertRouteHasMiddleware(string $method, string $uri, array $expectedMiddleware): void
    {
        $route = app('router')->getRoutes()->match(Request::create($uri, $method));
        $resolved = $route->gatherMiddleware();

        foreach ($expectedMiddleware as $middleware) {
            $this->assertContains(
                $middleware,
                $resolved,
                sprintf(
                    'Expected middleware [%s] on route [%s] %s. Resolved: %s',
                    $middleware,
                    $method,
                    $uri,
                    implode(', ', $resolved)
                )
            );
        }
    }
}
