<?php

$defaultOrigins = implode(',', [
    'https://manjiapp.ir',
    'https://www.manjiapp.ir',
    'https://app.manjiapp.ir',
    'https://admin.manjiapp.ir',
    'https://my.manjiapp.ir',
]);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Public API routes (e.g. /api/v1/public/team-members) are called from the
    | static landing (manjiapp.ir) and web app (app.manjiapp.ir).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', $defaultOrigins))
    ))),

    'allowed_origins_patterns' => [
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
