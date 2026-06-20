<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Source stories directory
    |--------------------------------------------------------------------------
    |
    | Canonical markdown episode files and characters_and_objects.json live here.
    | Matches ProcessStoriesFromMarkdown default discovery paths.
    |
    */
    'stories_path' => env('STORY_EDITOR_STORIES_PATH', null),

    'exclude_directory_patterns' => [
        'README', 'CHANGELOG', 'GUIDE', 'TEMPLATE', 'PROMPT',
        'ANIMATED', 'COMPLETE', 'QUICK_START', 'PROJECT_SUMMARY',
        '__pycache__', '.git', 'iransans', 'sarvcast-team', 'Voices', 'OLD_Sarv',
    ],

];
