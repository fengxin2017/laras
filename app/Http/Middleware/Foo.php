<?php

namespace App\Http\Middleware;

use Closure;
use MoneyMaker\Http\Request;
use MoneyMaker\Http\Response;

class Foo
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