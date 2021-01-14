<?php


namespace MoneyMaker\Contracts\Auth;


interface UserProvider
{
    public function retrieveById(int $identifier);
}