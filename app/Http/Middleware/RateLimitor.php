<?php

namespace App\Http\Middleware;

use Closure;
use Fig\Http\Message\StatusCodeInterface;
use Laras\Facades\Redis;
use Laras\Http\Request;
use Laras\Http\Response;

class RateLimitor
{
    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @param int|null $seconds
     * @param int|null $counts
     * @return Response|mixed
     */
    public function handle(Request $request, Response $response, Closure $next, ?int $seconds = null, ?int $counts = null)
    {
        if (is_null($seconds) || is_null($counts)) {
            return $next($request, $response);
        }
        $key = $request->uri();

        if (!Redis::exists($key)) {
            Redis::incr($key);
            Redis::expire($key, $seconds);
            return $next($request, $response);
        }

        if (Redis::get($key) > $counts) {
            $response->setStatus(StatusCodeInterface::STATUS_FORBIDDEN);
            $response->setContent('request limited');
            return $response;
        } else {
            Redis::incr($key);
            return $next($request, $response);
        }
    }
}