<?php

$defaultOrigins = implode(',', [
    'https://manjiapp.ir',
    'https://www.manjiapp.ir',
    'https://app.manjiapp.ir',
    'https://admin.manjiapp.ir',
    'https://my.manjiapp.ir',
]);

$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', env('CORS_ALLOWED_ORIGINS', $defaultOrigins))
)));

$adminDashboardUrl = trim((string) env('ADMIN_DASHBOARD_URL', 'https://admin.manjiapp.ir'));
if ($adminDashboardUrl !== '') {
    $adminOrigin = rtrim($adminDashboardUrl, '/');
    if (! in_array($adminOrigin, $allowedOrigins, true)) {
        $allowedOrigins[] = $adminOrigin;
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Public API routes (e.g. /api/v1/public/team-members) are called from the
    | static landing (manjiapp.ir), web app (app.manjiapp.ir), and the Next.js
    | admin dashboard (admin.manjiapp.ir).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
        // Any HTTPS manjiapp.ir host (admin/app/my/www/apex) — avoids single-origin
        // optimization returning the wrong Access-Control-Allow-Origin header.
        '#^https://([a-z0-9-]+\.)*manjiapp\.ir$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
