<?php


namespace App\Http\Middleware;


use Closure;
use Laras\Http\Request;
use Laras\Http\Response;

class TrimStrings
{
    protected $except = [];

    /**
     * @param Request $request
     * @param Response $response
     * @param Closure $next
     * @return bool|mixed
     */
    public function handle(Request $request, Response $response, Closure $next)
    {
        $input = $request->all();

        $this->cleanArray($input);

        return $next($request, $response);
    }

    /**
     * @param array $items
     */
    protected function cleanArray(array &$items)
    {
        foreach ($items as $key => &$item) {
            if (is_array($item)) {
                $this->cleanArray($item);
            } else {
                $item = $this->transform($key, $item);
            }
        }
    }

    /**
     * @param string $key
     * @param $value
     * @return string
     */
    protected function transform(string $key, &$value)
    {
        if (in_array($key, $this->except, true)) {
            return $value;
        }

        return is_string($value) ? trim($value) : $value;
    }
}