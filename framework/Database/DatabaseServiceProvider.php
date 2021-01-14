<?php


namespace MoneyMaker\Database;

use Exception;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use MoneyMaker\Contracts\Foundation\Application;
use MoneyMaker\Facades\Config;
use PDOException;
use Swoole\Timer;

/**
 * Class DatabaseServiceProvider
 * @package MoneyMaker\Database
 */
class DatabaseServiceProvider extends ServiceProvider
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
        if (!extension_loaded('pdo')) {
            throw new PDOException('PDO NOT INSTALL!');
        }

        $this->initDbPool();
        $this->bindConnection();
    }

    /**
     * @throws Exception
     */
    public function boot(): void
    {
        $this->runClean();
    }

    protected function initDbPool(): void
    {
        DatabasePool::getInstance(
            Config::get('database.min_connection'),
            Config::get('database.max_connection')
        )->init();
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function bindConnection()
    {
        if (class_exists($manager = Config::get('database.driver'))) {
            $this->app->coBind($manager,
                function () {
                    return DatabasePool::getInstance()->get();
                },
                function ($connection) {
                    DatabasePool::getInstance()->put($connection);
                }
            );

            if ($manager == DatabaseManager::class) {
                $this->app->instance('db.connector.mysql', new MySqlConnector());
            }

            return true;
        }

        throw new Exception(sprintf('unsupport <%s>', $manager));
    }

    protected function runClean(): void
    {
        Timer::tick(
            Config::get('database.idletime') * 1000 + 5000,
            function (int $timerId) {
                $pool = DatabasePool::getInstance();

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