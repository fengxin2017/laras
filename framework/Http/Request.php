<?php

namespace Laras\Http;

use Swoole\Http\Request as SwooleRequest;

class Request
{
    /**
     * @var SwooleRequest
     */
    protected $swooleRequest;

    /**
     * @var array $get
     */
    protected $get;

    /**
     * @var array $post
     */
    protected $post;

    /**
     * @var array $header
     */
    protected $header;

    /**
     * @var $files
     */
    protected $files;

    /**
     * Request constructor.
     * @param SwooleRequest $swooleRequest
     */
    public function __construct(SwooleRequest $swooleRequest = null)
    {
        $this->swooleRequest = $swooleRequest;
        $this->get = $this->swooleRequest->get;
        $this->post = $this->swooleRequest->post;
        $this->header = $this->swooleRequest->header;
        $this->files = $this->swooleRequest->files;
    }

    /**
     * @param string|null $key
     * @return mixed|null
     */
    public function get(string $key = null)
    {
        if (is_null($key)) {
            return $this->get ?? [];
        }
        return $this->get[$key] ?? null;
    }

    /**
     * @param string|null $key
     * @return mixed|null
     */
    public function post(string $key = null)
    {
        if (is_null($key)) {
            return $this->post ?? [];
        }

        return $this->post[$key] ?? null;
    }

    /**
     * @param string|null $key
     * @return mixed|null
     */
    public function header(string $key = null)
    {
        if (is_null($key)) {
            return $this->header ?? [];
        }

        return $this->header[$key] ?? null;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function file(string $key)
    {
        if (is_null($key)) {
            return $this->files;
        }

        return $this->files[$key];
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $this->swooleRequest->server['request_method'] ?? 'GET';
    }

    /**
     * @return mixed
     */
    public function uri()
    {
        return $this->swooleRequest->server['request_uri'];
    }

    /**
     * @return array
     */
    public function all()
    {
        return array_merge($this->get(), $this->post());
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return call_user_func_array([$this->swooleRequest, $method], $parameters);
    }
}