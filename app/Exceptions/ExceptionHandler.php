<?php

namespace App\Exceptions;

use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Laras\Exceptions\ExceptionHandler as Handler;
use Laras\Foundation\Http\HttpNotFoundException;
use Laras\Foundation\Http\MethodNotAllowedException;
use Laras\Http\Request;
use Laras\Http\Response;
use Throwable;

class ExceptionHandler extends Handler
{
    /**
     * @param Throwable $throwable
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function handle(Throwable $throwable, Request $request, Response $response): Response
    {
        if ($throwable instanceof MethodNotAllowedException) {
            if ($request->expectsJson()) {
                $response->setStatus(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
            } else {
                $response->setHeader('Content-type', 'text/html');
                $response->setContent(view('errors::404'));
            }

            return $response;
        } elseif ($throwable instanceof HttpNotFoundException) {
            if ($request->expectsJson()) {
                $response->setStatus(StatusCodeInterface::STATUS_NOT_FOUND);
            } else {
                $response->setHeader('Content-type', 'text/html');
                $response->setContent(view('errors::404'));
            }
            return $response;
        }

        return parent::handle($throwable, $request, $response);
    }
}