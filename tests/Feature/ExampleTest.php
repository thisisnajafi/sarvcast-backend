<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        config(['app.admin_dashboard_url' => 'https://admin.manjiapp.ir']);

        $response = $this->get('/');

        $response->assertRedirect('https://admin.manjiapp.ir');
    }
}
