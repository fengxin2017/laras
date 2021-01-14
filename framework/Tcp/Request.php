<?php


namespace MoneyMaker\Tcp;


use Swoole\Coroutine\Server\Connection;

class Request
{
    /**
     * @var Connection $connection
     */
    protected $connection;

    /**
     * @var # $requestData
     */
    protected $requestData;

    /**
     * TcpRequest constructor.
     * @param Connection $connection
     * @param $requestData
     */
    public function __construct(Connection $connection, $requestData)
    {
        $this->connection = $connection;
        $this->requestData = $requestData;
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
