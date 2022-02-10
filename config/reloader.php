<?php

return [
    'on' => true,
    // 某些修改（例如修改切面文件的注解）热载是不起作用的，请自动重启服务重新生产代理文件
    'driver' => 'hash',
    'hash' => [
        'watch_path' => [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
        ]
    ],
    // inotify only support edit in virtual env. edit code with phpstorm use hash driver plaz.
    'inotify' => [
        'watch_path' => [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
        ],
        'file_types' => ['.php'],
        'excluded_dirs' => [],
    ],
];
