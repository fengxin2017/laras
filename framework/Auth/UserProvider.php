<?php


namespace Laras\Auth;


use Laras\Contracts\Auth\UserProvider as UserProviderContract;
use Laras\Facades\Config;
use Laras\Facades\Redis;

class UserProvider implements UserProviderContract
{
    /**
     * @param int $identifier
     * @return bool
     */
    public function retrieveById(int $identifier)
    {
        $model = Config::get('auth.model');

        if ($user = $model::find($identifier)) {
            return $user;
        }

        return false;
    }

    /**
     * @param string $token
     * @return bool|mixed
     */
    public function retrieveByToken(string $token)
    {
        $userId = Redis::get($token);

        if (!$userId) {
            return false;
        }

        $model = Config::get('auth.model');

        if ($user = $model::find($userId)) {
            return $user;
        }

        return false;
    }
}