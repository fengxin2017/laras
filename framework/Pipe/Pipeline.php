<?php

namespace Laras\Pipe;

use Closure;
use Laras\Http\Request;
use Laras\Http\Response;
use Throwable;
use Laras\Contracts\Pipe\PipelineContract;
use Exception;

class Pipeline implements PipelineContract
{

    protected $request;

    protected $response;

    /**
     * @var array $pipes
     */
    protected $pipes = [];

    /**
     * @var string $method
     */
    protected $method = 'handle';

    /**
     * @param Request $requset
     * @param Response $response
     * @return $this
     */
    public function send(Request &$requset, Response &$response): self
    {
        $this->request = $requset;

        $this->response = $response;

        return $this;
    }

    /**
     * @param array $pipes
     * @return $this
     */
    public function through(array $pipes): self
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function via(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
        );

        try {
            return $pipeline($this->request, $this->response);
        } finally {
            $this->clean();
        }
    }

    protected function clean(): void
    {
        $this->request = null;
        $this->response = null;
        $this->pipes = [];
    }

    /**
     * @param Closure $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function (Request $request, Response $response) use ($destination) {
            try {
                return $destination($request, $response);
            } catch (Throwable $e) {
                return $this->handleException($request, $response, $e);
            }
        };
    }

    /**
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function (Request $request, Response $response) use ($stack, $pipe) {
                try {
                    if (is_callable($pipe)) {
                        return $pipe($request, $response, $stack);
                    } elseif (!is_object($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        try {
                            $pipe = new $name();
                        } catch (Throwable $throwable) {
                            throw new Exception(sprintf('Class [%s] not found.', $name));
                        }

                        $parameters = array_merge([$request, $response, $stack], $parameters);
                    } else {
                        $parameters = [$request, $response, $stack];
                    }

                    return method_exists($pipe, $this->method)
                        ? $pipe->{$this->method}(...$parameters)
                        : $pipe(...$parameters);
                } catch (Throwable $e) {
                    return $this->handleException($request, $response, $e);
                }
            };
        };
    }

    /**
     * 分离中间件类名和请求参数  eg. Dev::class . ':1,2,3' =>  [Dev::class,[1,2,3]]
     *
     * @param string $pipe 中间件
     * @return array
     */
    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * @return array
     */
    protected function pipes(): array
    {
        return $this->pipes;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param Throwable $e
     * @return Throwable|null
     * @throws Throwable
     */
    protected function handleException(Request $request, Response $response, Throwable $e): ?Throwable
    {
        throw $e;
    }
}
