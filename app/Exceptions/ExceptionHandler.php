<?php

namespace App\Exceptions;

use Exception;
use Laras\Exceptions\ExceptionHandler as Handler;
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
        return parent::handle($throwable, $request, $response);
    }
}