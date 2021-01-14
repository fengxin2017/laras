<?php

use App\Http\Controllers\HttpController;
use MoneyMaker\Router\Router;


Router::addGroup('/v1', function () {
    Router::get('/index', [HttpController::class, 'index'], ['middleware' => \App\Http\Middleware\Bar::class]);
},[
    'middleware' => \App\Http\Middleware\Foo::class
]);

Router::get('/test/test', [HttpController::class, 'test'], [
    //'middleware' => \App\Http\Middleware\Foo::class
]);

Router::get('/test/index',[HttpController::class,'index']);
