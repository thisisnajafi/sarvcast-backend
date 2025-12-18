<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Timeline Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for timeline image uploads, including PHP limits
    | and validation rules.
    |
    */

    'max_file_uploads' => env('TIMELINE_MAX_FILE_UPLOADS', 300),
    'max_file_size' => env('TIMELINE_MAX_FILE_SIZE', '10M'),
    'max_post_size' => env('TIMELINE_MAX_POST_SIZE', '200M'),
    'memory_limit' => env('TIMELINE_MEMORY_LIMIT', '1024M'),
    'max_execution_time' => env('TIMELINE_MAX_EXECUTION_TIME', 300),
    'max_input_time' => env('TIMELINE_MAX_INPUT_TIME', 300),
    'max_input_vars' => env('TIMELINE_MAX_INPUT_VARS', 5000),

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation rules for timeline uploads
    |
    */

    'validation' => [
        'min_timeline_entries' => 1,
        'max_timeline_entries' => env('TIMELINE_MAX_FILE_UPLOADS', 300),
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'max_image_size' => '10MB',
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | Error messages for timeline upload validation
    |
    */

    'messages' => [
        'too_many_files' => 'تعداد تصاویر تایم‌لاین (:count) از محدودیت PHP (:limit فایل در هر درخواست) بیشتر است.',
        'file_too_large' => 'حجم فایل تصویر نمی‌تواند بیشتر از :size باشد.',
        'invalid_file_type' => 'فرمت فایل تصویر پشتیبانی نمی‌شود.',
        'upload_failed' => 'خطا در آپلود فایل تصویر.',
    ],
];
