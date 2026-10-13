<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TestingHttpGuard
{
    /**
     * Tests may only talk to hosts that Http::fake() stubbed.
     * Http::preventStrayRequests() throws before a real socket opens.
     */
    public static function assertFaked(string $purpose): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        $factory = Http::getFacadeRoot();

        if (! is_object($factory) || ! method_exists($factory, 'preventingStrayRequests') || ! $factory->preventingStrayRequests()) {
            throw new RuntimeException($purpose.' cannot leave the test process without Http::preventStrayRequests().');
        }
    }
}
