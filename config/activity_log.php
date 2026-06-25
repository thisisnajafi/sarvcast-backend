<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity log channels
    |--------------------------------------------------------------------------
    */
    'channels' => [
        'admin',
        'app',
        'system',
        'security',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention (days) — used by future prune job
    |--------------------------------------------------------------------------
    */
    'retention_days' => [
        'app' => 180,
        'admin' => 365,
        'security' => 365,
        'system' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Keys redacted from properties / old_values / new_values
    |--------------------------------------------------------------------------
    */
    'redact_keys' => [
        'password',
        'password_confirmation',
        'token',
        'api_token',
        'otp',
        'code',
        'secret',
        'authorization',
        'credit_card',
        'cvv',
        'card_number',
    ],

    'max_properties_bytes' => 32768,

    'max_user_agent_length' => 512,

    /*
    |--------------------------------------------------------------------------
    | Admin HTTP paths skipped (no audit row)
    |--------------------------------------------------------------------------
    */
    'skip_path_patterns' => [
        'api/admin/activity-logs',
        'api/admin/v1/auth/send-otp',
    ],

    'export_max_rows' => (int) env('ACTIVITY_LOG_EXPORT_MAX_ROWS', 10000),

    /*
    |--------------------------------------------------------------------------
    | Security alert thresholds (dashboard widgets)
    |--------------------------------------------------------------------------
    */
    'security_alerts' => [
        'failed_login_spike' => [
            'actions' => ['login_failed'],
            'window_minutes' => 60,
            'threshold' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Eloquent model audit (Phase 4 observers)
    |--------------------------------------------------------------------------
    */
    'model_audit' => [
        'enabled' => env('ACTIVITY_LOG_MODEL_AUDIT', true),

        \App\Models\Story::class => [
            'subject_type' => 'story',
            'ignore' => [
                'updated_at',
                'created_at',
                'play_count',
                'total_episodes',
                'duration',
                'total_plays',
                'total_favorites',
                'total_ratings',
                'avg_rating',
                'total_duration_played',
                'unique_listeners',
                'completion_count',
                'completion_rate',
                'share_count',
                'download_count',
                'last_played_at',
                'trending_since',
                'analytics_data',
            ],
        ],

        \App\Models\Episode::class => [
            'subject_type' => 'episode',
            'ignore' => [
                'updated_at',
                'created_at',
                'play_count',
                'total_plays',
                'total_favorites',
                'total_ratings',
                'avg_rating',
                'total_duration_played',
                'unique_listeners',
                'completion_count',
                'completion_rate',
                'share_count',
                'download_count',
                'last_played_at',
                'trending_since',
                'analytics_data',
            ],
        ],

        \App\Models\User::class => [
            'subject_type' => 'user',
            'ignore' => [
                'updated_at',
                'created_at',
                'password',
                'remember_token',
                'last_login_at',
                'last_activity_at',
                'total_sessions',
                'total_play_time',
                'total_favorites',
                'total_ratings',
                'total_spent',
                'analytics_data',
            ],
        ],

        \App\Models\MediaAsset::class => [
            'subject_type' => 'media_asset',
            'ignore' => [
                'updated_at',
                'created_at',
            ],
        ],
    ],

];
