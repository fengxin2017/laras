<?php


namespace MoneyMaker\Contracts\Pool;


Interface Pool
{
    public function get($timeout = -1);

    public function put($connection);
}