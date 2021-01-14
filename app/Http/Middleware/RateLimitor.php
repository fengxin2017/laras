<?php

namespace App\Http\Middleware;

use Closure;
use Fig\Http\Message\StatusCodeInterface;
use MoneyMaker\Facades\Config;
use MoneyMaker\Facades\Redis;
use MoneyMaker\Http\Request;
use MoneyMaker\Http\Response;

class RateLimitor
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return bool|mixed
     */
    public function handle(Request $request, Response $response, Closure $next)
    {
        if ($this->guarded()) {
            $response->setStatus(StatusCodeInterface::STATUS_FORBIDDEN);
            $response->setContent('OVER REQUEST!');
            return $response;
        }

        return $next($request, $response);
    }

    /**
     * @return bool
     */
    protected function guarded(): bool
    {
        return false === Redis::sPop(Config::get('rate.key'));
    }
}