<?php

namespace App\Http;

use App\Http\Middleware\TrimStrings;
use Laras\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * @var array
     */
    protected $middleware = [
        TrimStrings::class
    ];
}