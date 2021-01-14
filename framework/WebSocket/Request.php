<?php

namespace MoneyMaker\WebSocket;

use MoneyMaker\Http\Request as MoneyMakerRequest;
use Swoole\Http\Request as SwooleRequest;

class Request extends MoneyMakerRequest
{
    /**
     * @var Request $webSocketRequest
     */
    protected $webSocketRequest;

    /**
     * @var $frame
     */
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