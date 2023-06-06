<?php

use App\Http\Controllers\HttpController;
use Laras\Router\Router;


Router::addGroup('/v1', function () {
    Router::get('/index', [HttpController::class, 'index'], ['middleware' => \App\Http\Middleware\Bar::class]);
},[
    'middleware' => \App\Http\Middleware\Foo::class
]);

Router::post('/test/index/{name}',[HttpController::class,'index']);
