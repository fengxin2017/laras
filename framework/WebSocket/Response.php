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

    protected $webSocketResponseData;

    /**
     * Response constructor.
     * @param SwooleResponse $response
     */
    public function __construct(SwooleResponse $response)
    {
        parent::__construct($response);
        $this->webSocketResponse = $response;
    }

    public function setWebSocketResponseData($data)
    {
        $this->webSocketResponseData = $data;

        return $this;
    }

    public function push()
    {
        $this->swooleResponse->push($this->webSocketResponseData);
    }

    public function close()
    {
        $this->swooleResponse->close();
    }
}