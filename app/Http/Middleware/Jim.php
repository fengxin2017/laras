<?php

namespace App\Http\Middleware;

use Closure;
use Laras\Http\Request;
use Laras\Http\Response;

class Jim
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return bool|mixed
     */
    public function handle(Request $request, Response $response, Closure $next,$a,$b)
    {
        var_dump('this is jim middleware');
        var_dump($a);
        var_dump($b);
        //return $response->setContent('invalid request')->setHeader('Content-type','text/html')->setStatus(401);
        return $next($request, $response);
    }
}