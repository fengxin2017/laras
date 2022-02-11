<?php


namespace Laras\Tcp;


use Swoole\Coroutine\Server\Connection;

class Request
{
    /**
     * @var Connection $connection
     */
    protected $connection;


    protected $requestData;

    /**
     * TcpRequest constructor.
     * @param Connection $connection
     * @param $requestRaw
     */
    public function __construct(Connection $connection, $requestRaw)
    {
        $this->connection = $connection;
        $this->requestData = $requestRaw;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return mixed
     */
    public function getRequestData()
    {
        return $this->requestData;
    }
}
