<?php

return [

    'disk' => env('MEDIA_LIBRARY_DISK', 'public'),

    'max_upload_kb' => (int) env('MEDIA_LIBRARY_MAX_KB', 5120),

    'max_audio_upload_kb' => (int) env('MEDIA_LIBRARY_MAX_AUDIO_KB', 102400),

    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],

    'allowed_audio_extensions' => ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac'],

    'thumbnail' => [
        'width' => 300,
        'height' => 300,
        'quality' => 80,
    ],

    'convert_to_webp' => filter_var(env('MEDIA_LIBRARY_CONVERT_WEBP', false), FILTER_VALIDATE_BOOL),

    'folders' => [
        'general',
        'stories',
        'episodes',
        'categories',
        'characters',
        'people',
        'timeline',
        'production',
        'users',
    ],

    'max_files_per_upload' => (int) env('MEDIA_LIBRARY_MAX_FILES', 20),

    'legacy_folder_map' => [
        'categories' => 'categories',
        'stories' => 'stories',
        'episodes' => 'episodes',
        'people' => 'people',
        'users' => 'users',
        'timeline' => 'timeline',
        'playlists' => 'general',
        'characters' => 'characters',
    ],

    'legacy_storage_scan_dirs' => ['images', 'media', 'stories', 'audio'],

    'legacy_document_extensions' => ['md', 'txt', 'json'],

];
