<?php


namespace Laras\Contracts\Auth;


interface Authenticatable
{

    public function getAuthIdentifierName();
    
    public function getAuthIdentifier();
}