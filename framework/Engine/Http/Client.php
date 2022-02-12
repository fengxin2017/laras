<?php


namespace Laras\Engine\Http;

use Swoole\Coroutine\Http\Client as HttpClient;
use Exception;


class Client extends HttpClient
{
    public function set(array $settings)
    {
        parent::set($settings);
        return $this;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $headers
     * @param string $contents
     * @param string $version
     * @return RawResponse
     * @throws Exception
     */
    public function request(string $method = 'GET', string $path = '/', array $headers = [], string $contents = '', string $version = '1.1'): RawResponse
    {
        $this->setMethod($method);
        $this->setData($contents);
        $this->setHeaders($this->encodeHeaders($headers));
        $this->execute($path);
        if ($this->errCode !== 0) {
            throw new \Exception($this->errMsg, $this->errCode);
        }
        return new RawResponse(
            $this->statusCode,
            $this->decodeHeaders($this->headers ?? []),
            $this->body,
            $version
        );
    }

    /**
     * @param string[] $headers
     * @return string[][]
     */
    private function decodeHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $header) {
            $result[$name][] = $header;
        }
        if ($this->set_cookie_headers) {
            $result['Set-Cookies'] = $this->set_cookie_headers;
        }
        return $result;
    }

    /**
     * Swoole engine not support two dimensional array.
     * @param string[][] $headers
     * @return string[]
     */
    private function encodeHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $value) {
            $result[$name] = is_array($value) ? implode(',', $value) : $value;
        }

        return $result;
    }
}