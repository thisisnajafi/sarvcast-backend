<?php

$storageStoriesPath = storage_path('app/manji-stories');

return [

    /*
    |--------------------------------------------------------------------------
    | Source stories directory
    |--------------------------------------------------------------------------
    |
    | Canonical markdown episodes and characters_and_objects.json are stored
    | under Laravel storage (not the public web root).
    |
    | Priority:
    |   1. STORY_EDITOR_STORIES_PATH in .env (optional override)
    |   2. default_stories_path below (storage/app/manji-stories)
    |   3. discovery_paths (local dev: sibling manji-stories repo)
    |
    */
    'stories_path' => env('STORY_EDITOR_STORIES_PATH'),

    'storage_subdirectory' => 'manji-stories',

    'default_stories_path' => $storageStoriesPath,

    /*
    | Local dev only — used when storage folder is empty and sibling repo exists.
    */
    'discovery_paths' => [
        '../manji-stories',
        '../../manji-stories',
    ],

    'exclude_directory_patterns' => [
        'README', 'CHANGELOG', 'GUIDE', 'TEMPLATE', 'PROMPT',
        'ANIMATED', 'COMPLETE', 'QUICK_START', 'PROJECT_SUMMARY',
        '__pycache__', '.git', 'iransans', 'manji-team', 'Voices', 'OLD_Sarv',
    ],

];
