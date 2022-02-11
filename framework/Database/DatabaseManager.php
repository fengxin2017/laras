<?php


namespace Laras\Database;


use Closure;
use Exception;
use Laras\Facades\Config;
use PDO;

/**
 * @deprecated we will use laravel database manager
 * Class DatabaseManager
 * @package Laras\Database
 */
class DatabaseManager
{
    /**
     * @var PDO $pdo
     */
    protected $pdo;

    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var string $fetchStyle
     */
    protected $fetchStyle;

    /**
     * @var Closure $pdoResolver
     */
    protected $pdoResolver;

    /**
     * @var float $lastUseTime
     */
    protected $lastUseTime = 0.0;

    /**
     * NativeManager constructor.
     */
    public function __construct()
    {
        $this->config = Config::get('database');
        $this->setPdoResolver(function () {
            $this->connect();
        });
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @return array
     */
    public function select(string $sql, array $bindings = [])
    {
        try {
            $statement = $this->getPdo()->prepare($sql);
            $statement->execute($bindings);
            return $statement->fetchAll($this->config['fetch']);
        } catch (Exception $exception) {
            throw new QueryException(
                $sql, $bindings, $exception
            );
        }
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @return mixed
     * @throws Exception
     */
    public function first(string $sql, array $bindings = [])
    {
        try {
            $statement = $this->getPdo()->prepare($sql);
            $statement->execute($bindings);

            return $statement->fetch($this->config['fetch']);
        } catch (Exception $exception) {
            throw new QueryException(
                $sql, $bindings, $exception
            );
        }
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @return bool
     * @throws Exception
     */
    public function update(string $sql, array $bindings = [])
    {
        try {
            $statement = $this->getPdo()->prepare($sql);
            return $statement->execute($bindings);
        } catch (Exception $exception) {
            throw new QueryException(
                $sql, $bindings, $exception
            );
        }
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @return bool
     * @throws Exception
     */
    public function delete(string $sql, array $bindings = [])
    {
        try {
            $statement = $this->getPdo()->prepare($sql);
            return $statement->execute($bindings);
        } catch (Exception $exception) {
            throw new QueryException(
                $sql, $bindings, $exception
            );
        }
    }

    /**
     * @param string $sql
     * @param array $bindings
     * @return bool
     * @throws Exception
     */
    public function create(string $sql, array $bindings = []): bool
    {
        try {
            $statement = $this->getPdo()->prepare($sql);

            return $statement->execute($bindings);
        } catch (Exception $exception) {
            throw new QueryException(
                $sql, $bindings, $exception
            );
        }
    }

    public function connect()
    {
        $this->pdo = new PDO(
            $this->getDns(), $this->config['connections']['mysql']['username'], $this->config['connections']['mysql']['password']
        );

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (isset($this->config['connections']['mysql']['charset'])) {
            $this->pdo->prepare("set names '{$this->config['connections']['mysql']['charset']}'")->execute();
        }
    }

    /**
     * @return string
     */
    protected function getDns(): string
    {
        return "mysql:host={$this->config['connections']['mysql']['host']};dbname={$this->config['connections']['mysql']['database']}";
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function setPdoResolver(Closure $closure): self
    {
        $this->pdoResolver = $closure;

        return $this;
    }

    /**
     * @return PDO
     * @throws Exception
     */
    public function getPdo(): PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        if ($this->pdoResolver) {
            call_user_func($this->pdoResolver);
            return $this->pdo;
        }

        throw new Exception('Can\'t find pdo resolver.');
    }

    public function disconnect()
    {
        if ($this->pdo) {
            $this->pdo = null;
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function beginTransaction()
    {
        return $this->getPdo()->beginTransaction();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function commit()
    {
        return $this->getPdo()->commit();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function rollBack()
    {
        return $this->getPdo()->rollBack();
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $maxIdleTime = $this->config['idletime'];

        $now = microtime(true);

        if ($now > $maxIdleTime + $this->lastUseTime) {
            return false;
        }

        $this->lastUseTime = $now;
        return true;
    }

    /**
     * @return bool
     */
    public function isDead()
    {
        return !$this->isActive();
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function __call(string $method, array $parameters)
    {
        $this->lastUseTime = microtime(true);
        return call_user_func([$this->getPdo(), $method], ...$parameters);
    }
}