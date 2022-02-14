<?php

use App\Models\User;

return [
    // 目前仅支持token方式认证，后续添加guards支持更多认证方式
    'model' => User::class,
    'token' => [
        // 续命时间
        'renewal' => 3600
    ]
];