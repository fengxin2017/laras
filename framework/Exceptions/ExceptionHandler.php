<?php

namespace Laras\Exceptions;

use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Illuminate\Validation\ValidationException;
use Laras\Facades\Config;
use Laras\Foundation\Http\HttpNotFoundException;
use Laras\Foundation\Http\MethodNotAllowedException;
use Laras\Http\Request;
use Laras\Http\Response;
use Throwable;

class ExceptionHandler
{

    /**
     * @var array $internalDontReport
     */
    protected $internalDontReport = [
        ValidationException::class,
        MethodNotAllowedException::class,
        HttpNotFoundException::class
    ];

    /**
     * @param Throwable $throwable
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function handle(Throwable $throwable, Request $request, Response $response): Response
    {
        if ($throwable instanceof ValidationException) {
            $response->setStatus($throwable->status);
            $response->setContent($throwable->validator->errors()->toJson());
        } elseif ($throwable instanceof MethodNotAllowedException) {
            if ($request->expectsJson()) {
                $response->setStatus(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
            } else {
                $response->setHeader('Content-type', 'text/html');
                $response->setContent(view('errors::404'));
            }

        } elseif ($throwable instanceof HttpNotFoundException) {
            if ($request->expectsJson()) {
                $response->setStatus(StatusCodeInterface::STATUS_NOT_FOUND);
            } else {
                $response->setHeader('Content-type', 'text/html');
                $response->setContent(view('errors::404'));
            }
        }

        if (Config::get('app.debug_inconsole') && !in_array(get_class($throwable), $this->internalDontReport)) {
            var_dump($throwable->getMessage());
            var_dump($throwable->getTraceAsString());
        }

        return $response;
    }
}