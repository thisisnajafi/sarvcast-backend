<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
            'https://fcm.googleapis.com/*' => Http::response(['success' => 1], 200),
        ]);
    }
}
