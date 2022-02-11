<?php

namespace Laras\Foundation;

use App\Providers\EventServiceProvider;
use Dotenv\Dotenv;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laras\Config\ConfigServiceProvider;
use Laras\Container\Container;
use Laras\Contracts\Container\Container as ContainerContract;
use Laras\Contracts\Foundation\Application as ApplicationContract;
use Laras\Contracts\Foundation\Application as ApplicationContrat;
use Laras\Foundation\Bootstrap\RegisterFacades;
use Laras\Foundation\Http\Kernel;
use Laras\Foundation\Tcp\Kernel as TcpKernel;
use Laras\Foundation\WebSocket\Kernel as WebSocketKernel;
use Laras\Pipe\Pipeline;
use Laras\Server\HttpServer;
use Laras\Server\TcpServer;
use Laras\Server\WebsocketServer;
use ReflectionException;
use Swoole\Coroutine\Context;
use Swoole\Process;
use Swoole\Process\Pool;
use Symfony\Component\Console\Input\ArgvInput;

class Application extends Container implements ApplicationContract
{
    const VERSION = '1.0.0';

    /**
     * @var Pool $pool
     */
    protected $pool;

    /**
     * @var Process $worker
     */
    protected $worker;

    /**
     * @var string $rootPath
     */
    protected $rootPath;

    /**
     * @var string $environmentPath
     */
    protected $environmentPath;

    /**
     * @var string $environmentFile
     */
    protected $environmentFile = '.env';

    /**
     * @var array $eagerLoadedServiceProviders
     */
    protected $eagerLoadedServiceProviders = [];

    /**
     * @var array $deferredServiceProviders
     */
    protected $deferredServiceProviders = [];

    /**
     * @var string $serverType
     */
    protected $serverType;

    /**
     * @var mixed $server
     */
    protected $server;

    /**
     * @var array $bootstrappers
     */
    protected $bootstrappers = [
        RegisterFacades::class
    ];

    /**
     * Application constructor.
     * @param Pool|null $pool
     * @param Process|null $worker
     * @param string $server
     * @throws BindingResolutionException
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(Pool $pool, Process $worker, string $server)
    {
        $this->pool = $pool;
        $this->worker = $worker;
        $this->registerContext();
        $this->registerServerType($server);
        $this->setBasePath(ROOT_PATH);
        $this->LoadEnvironmentVariables();
        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerServiceProviders();
        $this->server = $this->make($server);
        $this->bootServiceProvider();
        $this->bootstrap();
    }

    /**
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * @return Pool
     */
    public function getPool(): Pool
    {
        return $this->pool;
    }

    /**
     * @return int
     */
    public function getWorkerId(): int
    {
        return $this->worker->id;
    }

    /**
     * @param string $rootPath
     * @return $this
     */
    public function setBasePath(string $rootPath): self
    {
        $this->rootPath = rtrim($rootPath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    public function bindPathsInContainer()
    {
        $this->instance('path.base', $this->basePath());
        $this->instance('path.app', $this->appPath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.lang', $this->langPath());
    }

    /**
     * @param string $path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->rootPath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * @param string $path
     * @return string
     */
    public function appPath($path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'app' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * @param string $path
     * @return string
     */
    public function configPath($path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * @param string $path
     * @return string
     */
    public function storagePath($path = ''): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * @param string $path
     * @return string
     */
    public function langPath($path = '')
    {
        return $this->resourcePath() . DIRECTORY_SEPARATOR . 'lang' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function resourcePath($path = '')
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function LoadEnvironmentVariables()
    {
        $this->checkForSpecificEnvironmentFile();
        $this->createDotenv()->safeLoad();
    }

    protected function checkForSpecificEnvironmentFile()
    {
        if (($input = new ArgvInput)->hasParameterOption('--env')) {
            if ($this->setEnvironmentFilePath(
                $this->environmentFile() . '.' . $input->getParameterOption('--env')
            )) {
                return;
            }
        }

        $environment = Env::get('APP_ENV');

        if (!$environment) {
            return;
        }

        $this->setEnvironmentFilePath(
            $this->environmentFile() . '.' . $environment
        );
    }

    protected function setEnvironmentFilePath($file)
    {
        if (is_file($this->environmentPath() . '/' . $file)) {
            $this->loadEnvironmentFrom($file);

            return true;
        }

        return false;
    }

    protected function createDotenv()
    {
        return Dotenv::create(
            Env::getRepository(),
            $this->environmentPath(),
            $this->environmentFile()
        );
    }

    public function environmentFile()
    {
        return $this->environmentFile ?: '.env';
    }

    public function environmentFilePath()
    {
        return $this->environmentPath() . DIRECTORY_SEPARATOR . $this->environmentFile();
    }

    public function environmentPath()
    {
        return $this->environmentPath ?: $this->rootPath;
    }

    public function useEnvironmentPath($path)
    {
        $this->environmentPath = $path;

        return $this;
    }

    public function loadEnvironmentFrom($file)
    {
        $this->environmentFile = $file;

        return $this;
    }

    public function environment(...$environments)
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }

    /**
     * @throws Exception
     */
    public function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance(ApplicationContrat::class, $this);
        $this->alias(ApplicationContrat::class, 'app');
        $this->alias(ApplicationContrat::class, ContainerContract::class);
        $this->alias(ContainerContract::class, 'container');

        if ($this->serverType == 'Http') {
            $this->instance(Kernel::class, new Kernel($this));
        } elseif ($this->serverType == 'Tcp') {
            $this->instance(TcpKernel::class, new TcpKernel($this));
        } elseif ($this->serverType == 'WebSocket') {
            $this->instance(WebSocketKernel::class, new WebSocketKernel($this));
        }

        $this->instance(Pipeline::class, new Pipeline());
    }

    /**
     * @throws BindingResolutionException
     */
    public function bootstrap()
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)
                ->bootstrap($this);
        }
    }

    public function registerBaseServiceProviders()
    {
        $configServiceProvider = new ConfigServiceProvider($this);
        $eventServiceProvider = new EventServiceProvider($this);
        foreach ([$configServiceProvider, $eventServiceProvider] as $baseServiceProvider) {
            if (method_exists($baseServiceProvider, 'register')) {
                $baseServiceProvider->register();
            }
            $this->eagerLoadedServiceProviders[] = $baseServiceProvider;
        }
    }

    public function registerServiceProviders()
    {
        foreach ($this['config']['app']['providers'] as $serviceProvider) {
            $serviceProvider = new $serviceProvider($this);
            if ($serviceProvider instanceof DeferrableProvider) {
                $deferredServices = $serviceProvider->provides();
                foreach ($deferredServices as $deferredService) {
                    $this->deferredServiceProviders[$deferredService] = $serviceProvider;
                }
            } else {
                if (method_exists($serviceProvider, 'register')) {
                    $serviceProvider->register();
                }
                $this->eagerLoadedServiceProviders[] = $serviceProvider;
            }
        }
    }

    /**
     * @param string $server
     * @throws Exception
     */
    protected function registerServerType(string $server)
    {
        switch ($server) {
            case  HttpServer::class:
                $this->serverType = 'Http';
                break;
            case TcpServer::class:
                $this->serverType = 'Tcp';
                break;
            case WebsocketServer::class:
                $this->serverType = 'WebSocket';
                break;
            default:
                throw new Exception(sprintf('Invalid server %s', $server));
        }
    }

    public function bootServiceProvider()
    {
        foreach ($this->eagerLoadedServiceProviders as $eagerLoadedServiceProvider) {
            if (method_exists($eagerLoadedServiceProvider, 'boot')) {
                $eagerLoadedServiceProvider->boot();
            }
            $this->eagerLoadedServiceProviders[] = $eagerLoadedServiceProvider;
        }
    }

    /**
     * @return Context
     */
    public function registerContext()
    {
        return new Context();
    }

    public function runHttpServer()
    {
        $this->server->configureServer($this['config']['server.http.setting']);
        $this->server->registerHttpHandler();

        $onServerStartHandler = $this['config']['server.http.on_server_start'] ?? [];
        if (!empty($onServerStartHandler)) {
            $handler = new $onServerStartHandler[0]($this);
            call_user_func_array([$handler, $onServerStartHandler[1]], [$this->server, $this->worker]);
        }

        $this->server->start();
    }

    public function runTcpServer()
    {
        $this->server->configureServer($this['config']['server.tcp.setting']);
        $this->server->registerTcpHandler();

        $onTcpServerStartHandler = $this['config']['server.tcp.on_server_start'] ?? [];
        if (!empty($onTcpServerStartHandler)) {
            $handler = new $onTcpServerStartHandler[0]($this);
            call_user_func_array([$handler, $onTcpServerStartHandler[1]], [$this->server, $this->worker]);
        }

        $this->server->start();
    }

    public function runWebSocketServer()
    {
        $this->server->configureServer($this['config']['server.websocket.setting']);
        $this->server->registerWebSocketHandler();

        $onWebSocketServerStartHandler = $this['config']['server.websocket.on_server_start'] ?? [];
        if (!empty($onWebSocketServerStartHandler)) {
            $handler = new $onWebSocketServerStartHandler[0]($this);
            call_user_func_array([$handler, $onWebSocketServerStartHandler[1]], [$this->server, $this->worker]);
        }

        $this->server->start();
    }

    /**
     * @return mixed
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param callable|string $abstract
     * @param array $parameters
     * @return mixed
     * @throws BindingResolutionException
     */
    public function make($abstract, array $parameters = [])
    {
        if ($this->isDeferredService($abstract) && !isset($this->instances[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * @param string $service
     * @return bool
     */
    protected function isDeferredService(string $service)
    {
        return isset($this->deferredServiceProviders[$service]);
    }

    /**
     * @param string $service
     */
    protected function loadDeferredProvider(string $service)
    {
        if (!$this->isDeferredService($service)) {
            return;
        }

        /**@var ServiceProvider $deferServiceProvider */
        $deferServiceProvider = $this->deferredServiceProviders[$service];

        if (!in_array($deferServiceProvider, $this->eagerLoadedServiceProviders)) {
            if (method_exists($deferServiceProvider, 'register')) {
                $deferServiceProvider->register();
            }

            if (method_exists($deferServiceProvider, 'boot')) {
                $deferServiceProvider->boot();
            }

            unset($this->deferredServiceProviders[$service]);
            $this->eagerLoadedServiceProviders[] = $deferServiceProvider;
        }
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this['config']->get('app.locale');
    }

    /**
     * Get the current application fallback locale.
     *
     * @return string
     */
    public function getFallbackLocale()
    {
        return $this['config']->get('app.fallback_locale');
    }

    /**
     * Set the current application locale.
     *
     * @param string $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this['config']->set('app.locale', $locale);

        $this['translator']->setLocale($locale);
    }

    /**
     * Determine if application locale is the given locale.
     *
     * @param string $locale
     * @return bool
     */
    public function isLocale($locale)
    {
        return $this->getLocale() == $locale;
    }

    /**
     * Determine if application is in production environment.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this['env'] === 'production';
    }
}