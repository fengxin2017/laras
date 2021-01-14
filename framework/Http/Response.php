<?php

namespace MoneyMaker\Http;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use MoneyMaker\Database\Model;
use MoneyMaker\Facades\Config;
use ReflectionException;
use ReflectionObject;
use stdClass;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Response
{
    const NORMALRESPONSE = 1;

    const DOWNLOADRESPONSE = 2;

    const REDIRECTRESPONSE = 3;
    /**
     * @var SwooleResponse $swooleResponse
     */
    public $swooleResponse;

    /**
     * @var array
     */
    public $headers = [
        'Content-Type' => 'application/json',
        'charset' => 'UTF-8'
    ];

    /**
     * @var array
     */
    public $cookies = [];

    /**
     * @var string $content
     */
    public $content = '';

    /**
     * @var int $responseType
     */
    public $responseType = self::NORMALRESPONSE;

    /**
     * @var string $redirectPath
     */
    public $redirectPath = '';

    /**
     * file Response
     *
     * @var string $filePath
     */
    public $filePath = '';

    /**
     * @var string $filename
     */
    public $filename = '';

    /**
     * @var int $status
     */
    public $status = 200;

    /**
     * @var
     */
    protected $chunkLimit = 2097152; // 2 * 1024 * 1024

    /**
     * Response constructor.
     * @param SwooleResponse $swooleResponse
     */
    public function __construct(SwooleResponse $swooleResponse)
    {
        $this->swooleResponse = $swooleResponse;
    }

    /**
     * @param string $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @param string $samesite
     * @param string $priority
     * @return $this
     */
    public function setCookies(
        string $key,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false,
        string $samesite = '',
        string $priority = ''
    ): self {
        $this->cookies = func_get_args();

        return $this;
    }

    /**
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function setHeader(string $key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getHeader(string $key)
    {
        return $this->headers[$key];
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param $content
     * @return $this
     */
    public function setContent($content): self
    {
        if (is_array($content)) {
            $content = json_encode($content);
        } elseif ($content instanceof Collection ||
            $content instanceof EloquentCollection ||
            $content instanceof Model ||
            $content instanceof Arrayable
        ) {
            $content = json_encode($content->toArray());
        } elseif ($content instanceof Jsonable) {
            $content = json_encode($content->toJson());
        } elseif ($content instanceof stdClass) {
            $content = json_encode((array)$content);
        } elseif ($content instanceof LengthAwarePaginator) {
            $content = json_encode((array)$content->items());
        }

        $this->content = (string)$content;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $chunkLimit
     *
     * @return $this
     */
    public function setChunkLimit(int $chunkLimit)
    {
        $this->chunkLimit = $chunkLimit;

        return $this;
    }

    /**
     * @return int
     */
    public function getChunkLimit(): int
    {
        return $this->chunkLimit;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setFileResponse(string $path)
    {
        $this->responseType = self::DOWNLOADRESPONSE;
        $this->filePath = $path;
        return $this;
    }

    /**
     * @param string $path
     * @param string|null $filename
     * @param int $offset
     * @param int $length
     * @return $this
     * @throws Exception
     */
    public function download(string $path, string $filename = null, int $offset = 0, int $length = 0)
    {
        if (!is_file($path)) {
            throw new Exception(sprintf('FILE [%s] NOT EXIST', $path));
        }
        $this->setFileResponse($path);

        if (is_null($filename)) {
            $pos = strrpos($path, '/');
            if (false === $pos) {
                $filename = $path;
            } else {
                $filename = substr($path, $pos + 1);
            }
        }
        $this->setResponseFileName($filename);

        return $this;
    }

    /**
     * @param string $filename
     * @return $this
     */
    protected function setResponseFileName(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @param string $path
     * @param int $statusCode
     * @return $this
     */
    public function redirect(string $path, int $statusCode = 200)
    {
        $this->responseType = self::REDIRECTRESPONSE;
        $this->redirectPath = $path;
        $this->status = $statusCode;

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function route(string $path)
    {
        $this->redirect(Config::get('app.url') . '/' . trim($path));

        return $this;
    }

    /**
     * @return SwooleResponse
     */
    public function getSwooleResponse(): SwooleResponse
    {
        return $this->swooleResponse;
    }

    public function send()
    {
        foreach ($this->headers as $key => $value) {
            $this->swooleResponse->setHeader($key, $value);
        }

        $this->swooleResponse->setStatusCode($this->status);

        if (count($this->cookies)) {
            $this->swooleResponse->setCookie(...$this->cookies);
        }


        if ($this->responseType == self::NORMALRESPONSE) {
            $this->end($this->content);
            return;
        }

        if ($this->responseType == self::DOWNLOADRESPONSE) {
            $this->swooleResponse->setHeader('Content-Disposition', 'attachment;filename=' . $this->filename);
            $this->swooleResponse->sendfile($this->filePath);
            return;
        }

        if ($this->responseType == self::REDIRECTRESPONSE) {
            $this->swooleResponse->redirect($this->redirectPath, $this->status);
            return;
        }
    }

    /**
     * @param StreamedResponse $response
     */
    public function handleStreamedResponse(StreamedResponse $response)
    {
        $this->swooleResponse->status($response->getStatusCode());

        $headers = method_exists($response->headers, 'allPreserveCaseWithoutCookies') ?
            $response->headers->allPreserveCaseWithoutCookies() : $response->headers->allPreserveCase();
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $this->swooleResponse->header($name, $value);
            }
        }

        $hasIsRaw = null;
        /**@var Cookie[] $cookies */
        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            if ($hasIsRaw === null) {
                $hasIsRaw = method_exists($cookie, 'isRaw');
            }
            $setCookie = $hasIsRaw && $cookie->isRaw() ? 'rawcookie' : 'cookie';
            $this->swooleResponse->$setCookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }

        ob_start();
        $response->sendContent();

        $this->end(ob_get_clean());
    }

    /**
     * @param $content
     */
    protected function end($content)
    {
        $len = strlen($content);
        if ($len === 0) {
            $this->swooleResponse->end();
            return;
        }

        if ($len > $this->chunkLimit) {
            for ($offset = 0, $limit = (int)(0.6 * $this->chunkLimit); $offset < $len; $offset += $limit) {
                $chunk = substr($content, $offset, $limit);
                $this->swooleResponse->write($chunk);
            }
            $this->swooleResponse->end();
        } else {
            $this->swooleResponse->end($content);
        }
    }

    /**
     * @param BinaryFileResponse $response
     * @throws ReflectionException
     */
    public function handleBinaryFileResponse(BinaryFileResponse $response)
    {
        /**@var File $file */
        $file = $response->getFile();
        $this->swooleResponse->header('Content-Type', $file->getMimeType());
        if ($response->getStatusCode() == BinaryFileResponse::HTTP_NOT_MODIFIED) {
            return;
        }

        $path = $file->getPathname();
        $size = filesize($path);
        if ($size <= 0) {
            return;
        }

        $reflection = new ReflectionObject($response);
        if ($reflection->hasProperty('deleteFileAfterSend')) {
            $deleteFileAfterSend = $reflection->getProperty('deleteFileAfterSend');
            $deleteFileAfterSend->setAccessible(true);
            $deleteFile = $deleteFileAfterSend->getValue($response);
        } else {
            $deleteFile = false;
        }

        if ($deleteFile) {
            $fp = fopen($path, 'rb');

            for ($offset = 0, $limit = (int)(0.99 * $this->chunkLimit); $offset < $size; $offset += $limit) {
                fseek($fp, $offset, SEEK_SET);
                $chunk = fread($fp, $limit);
                $this->swooleResponse->write($chunk);
            }

            $this->swooleResponse->end();
            fclose($fp);

            if (file_exists($path)) {
                unlink($path);
            }
        } else {
            $this->swooleResponse->sendfile($path);
        }
    }
}