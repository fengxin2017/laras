<?php


namespace MoneyMaker\Contracts\Auth;


interface Authenticatable
{

    public function getAuthIdentifierName();
    
    public function getAuthIdentifier();
}