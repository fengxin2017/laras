<?php

namespace App\Exceptions;

use MoneyMaker\Exceptions\ExceptionHandler as Handler;
use MoneyMaker\Http\Request;
use MoneyMaker\Http\Response;
use Throwable;

class ExceptionHandler extends Handler
{
    /**
     * @param Throwable $throwable
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function handle(Throwable $throwable, Request $request, Response $response): Response
    {
        return parent::handle($throwable, $request, $response);
    }
}