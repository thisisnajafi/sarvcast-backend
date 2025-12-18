<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disable All Caching
    |--------------------------------------------------------------------------
    |
    | This configuration disables all Laravel caching mechanisms
    | for development or when caching causes issues.
    |
    */

    'disable_all_caching' => env('DISABLE_ALL_CACHING', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Drivers
    |--------------------------------------------------------------------------
    |
    | When caching is disabled, use these drivers:
    |
    */

    'cache_driver' => env('DISABLE_ALL_CACHING', false) ? 'array' : env('CACHE_DRIVER', 'file'),
    'session_driver' => env('DISABLE_ALL_CACHING', false) ? 'file' : env('SESSION_DRIVER', 'file'),
    'queue_driver' => env('DISABLE_ALL_CACHING', false) ? 'sync' : env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache settings when caching is disabled
    |
    */

    'settings' => [
        'config_cache' => !env('DISABLE_ALL_CACHING', false),
        'route_cache' => !env('DISABLE_ALL_CACHING', false),
        'view_cache' => !env('DISABLE_ALL_CACHING', false),
        'event_cache' => !env('DISABLE_ALL_CACHING', false),
    ],
];
