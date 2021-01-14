<?php

return [
    'driver' => 'hash',
    'hash' => [
        'watch_path' => [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
            ROOT_PATH . DIRECTORY_SEPARATOR . 'config'
        ]
    ],
    'inotify' => [
        'watch_path' => ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
        'file_types' => ['.php'],
        'excluded_dirs' => [],
    ],
];
