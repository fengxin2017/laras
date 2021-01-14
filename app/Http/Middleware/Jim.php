<?php

namespace App\Http\Middleware;

use Closure;
use MoneyMaker\Http\Request;
use MoneyMaker\Http\Response;

class Jim
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return bool|mixed
     */
    public function handle(Request $request, Response $response, Closure $next)
    {
        var_dump('this is jim middleware');
        //return $response->setContent('invalid request')->setHeader('Content-type','text/html')->setStatus(401);
        return $next($request, $response);
    }
}