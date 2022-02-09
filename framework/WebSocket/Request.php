<?php

namespace Laras\WebSocket;

use Laras\Http\Request as LarasRequest;
use Swoole\Http\Request as SwooleRequest;

class Request extends LarasRequest
{
    /**
     * @var Request $webSocketRequest
     */
    protected $webSocketRequest;

    protected $frame;

    /**
     * Request constructor.
     * @param SwooleRequest $request
     * @param $frame
     */
    public function __construct(SwooleRequest $request, $frame)
    {
        parent::__construct($request);
        $this->webSocketRequest = $request;
        $this->frame = $frame;
    }

    /**
     * @return mixed
     */
    public function getFrame()
    {
        return $this->frame;
    }

    public function getMessage()
    {
        return $this->frame->data;
    }

    /**
     * @return mixed
     */
    public function getFd()
    {
        return $this->frame->fd;
    }
}