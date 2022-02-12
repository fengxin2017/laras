<?php


namespace Laras\Facades;


use Exception;
use Laras\Auth\AuthManager;
use Laras\Contracts\Auth\Authenticatable;

/**
 * Class Auth
 * @package Laras\Facades
 * @method static AuthManager setId(int $userId)
 * @method static int id()
 * @method static bool jwtCheck()
 * @method static bool logout()
 * @method static bool attempt(string $token)
 * @method static string jwtloginUser(Authenticatable $user, int $expire)
 * @method static login($user)
 * @method static bool loginUsingId(int $userId)
 * @method static string getToken()
 * @method static setToken(string $token)
 * @method static user()
 */
class Auth extends Facade
{
    /**
     * @return bool|mixed|object|void
     * @throws Exception
     */
    public function getAccessor()
    {
        return AuthManager::class;
    }
}