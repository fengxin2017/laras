<?php
use Laras\Router\Router;
use App\Http\Controllers\WebSocketController;

Router::get('test',[WebSocketController::class,'test']);
Router::get('hello',[WebSocketController::class,'hello']);