<?php


namespace App\Http\Controllers;


use Laras\Tcp\Request;

class TcpController extends Controller
{
    public function handle(Request $request)
    {
        $request->getConnection()->send($request->getRequestData());
    }
}