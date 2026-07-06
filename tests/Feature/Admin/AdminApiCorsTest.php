<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminApiCorsTest extends TestCase
{
    public function test_admin_send_otp_preflight_allows_admin_dashboard_origin(): void
    {
        $response = $this->call(
            'OPTIONS',
            '/api/admin/v1/auth/send-otp',
            [],
            [],
            [],
            [
                'HTTP_ORIGIN' => 'https://admin.manjiapp.ir',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,authorization',
            ],
        );

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'https://admin.manjiapp.ir');
    }

    public function test_admin_send_otp_post_echoes_allowed_origin_header(): void
    {
        $response = $this->postJson(
            '/api/admin/v1/auth/send-otp',
            ['phone' => '09120000000'],
            ['Origin' => 'https://admin.manjiapp.ir'],
        );

        $this->assertSame(
            'https://admin.manjiapp.ir',
            $response->headers->get('Access-Control-Allow-Origin'),
        );
    }
}
