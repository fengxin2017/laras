<?php


namespace MoneyMaker\Redis;

use Exception;
use Illuminate\Support\ServiceProvider;
use MoneyMaker\Contracts\Foundation\Application;
use MoneyMaker\Facades\Config;
use Swoole\Timer;

/**
 * Class RedisServiceProvider
 * @package MoneyMaker\Redis
 */
class RedisServiceProvider extends ServiceProvider
{
    /**
     * @var Application $app
     */
    protected $app;

    /**
     * @throws Exception
     */
    public function register()
    {
        $this->initRedisPool();
        $this->bindConnection();
    }

    /**
     * @throws Exception
     */
    public function boot(): void
    {
        $this->runClean();
    }

    protected function initRedisPool(): void
    {
        RedisPool::getInstance(
            Config::get('cache.redis_pool.min'),
            Config::get('cache.redis_pool.max')
        )->init();
    }

    /**
     * @throws Exception
     */
    protected function bindConnection(): void
    {
        if (class_exists($driver = Config::get('cache.driver'))) {
            $this->app->coBind(
                Config::get('cache.driver'),
                function () {
                    return RedisPool::getInstance()->get();
                },
                function ($connection) {
                    RedisPool::getInstance()->put($connection);
                }
            );

            return;
        }
        throw new Exception(sprintf('driver <%s> not supported', $driver));
    }

    protected function runClean(): void
    {
        Timer::tick(
            Config::get('cache.redis_pool.idletime') * 1000 + 5000,
            function (int $timerId) {
                $pool = RedisPool::getInstance();

                $free = $pool->getFreeCount();

                while ($free > 0) {
                    if ($connection = $pool->get(0.001)) {
                        if ($connection->isDead()) {
                            $pool->remove($connection);
                        } else {
                            $pool->put($connection);
                        }
                    }
                    $free--;
                }
            }
        );
    }
}