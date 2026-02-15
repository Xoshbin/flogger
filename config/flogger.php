<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exclude Files
    |--------------------------------------------------------------------------
    |
    | Here you can specify the file patterns that should be excluded from the
    | log viewer. These patterns will be matched against the file names
    | in the storage/logs directory.
    |
    */
    'exclude_files' => [
        'schedule-*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    |
    | The size of each log chunk to read from the log file in bytes.
    | This is used to paginate the log file content.
    |
    */
    'chunk_size' => 50 * 1024, // 50KB
];
