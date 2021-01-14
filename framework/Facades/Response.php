<?php


namespace MoneyMaker\Facades;

use MoneyMaker\Http\Response as MoneyMakerResponse;

/**
 * Class Response
 * @package MoneyMaker\Facades
 * @method static setCookies(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '', string $priority = '')
 * @method static getCookies()
 * @method static setHeader(string $key, $value)
 * @method static getHeader($key = null)
 * @method static setStatus(int $status)
 * @method static getStatus()
 * @method static setContent($content)
 * @method static getContent()
 * @method static download(string $path, string $filename = null, int $offset = 0, int $length = 0)
 * @method static redirect(string $path, int $statusCode = 200)
 * @method static route(string $path)
 */
class Response extends Facade
{
    public function getAccessor(): string
    {
        return MoneyMakerResponse::class;
    }
}