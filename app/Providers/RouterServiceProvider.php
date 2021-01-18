<?php


namespace App\Providers;


use FastRoute\Dispatcher\GroupCountBased;
use Laras\Router\RouteCollector;
use Laras\Router\RouterServiceProvider as BaseRouterServiceProvider;
use Laras\Server\WebsocketServer;

class RouterServiceProvider extends BaseRouterServiceProvider
{
    /**
     * @var RouteCollector $collector
     */
    protected $collector;

    public function boot()
    {
        $this->collector = $this->app->make(RouteCollector::class);
        $this->loadApiRoutes();
        $this->loadWebRoutes();
        if ($this->app->getServer() instanceof WebsocketServer) {
            $this->loadWebSocketRoutes();
        }

        $this->app->instance(GroupCountBased::class, new GroupCountBased($this->collector->getRoutes()));
    }

    protected function loadApiRoutes()
    {
        $this->collector->addGroup('api', function () {
            require ROOT_PATH . '/routes/api.php';
        });
    }

    protected function loadWebRoutes()
    {
        require ROOT_PATH . '/routes/web.php';
    }

    protected function loadWebSocketRoutes()
    {
        $this->collector->addGroup($this->app['config']['server.websocket.route_prefix'], function () {
            require ROOT_PATH . '/routes/websocket.php';
        });
    }
}