<?php

return [
    // 当修改会影响到代理文件时，热载是不起作用的，请自动重启服务重新生产代理文件
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
