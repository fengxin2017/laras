<?php

namespace Laras\Foundation\Exceptions;

use Exception;
use Laras\Facades\View;

class RegisterErrorViewPaths
{
    /**
     * @throws Exception
     */
    public function __invoke()
    {
        View::replaceNamespace('errors', collect(config('view.paths'))->map(function ($path) {
            return "{$path}/errors";
        })->push(__DIR__ . '/views')->all());
    }
}
