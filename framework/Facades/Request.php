<?php


namespace Laras\Facades;

use Laras\Http\Request as HttpRequest;

/**
 * Class Request
 * @package Laras\Facades
 * @method static get(string $key = null)
 * @method static post(string $key = null)
 * @method static file(string $key = null)
 * @method static header(string $key = null)
 * @method static user()
 */
class Request extends Facade
{
    public function getAccessor(): string
    {
        return HttpRequest::class;
    }
}