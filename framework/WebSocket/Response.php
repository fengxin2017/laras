<?php

namespace Laras\WebSocket;

use Laras\Http\Response as LarasResponse;
use Swoole\Http\Response as SwooleResponse;

class Response extends LarasResponse
{
    /**
     * @var Response $webSocketResponse
     */
    protected $webSocketResponse;

    /**
     * Response constructor.
     * @param SwooleResponse $response
     */
    public function __construct(SwooleResponse $response)
    {
        parent::__construct($response);
        $this->webSocketResponse = $response;
    }

    public function push($data)
    {
        $this->swooleResponse->push($data);
    }

    public function close()
    {
        $this->swooleResponse->close();
    }
}