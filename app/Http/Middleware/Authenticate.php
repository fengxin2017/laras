<?php


namespace App\Http\Middleware;


use Closure;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Laras\Facades\Auth;
use Laras\Http\Request;
use Laras\Http\Response;

class Authenticate
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return Response|mixed
     * @throws Exception
     */
    public function handle(Request $request, Response $response, Closure $next)
    {
        // case use token in request header
        if (Auth::jwtCheck()) {
            return $next($request, $response);
        }

        if ($request->expectsJson()) {
            return $response->setStatus(StatusCodeInterface::STATUS_UNAUTHORIZED)
                ->setHeader('Content-type', 'application/json');
        } else {
            return $response->setHeader('Content-type', 'text/html')->setContent(view('errors::401'));
        }
    }
}