<?php

return [
    'on' => true,
    // 注解和切面的收集发生在server进程启动前，故（例如修改切面文件的注解的操作）AnnotationCollector
    //和AspectCollector中的container不会发生变化，此时请自行重启服务。
    'driver' => 'hash',
    'hash' => [
        'watch_path' => [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
        ]
    ],
    // 不建议用此方式
    // inotify only support edit in virtual env. edit code with phpstorm use hash driver plaz.
    'inotify' => [
        'watch_path' => [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'app',
        ],
        'file_types' => ['.php'],
        'excluded_dirs' => [],
    ],
];
