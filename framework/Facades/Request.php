<?php


namespace MoneyMaker\Facades;

use MoneyMaker\Http\Request as HttpRequest;

/**
 * Class Request
 * @package MoneyMaker\Facades
 * @method static get(string $key = null)
 * @method static post(string $key = null)
 * @method static file(string $key = null)
 * @method static header(string $key = null)
 */
class Request extends Facade
{
    public function getAccessor(): string
    {
        return HttpRequest::class;
    }
}