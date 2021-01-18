<?php

namespace App\Http\Middleware;

use Closure;
use Laras\Http\Request;
use Laras\Http\Response;

class Tool
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return bool|mixed
     */
    public function handle(Request $request, Response $response, Closure $next)
    {
        var_dump('tool');
        return $next($request, $response);
    }
}