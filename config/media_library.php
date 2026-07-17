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

    /*
    | Convert uploaded JPEG/PNG images to WebP before storing in the library.
    | GIF is left unchanged (animation). Existing assets are never rewritten.
    */
    'convert_to_webp' => filter_var(env('MEDIA_LIBRARY_CONVERT_WEBP', true), FILTER_VALIDATE_BOOL),

    /*
    | WebP quality for originals (1–100). ~80–85 keeps visual quality while shrinking files.
    */
    'webp_quality' => (int) env('MEDIA_LIBRARY_WEBP_QUALITY', 82),

    /*
    | Optional max longest edge in pixels before encode (0 = no downscale).
    | Large phone photos are scaled down first, then encoded as WebP.
    */
    'max_edge_px' => (int) env('MEDIA_LIBRARY_MAX_EDGE', 2560),

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
