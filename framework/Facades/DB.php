<?php


namespace Laras\Facades;


use Closure;
use Exception;
use Illuminate\Database\DatabaseManager;
use Laras\Foundation\Application;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

/**
 * Class DB
 * @package Laras\Facades
 * @method static first(string $sql,array $bindings = [])
 * @method static \Illuminate\Database\ConnectionInterface connection(string $name = null)
 * @method static \Illuminate\Database\Query\Builder table(string $table, string $as = null)
 * @method static \Illuminate\Database\Query\Expression raw($value)
 * @method static array prepareBindings(array $bindings)
 * @method static array pretend(\Closure $callback)
 * @method static array select(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static bool insert(string $query, array $bindings = [])
 * @method static bool statement(string $query, array $bindings = [])
 * @method static bool unprepared(string $query)
 * @method static int affectingStatement(string $query, array $bindings = [])
 * @method static int delete(string $query, array $bindings = [])
 * @method static int transactionLevel()
 * @method static int update(string $query, array $bindings = [])
 * @method static mixed selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static mixed transaction(\Closure $callback, int $attempts = 1)
 * @method static string getDefaultConnection()
 * @method static void beginTransaction()
 * @method static void commit()
 * @method static void listen(\Closure $callback)
 * @method static void rollBack(int $toLevel = null)
 * @method static void setDefaultConnection(string $name)
 * @method static wrap(Closure $closure, ?float $timeout = null)
 *
 * @see \Illuminate\Database\DatabaseManager
 * @see \Illuminate\Database\Connection
 */
class DB extends Facade
{
    /**
     * @var float $popTimeout
     */
    public $popTimeout = 10.0;

    public function getAccessor()
    {
        return Config::get('database.driver');
    }

    /**
     * @param Closure $closure
     * @param float|null $timeout
     * @return mixed
     * @throws Throwable
     */
    public function _wrap(Closure $closure, ?float $timeout = null)
    {
        if ($timeout === null) {
            $timeout = $this->popTimeout;
        }

        $channel = new Channel(1);
        Coroutine::create(function () use ($channel, $closure, $timeout) {
            try {
                $manager = $this->dbManager();
                $result = $closure($manager);
            } catch (Throwable $exception) {
                $result = $exception;
            } finally {
                $channel->push($result, $timeout);
            }
        });

        $result = $channel->pop($timeout);
        if ($result === false && $channel->errCode === SWOOLE_CHANNEL_TIMEOUT) {
            throw new Exception(sprintf('Channel wait failed, reason: Timed out for %s s', $timeout));
        }

        if ($result instanceof Throwable) {
            throw $result;
        }

        return $result;
    }

    /**
     * @param Closure $closure
     * @param float|null $timeout
     * @return mixed
     * @throws Throwable
     */
    protected function _transaction(Closure $closure, ?float $timeout = null)
    {
        if ($timeout === null) {
            $timeout = $this->popTimeout;
        }

        $channel = new Coroutine\Channel(1);

        Coroutine::create(
            function () use ($channel, $closure) {
                try {
                    /**@var DatabaseManager $manager */
                    $manager = $this->dbManager();
                    $manager->beginTransaction();

                    $result = call_user_func($closure, $manager);
                    $manager->commit();
                } catch (Throwable $throwable) {
                    $manager->rollBack();
                    $result = $throwable;
                } finally {
                    $channel->push($result);
                }
            }
        );

        $result = $channel->pop($timeout);

        if ($result === false && $channel->errCode === SWOOLE_CHANNEL_TIMEOUT) {
            throw new Exception(sprintf('Channel wait failed, reason: Timed out for %s s', $timeout));
        }

        if ($result instanceof Throwable) {
            throw $result;
        }

        return $result;
    }

    /**
     * @return bool|mixed|object|void
     * @throws Exception
     */
    protected function dbManager()
    {
        $manager = $this->getAccessor();

        if (false === $manager) {
            throw new Exception('Method getAccessor should be implements');
        }
        if (is_string($manager)) {
            $manager = Application::getInstance()->coMake($manager);
        }

        return $manager;
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        if (in_array($method, ['transaction', 'wrap'])) {
            return call_user_func([new static(), '_' . $method], ...$parameters);
        }

        return call_user_func([new static(), $method], ...$parameters);
    }
}