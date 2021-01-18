<?php


namespace Laras\Database;


use Exception;
use Laras\Facades\Config;
use Laras\Foundation\Application;
use Laras\Pool\Pool;
use Swoole\Coroutine\Channel;

class DatabasePool extends Pool
{
    /**
     * @var Application $app
     */
    public $app;

    /**
     * @var int $min
     */
    public $min;

    /**
     * @var int $max
     */
    public $max;

    /**
     * @var int $current
     */
    public $current = 0;

    /**
     * @var Channel $connections
     */
    public $connections = null;

    /**
     * @var bool $booted
     */
    public $booted = false;

    /**
     * @var static $instance
     */
    public static $instance = null;

    /**
     * @param int $min
     * @param int $max
     * @return DatabasePool|static|null
     */
    public static function getInstance(int $min = 100, int $max = 500): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new static(Application::getInstance(), $min, $max);
        }

        return self::$instance;
    }

    /**
     * @throws Exception
     */
    public function addConnection()
    {
        $this->current++;

        $driver = Config::get('database.driver');

        $this->connections->push(new $driver());
    }

    /**
     * @return int
     */
    public function getFreeCount(): int
    {
        return $this->connections->length() - $this->min;
    }

    public function remove(&$connection): void
    {
        $connection->disconnect();
        $this->current--;
        unset($connection);
    }
}