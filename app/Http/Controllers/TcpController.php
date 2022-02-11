<?php


namespace App\Http\Controllers;


use Laras\Tcp\Request;

class TcpController extends BaseController
{
    public function handle(Request $request)
    {
        $request->getConnection()->send($request->getRequestRaw());
    }
}