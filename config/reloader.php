<?php

return [
    'driver' => 'hash',
    'hash' => [
        'watch_path' => [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
            ROOT_PATH . DIRECTORY_SEPARATOR . 'config'
        ]
    ],
    // inotify only support edit in virtual env. edit code with phpstorm use hash driver plaz.
    'inotify' => [
        'watch_path' => [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
            ROOT_PATH . DIRECTORY_SEPARATOR . 'config'
        ],
        'file_types' => ['.php'],
        'excluded_dirs' => [],
    ],
];
