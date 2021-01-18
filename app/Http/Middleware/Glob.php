<?php

namespace App\Http\Middleware;

use Closure;
use Laras\Http\Request;
use Laras\Http\Response;

class Glob
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return bool|mixed
     */
    public function handle(Request $request, Response $response, Closure $next)
    {
        return $next($request, $response);
    }
}