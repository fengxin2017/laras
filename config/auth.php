<?php

use App\Models\User;

return [
    'model' => User::class,
    'token' => [
        // 续命时间
        'renewal' => 3600
    ]
];