<?php


namespace App\Http\Controllers;


use Laras\WebSocket\Request;
use Laras\WebSocket\Response;

class WebSocketController extends BaseController
{
    public function test(Response $response)
    {
        $content = <<<HTML
    <h1>Swoole WebSocket Server</h1>
    <script>
var wsServer = 'ws://192.168.20.10:9505/websocket/hello';
var websocket = new WebSocket(wsServer);
websocket.onopen = function (evt) {
    console.log("Connected to WebSocket server.");
    websocket.send('hello,server');
};

websocket.onclose = function (evt) {
    console.log("Disconnected");
};

websocket.onmessage = function (evt) {
    console.log('Retrieved data from server: ' + evt.data);
};

websocket.onerror = function (evt, e) {
    console.log('Error occured: ' + evt.data);
};
</script>
HTML;
//        return $response->setHeader('Content-type', 'text/html')->setContent($content);
        \Laras\Facades\Response::setHeader('Content-type', 'text/html');
        return \Laras\Facades\Response::setContent($content);
    }

    public function hello(Request $request, Response $response)
    {
        var_dump($request->getFrame());
        //var_dump($request->getMessage());
        $response->push('hello client');
    }

    public function close(Request $request, Response $response)
    {
        $response->close();
    }
}