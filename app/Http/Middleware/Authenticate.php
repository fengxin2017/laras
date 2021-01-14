<?php


namespace App\Http\Middleware;


use Closure;
use Fig\Http\Message\StatusCodeInterface;
use MoneyMaker\Facades\Auth;
use MoneyMaker\Http\Request;
use MoneyMaker\Http\Response;

class Authenticate
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return bool|mixed
     */
    public function handle(Request $request, Response $response, Closure $next)
    {
        if (Auth::check()) {
            return $next($request, $response);
        }
        return $response->setStatus(StatusCodeInterface::STATUS_UNAUTHORIZED)
            ->setHeader('Content-type', 'application/json');
    }
}