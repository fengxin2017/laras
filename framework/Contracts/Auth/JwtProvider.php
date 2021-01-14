<?php


namespace MoneyMaker\Contracts\Auth;


Interface JwtProvider extends UserProvider
{
    public function retrieveByJwt(string $jwt);
}