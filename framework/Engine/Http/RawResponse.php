<?php

namespace Laras\Engine\Http;

final class RawResponse
{
    /**
     * @var int
     */
    public $statusCode = 0;

    /**
     * @var string[][]
     */
    public $headers = [];

    /**
     * @var string
     */
    public $body = '';

    /**
     * Protocol version.
     * @var string
     */
    public $version = '';

    /**
     * RawResponse constructor.
     * @param int $statusCode
     * @param array $headers
     * @param string $body
     * @param string $version
     */
    public function __construct(int $statusCode, array $headers, string $body, string $version)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->version = $version;
    }
}
