<?php


namespace Laras\Contracts\Auth;

/**
 * Interface AuthGuard
 * @package App\AuthManager
 */
Interface AuthGuard
{
    /**
     * @param string $token
     * @return mixed
     */
    public function attempt(string $token);

    /**
     * @param Authenticatable $user
     * @return mixed
     */
    public function login(Authenticatable $user);

    /**
     * @param int $id
     * @return mixed
     */
    public function loginUsingId(int $id);

    /**
     * @return mixed
     */
    public function logout();
}