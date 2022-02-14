<?php

use App\Http\Controllers\HttpController;
use Laras\Router\Router;

Router::get('index/{name}',[HttpController::class,'index']);
Router::get('response',[HttpController::class,'response']);
Router::get('middleware', [HttpController::class, 'middleware']);
Router::get('event',[HttpController::class,'event']);
Router::get('job',[HttpController::class,'job']);
Router::get('validates',[HttpController::class,'validates']);
Router::get('ratelimit',[HttpController::class,'ratelimit']);
Router::get('login',[HttpController::class,'login']);
Router::get('auth',[HttpController::class,'auth']);
Router::get('testAuth',[HttpController::class,'testAuth']);
