<?php

namespace MoneyMaker\Router;

use FastRoute\DataGenerator;
use FastRoute\RouteParser;

class RouteCollector
{
    /**
     * @var string
     */
    protected $server;

    /**
     * @var array $middleware
     */
    public $middleware = [];

    /**
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * @var DataGenerator
     */
    protected $dataGenerator;

    /**
     * @var string
     */
    protected $currentGroupPrefix;

    /**
     * @var array
     */
    protected $currentGroupOptions = [];

    /**
     * RouteCollector constructor.
     * @param RouteParser $routeParser
     * @param DataGenerator $dataGenerator
     * @param string $server
     */
    public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator, string $server = 'http')
    {
        $this->routeParser = $routeParser;
        $this->dataGenerator = $dataGenerator;
        $this->currentGroupPrefix = '/';
        $this->server = $server;
    }

    /**
     * @param $httpMethod
     * @param string $route
     * @param $handler
     * @param array $options
     */
    public function addRoute($httpMethod, string $route, $handler, array $options = [])
    {
        $route = $this->currentGroupPrefix . trim($route, '/');
        $routeDatas = $this->routeParser->parse($route);
        $options = $this->mergeOptions($this->currentGroupOptions, $options);
        foreach ((array)$httpMethod as $method) {
            $method = strtoupper($method);
            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($method, $routeData, $handler);
                $method = $method . '@' . $handler[0] . '@' . $handler[1];
                if (isset($options['middleware'])) {
                    $this->middleware[$method] = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
                }
            }
        }
    }

    /**
     * Create a route group with a common prefix.
     *
     * @param string $prefix
     * @param callable $callback
     * @param array $options
     */
    public function addGroup(string $prefix, callable $callback, array $options = [])
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $currentGroupOptions = $this->currentGroupOptions;

        $this->currentGroupPrefix = $previousGroupPrefix . trim($prefix, '/') . '/';

        $this->currentGroupOptions = $this->mergeOptions($currentGroupOptions, $options);
        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupOptions = $currentGroupOptions;
    }

    /**
     * Adds a GET route to the collection.
     *
     * @param string $route
     * @param $handler
     * @param array $options
     */
    public function get(string $route, $handler, array $options = [])
    {
        $this->addRoute('GET', $route, $handler, $options);
    }

    /**
     * @param string $route
     * @param $handler
     * @param array $options
     */
    public function post(string $route, $handler, array $options = [])
    {
        $this->addRoute('POST', $route, $handler, $options);
    }

    /**
     * Adds a PUT route to the collection.
     *
     * @param string $route
     * @param $handler
     * @param array $options
     */
    public function put(string $route, $handler, array $options = [])
    {
        $this->addRoute('PUT', $route, $handler, $options);
    }

    /**
     * Adds a DELETE route to the collection.
     *
     * @param string $route
     * @param $handler
     * @param array $options
     */
    public function delete(string $route, $handler, array $options = [])
    {
        $this->addRoute('DELETE', $route, $handler, $options);
    }

    /**
     * Adds a PATCH route to the collection.
     *
     * @param string $route
     * @param $handler
     * @param array $options
     */
    public function patch(string $route, $handler, array $options = [])
    {
        $this->addRoute('PATCH', $route, $handler, $options);
    }

    /**
     * Adds a HEAD route to the collection.
     *
     * @param string $route
     * @param $handler
     * @param array $options
     */
    public function head(string $route, $handler, array $options = [])
    {
        $this->addRoute('HEAD', $route, $handler, $options);
    }

    /**
     * Returns the collected route data, as provided by the data generator.
     */
    public function getRoutes(): array
    {
        return $this->dataGenerator->getData();
    }

    /**
     * @param array $origin
     * @param array $options
     * @return array
     */
    protected function mergeOptions(array $origin, array $options): array
    {
        return array_merge_recursive($origin, $options);
    }

    public function collect()
    {
        include ROOT_PATH . '/routes/web.php';
    }
}
