<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Source stories directory
    |--------------------------------------------------------------------------
    |
    | Priority:
    |   1. STORY_EDITOR_STORIES_PATH in .env
    |   2. default_stories_path below (edit on server if you prefer config over .env)
    |   3. discovery_paths (relative to Laravel base_path, or absolute)
    |
    | Production: upload the sarvcast-stories repo on the server, then either set
    | STORY_EDITOR_STORIES_PATH in .env or set default_stories_path here.
    |
    */
    'stories_path' => env('STORY_EDITOR_STORIES_PATH'),

    /*
    | Fallback when STORY_EDITOR_STORIES_PATH is not set in .env.
    | Example on shared hosting (adjust to your account):
    | 'default_stories_path' => '/home/my@sarvcast.ir/sarvcast-stories',
    */
    'default_stories_path' => null,

    /*
    | Paths tried in order when neither env nor default_stories_path resolves.
    | Relative paths are resolved from Laravel base_path().
    */
    'discovery_paths' => [
        '../sarvcast-stories',
        '../../sarvcast-stories',
        'sarvcast-stories',
        'storage/app/sarvcast-stories',
    ],

    'exclude_directory_patterns' => [
        'README', 'CHANGELOG', 'GUIDE', 'TEMPLATE', 'PROMPT',
        'ANIMATED', 'COMPLETE', 'QUICK_START', 'PROJECT_SUMMARY',
        '__pycache__', '.git', 'iransans', 'sarvcast-team', 'Voices', 'OLD_Sarv',
    ],

];
