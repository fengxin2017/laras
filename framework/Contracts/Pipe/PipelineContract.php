<?php

namespace Laras\Contracts\Pipe;

use Closure;
use Laras\Http\Request;
use Laras\Http\Response;

interface PipelineContract
{
    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function send(Request &$request, Response &$response);

    /**
     * @param array $pipes
     * @return mixed
     */
    public function through(array $pipes);

    /**
     * @param string $method
     * @return mixed
     */
    public function via(string $method);

    /**
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination);
}