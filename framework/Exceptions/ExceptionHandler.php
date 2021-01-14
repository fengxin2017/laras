<?php

namespace MoneyMaker\Exceptions;

use Illuminate\Validation\ValidationException;
use MoneyMaker\Facades\Config;
use MoneyMaker\Http\Request;
use MoneyMaker\Http\Response;
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