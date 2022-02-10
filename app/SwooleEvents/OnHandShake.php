<?php


namespace App\SwooleEvents;


use Laras\Contracts\Foundation\Application;
use Laras\Support\Annotation\Inject;
use Swoole\Http\Request;
use Swoole\Http\Response;

class OnHandShake
{
    /**
     * @Inject()
     * @var Application $app
     */
    protected $app;

    /**
     * @param Request $request
     * @param Response $response
     */
    public function handle(Request $request, Response $response)
    {
        //var_dump('shake hand success~');
    }
}