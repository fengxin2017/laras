<?php


namespace Laras\Contracts\Auth;


interface UserProvider
{
    public function retrieveById(int $identifier);
}