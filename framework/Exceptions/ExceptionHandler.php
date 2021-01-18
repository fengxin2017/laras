<?php

namespace Laras\Exceptions;

use Illuminate\Validation\ValidationException;
use Laras\Facades\Config;
use Laras\Http\Request;
use Laras\Http\Response;
use Throwable;

class ExceptionHandler
{
    /**
     * @param Throwable $throwable
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function handle(Throwable $throwable, Request $request, Response $response): Response
    {
        if ($throwable instanceof ValidationException) {
            $response->setStatus($throwable->status);
            $response->setContent($throwable->validator->errors()->toJson());
        }

        if (Config::get('app.debug_inconsole')) {
            var_dump($throwable->getMessage());
            var_dump($throwable->getTraceAsString());
        }

        return $response;
    }
}