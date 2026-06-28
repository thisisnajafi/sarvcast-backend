<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local → server story import (Phase C remote pilot)
    |--------------------------------------------------------------------------
    |
    | Used when developers run imports from a local machine against the
    | production/staging Laravel API (no direct DB connection).
    |
    | 1. Set LOCAL_IMPORT_BOOTSTRAP_SECRET on the server (.env).
    | 2. POST /api/admin/local-import/bootstrap with header
    |    X-Local-Import-Bootstrap: {secret}
    |    — or run: php artisan admin:create-local-import-token
    | 3. Store the returned token locally as LOCAL_IMPORT_API_TOKEN.
    |
    */

    'bootstrap_secret' => env('LOCAL_IMPORT_BOOTSTRAP_SECRET'),

    /** Sanctum token name (used to revoke/rotate). */
    'token_name' => env('LOCAL_IMPORT_TOKEN_NAME', 'local-old-stories-import'),

    /** Optional: pin token to this super_admin phone when bootstrapping. */
    'super_admin_phone' => env('LOCAL_IMPORT_SUPER_ADMIN_PHONE'),

    /** Optional: pin token to this user id when bootstrapping. */
    'super_admin_user_id' => env('LOCAL_IMPORT_SUPER_ADMIN_USER_ID'),

    /**
     * Sanctum token abilities (informational; super_admin bypasses RBAC today).
     *
     * @var list<string>
     */
    'token_abilities' => [
        'local-import:bootstrap',
        'story-editor:read',
        'story-editor:import',
        'story-editor:scaffold',
        'stories:import-old',
    ],

    /** Suggested local .env keys (for CLI output only). */
    'local_env_keys' => [
        'base_url' => 'LOCAL_IMPORT_API_BASE_URL',
        'token' => 'LOCAL_IMPORT_API_TOKEN',
    ],

];
