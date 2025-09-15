<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AWS S3 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AWS S3 file storage integration
    |
    */

    'default_bucket' => env('AWS_BUCKET', 'sarvcast-storage'),
    'default_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    
    'buckets' => [
        'images' => env('AWS_IMAGES_BUCKET', env('AWS_BUCKET', 'sarvcast-images')),
        'audio' => env('AWS_AUDIO_BUCKET', env('AWS_BUCKET', 'sarvcast-audio')),
        'documents' => env('AWS_DOCUMENTS_BUCKET', env('AWS_BUCKET', 'sarvcast-documents')),
    ],

    'directories' => [
        'images' => [
            'stories' => 'stories/images',
            'episodes' => 'episodes/images',
            'users' => 'users/images',
            'categories' => 'categories/images',
        ],
        'audio' => [
            'episodes' => 'episodes/audio',
            'previews' => 'episodes/previews',
        ],
        'documents' => [
            'scripts' => 'documents/scripts',
            'transcripts' => 'documents/transcripts',
        ],
    ],

    'image_sizes' => [
        'stories' => [
            'thumbnail' => ['width' => 300, 'height' => 300, 'fit' => 'cover'],
            'medium' => ['width' => 600, 'height' => 400, 'fit' => 'cover'],
            'large' => ['width' => 1200, 'height' => 800, 'fit' => 'cover'],
        ],
        'episodes' => [
            'thumbnail' => ['width' => 200, 'height' => 200, 'fit' => 'cover'],
            'medium' => ['width' => 400, 'height' => 300, 'fit' => 'cover'],
        ],
        'users' => [
            'avatar' => ['width' => 150, 'height' => 150, 'fit' => 'cover'],
            'thumbnail' => ['width' => 100, 'height' => 100, 'fit' => 'cover'],
        ],
    ],

    'audio_settings' => [
        'max_duration' => 3600, // 1 hour in seconds
        'allowed_formats' => ['mp3', 'wav', 'm4a', 'aac', 'ogg'],
        'max_file_size' => 102400, // 100MB in KB
        'quality' => 'high', // high, medium, low
    ],

    'cdn' => [
        'enabled' => env('AWS_CDN_ENABLED', false),
        'domain' => env('AWS_CDN_DOMAIN'),
    ],

    'backup' => [
        'enabled' => env('AWS_BACKUP_ENABLED', false),
        'retention_days' => env('AWS_BACKUP_RETENTION_DAYS', 30),
    ],
];
